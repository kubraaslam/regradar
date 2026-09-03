<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Services\GitHubService;
use App\Services\DiffPreprocessor;
use App\Services\LLMService;
use App\Services\FeatureRegistryService;
use App\Services\RiskScoringService;
use Illuminate\Http\Request;
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
        return view('analysis.create');
    }

    public function store(Request $request)
    {
        $request->validate(['pr_url' => 'required|url']);

        // Create a pending analysis record
        $analysis = Analysis::create([
            'user_id' => auth()->id(),
            'pr_url' => $request->pr_url,
            'status' => 'pending',
            'repo_owner' => '',
            'repo_name' => '',
            'pr_number' => 0,
        ]);

        try {
            // Step 1: Fetch diff from GitHub
            $rawDiff = $this->githubService->fetchDiff($request->pr_url);

            // Step 2: Preprocess the diff
            $preprocessed = $this->preprocessor->process($rawDiff);

            // Step 3: Get feature registry
            $registry = $this->registryService->getFormattedForLLM(auth()->id());

            if (empty($registry)) {
                return redirect()->route('feature-registry.create')
                    ->with('warning', 'Please add features to your registry before running an analysis.');
            }

            // Step 4: Call LLM
            $llmOutput = $this->llmService->analyseWithZeroShot(
                $preprocessed['summary'],
                $registry
            );

            // Step 5: Score and rank
            $results = $this->scoringService->scoreAndRank($llmOutput, $preprocessed);

            // Step 6: Save results
            $analysis->update([
                'results' => $results,
                'status' => 'completed',
            ]);

            return redirect()->route('analysis.show', $analysis);

        } catch (Exception $e) {
            $analysis->update(['status' => 'failed']);
            return redirect()->back()
                ->with('error', 'Analysis failed: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
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