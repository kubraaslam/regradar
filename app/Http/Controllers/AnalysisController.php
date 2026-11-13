<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\GitHubService;
use App\Services\DiffPreprocessor;
use App\Services\LLMService;
use App\Services\FeatureRegistryService;
use App\Services\RiskScoringService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Exception;

class AnalysisController extends Controller
{
    public function __construct(
        protected GitHubService $githubService,
        protected DiffPreprocessor $preprocessor,
        protected LLMService $llmService,
        protected FeatureRegistryService $registryService,
        protected RiskScoringService $scoringService,
    ) {
    }

    public function create()
    {
        return view('analysis.create', [
            'models' => LLMService::MODEL_LABELS,
            'strategies' => LLMService::STRATEGY_LABELS,
            'defaultModel' => LLMService::MODEL_OPENAI,
            'defaultStrategy' => LLMService::STRATEGY_ZERO_SHOT,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pr_url' => 'required|url',
            'llm_model' => ['required', Rule::in(LLMService::MODELS)],
            'prompting_strategy' => ['required', Rule::in(LLMService::STRATEGIES)],
        ]);

        // A URL that is not a pull request is an input error, not an analysis
        // failure, so it is reported against the field and no record is created.
        try {
            $pr = GitHubService::parsePullRequestUrl($validated['pr_url']);
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['pr_url' => $e->getMessage()]);
        }

        // Only entries that apply to this pull request's repository are sent to
        // the model: those scoped to it, plus unscoped general entries.
        $registry = $this->registryService->getFormattedForLLM(auth()->id(), $pr['repository']);

        // Checked before the record is created, so an empty registry no longer
        // leaves an orphaned analysis stuck in the pending state.
        if ($registry === []) {
            return redirect()->route('feature-registry.create')
                ->with('warning', "No Feature Registry entries apply to {$pr['repository']}. Add features scoped to that repository, or unscoped features, before running an analysis.");
        }

        // Create a pending analysis record. The model and strategy are stored up
        // front rather than on success, so that a failed run still records which
        // combination produced the failure.
        $analysis = Analysis::create([
            'user_id' => auth()->id(),
            'pr_url' => $validated['pr_url'],
            'status' => 'pending',
            'repo_owner' => $pr['owner'],
            'repo_name' => $pr['repo'],
            'pr_number' => $pr['number'],
            'llm_model' => $validated['llm_model'],
            'prompting_strategy' => $validated['prompting_strategy'],
        ]);

        try {
            // Step 1: Fetch diff from GitHub
            $rawDiff = $this->githubService->fetchDiff($validated['pr_url']);

            // Step 2: Preprocess the diff
            $preprocessed = $this->preprocessor->process($rawDiff);

            // Step 3: Call the selected model with the selected prompting strategy
            $llmOutput = $this->llmService->analyse(
                $validated['llm_model'],
                $validated['prompting_strategy'],
                $preprocessed['summary'],
                $registry
            );

            // Step 4: Score and rank
            $results = $this->scoringService->scoreAndRank($llmOutput, $preprocessed);

            // Step 5: Save results alongside what the run cost to produce
            $usage = $this->llmService->lastUsage();

            $analysis->update([
                'results' => $results,
                'status' => 'completed',
                'duration_ms' => $usage['duration_ms'],
                'prompt_tokens' => $usage['prompt_tokens'],
                'completion_tokens' => $usage['completion_tokens'],
            ]);

            return redirect()->route('analysis.show', $analysis);

        } catch (Exception $e) {
            $analysis->update(['status' => 'failed']);

            // Full trace goes to the log; the user sees the reason without the
            // server file path, which should not be disclosed in the interface.
            Log::error('Analysis failed', [
                'analysis_id' => $analysis->id,
                'llm_model' => $validated['llm_model'],
                'prompting_strategy' => $validated['prompting_strategy'],
                'exception' => $e,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Analysis failed: ' . $e->getMessage());
        }
    }

    public function show(Request $request, Analysis $analysis)
    {
        $this->authorizeView($request, $analysis);

        $analysis->loadMissing('user');

        return view('analysis.show', compact('analysis'));
    }

    /**
     * SRS FR11: download the risk report as a PDF.
     *
     * Rendered from a dedicated print template rather than the screen view,
     * because Dompdf supports only a subset of CSS and the interface relies on
     * flexbox, grid and a dark background that would not print legibly.
     */
    public function pdf(Request $request, Analysis $analysis)
    {
        $this->authorizeView($request, $analysis);

        abort_unless(
            $analysis->status === 'completed' && is_array($analysis->results),
            404,
            'Only completed analyses can be exported.'
        );

        $analysis->loadMissing('user');

        return Pdf::loadView('analysis.pdf', [
            'analysis' => $analysis,
            'generatedAt' => now(),
        ])
            ->setPaper('a4')
            ->setOption([
                // Embeds only the glyphs used: roughly 1.1 MB down to tens of KB.
                'isFontSubsettingEnabled' => true,
                // Kept off explicitly: the report never needs remote assets, and
                // allowing them would let report content trigger server requests.
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
            ])
            ->download($analysis->reportFilename());
    }

    /**
     * Previously any signed-in QA engineer could open any analysis by changing
     * the ID in the URL.
     */
    protected function authorizeView(Request $request, Analysis $analysis): void
    {
        abort_unless(
            $request->user()->canViewAll() || $request->user()->owns($analysis),
            403,
            'You do not have access to this analysis.'
        );
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $all = Analysis::visibleTo($user)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        $filters = [
            'repository' => (string) $request->query('repository', ''),
            'status' => (string) $request->query('status', ''),
            'risk' => (string) $request->query('risk', ''),
            'model' => (string) $request->query('model', ''),
            'strategy' => (string) $request->query('strategy', ''),
            'owner' => $user->canViewAll() ? (string) $request->query('owner', '') : '',
        ];

        // History is small enough to filter in memory, which also covers older
        // analyses whose repository is known only from the pull request URL.
        $analyses = $all->filter(function (Analysis $a) use ($filters) {
            if ($filters['repository'] !== '' && $a->repository() !== $filters['repository']) {
                return false;
            }

            if ($filters['status'] === 'in_progress') {
                if (in_array($a->status, ['completed', 'failed'], true)) {
                    return false;
                }
            } elseif ($filters['status'] !== '' && $a->status !== $filters['status']) {
                return false;
            }

            if ($filters['risk'] === 'none') {
                if ($a->status !== 'completed' || $a->highestRisk() !== null) {
                    return false;
                }
            } elseif ($filters['risk'] !== '' && $a->highestRisk() !== $filters['risk']) {
                return false;
            }

            if ($filters['model'] !== '' && $a->llm_model !== $filters['model']) {
                return false;
            }

            if ($filters['strategy'] !== '' && $a->prompting_strategy !== $filters['strategy']) {
                return false;
            }

            return $filters['owner'] === '' || (string) $a->user_id === $filters['owner'];
        })->values();

        $options = [
            'repositories' => $all->map->repository()->filter()->unique()->sort()->values(),
            'owners' => $user->canViewAll()
                ? $all->pluck('user')->filter()->unique('id')->sortBy('name')->values()
                : collect(),
        ];

        return view('analysis.index', [
            'analyses' => $analyses,
            'total' => $all->count(),
            'filters' => $filters,
            'filtered' => collect($filters)->filter(fn($v) => $v !== '')->isNotEmpty(),
            'options' => $options,
        ]);
    }
}