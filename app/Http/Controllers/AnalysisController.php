<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
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

        // Create a pending analysis record. The model and strategy are stored up
        // front rather than on success, so that a failed run still records which
        // combination produced the failure.
        $analysis = Analysis::create([
            'user_id' => auth()->id(),
            'pr_url' => $validated['pr_url'],
            'status' => 'pending',
            'repo_owner' => '',
            'repo_name' => '',
            'pr_number' => 0,
            'llm_model' => $validated['llm_model'],
            'prompting_strategy' => $validated['prompting_strategy'],
        ]);

        try {
            // Step 1: Fetch diff from GitHub
            $rawDiff = $this->githubService->fetchDiff($validated['pr_url']);

            // Step 2: Preprocess the diff
            $preprocessed = $this->preprocessor->process($rawDiff);

            // Step 3: Get feature registry
            $registry = $this->registryService->getFormattedForLLM(auth()->id());

            if (empty($registry)) {
                return redirect()->route('feature-registry.create')
                    ->with('warning', 'Please add features to your registry before running an analysis.');
            }

            // Step 4: Call the selected model with the selected prompting strategy
            $llmOutput = $this->llmService->analyse(
                $validated['llm_model'],
                $validated['prompting_strategy'],
                $preprocessed['summary'],
                $registry
            );

            // Step 5: Score and rank
            $results = $this->scoringService->scoreAndRank($llmOutput, $preprocessed);

            // Step 6: Save results alongside what the run cost to produce
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

    public function show(Analysis $analysis)
    {
        return view('analysis.show', compact('analysis'));
    }

    public function index()
    {
        $analyses = Analysis::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();
        return view('analysis.index', compact('analyses'));
    }
}