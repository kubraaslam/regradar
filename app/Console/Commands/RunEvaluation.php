<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DiffPreprocessor;
use App\Services\FeatureRegistryService;
use App\Services\GitHubService;
use App\Services\KeywordBaselineService;
use App\Services\LLMService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Runs the RO4 ablation: every prompting strategy against every model, plus the
 * deterministic keyword baseline, across the curated evaluation dataset.
 *
 * Raw per-prediction rows are written to CSV for scoring in Python with pandas
 * and scikit-learn, as set out in the proposal. A summary table is also printed
 * so that a run can be sanity checked without leaving the terminal.
 */
class RunEvaluation extends Command
{
    protected $signature = 'regradar:evaluate
        {--user= : User whose Feature Registry to evaluate against. Defaults to the first user.}
        {--models= : Comma separated model subset. Defaults to all three.}
        {--strategies= : Comma separated strategy subset. Defaults to all three.}
        {--limit= : Only evaluate the first N pull requests.}
        {--baseline-only : Run only the keyword baseline. Makes no API calls.}
        {--skip-baseline : Run only the language models.}
        {--out= : Output CSV path. Defaults to storage/app/evaluation/<timestamp>.csv}';

    protected $description = 'Run the model and prompting strategy ablation over the evaluation dataset';

    public function handle(
        GitHubService $github,
        DiffPreprocessor $preprocessor,
        FeatureRegistryService $registryService,
        LLMService $llm,
        KeywordBaselineService $baseline,
    ): int {
        $dataset = $this->loadDataset();

        if ($dataset === null) {
            return self::FAILURE;
        }

        $userId = $this->option('user') ?: User::orderBy('id')->value('id');
        $registry = $registryService->getFormattedForLLM((int) $userId);

        if ($registry === []) {
            $this->error('The Feature Registry for this user is empty. Populate it before evaluating.');

            return self::FAILURE;
        }

        $conditions = $this->resolveConditions();
        $rows = [];

        $this->info(sprintf(
            'Evaluating %d pull request(s) against %d registry feature(s) under %d condition(s).',
            count($dataset),
            count($registry),
            count($conditions)
        ));
        $this->newLine();

        foreach ($dataset as $index => $case) {
            $label = $case['pr_url'];
            $this->line(sprintf('[%d/%d] %s', $index + 1, count($dataset), $label));

            // Fetched and preprocessed ONCE per pull request, then reused for
            // every condition. This keeps GitHub requests proportional to the
            // dataset rather than to the dataset times the condition count, and
            // guarantees that all conditions are scored on identical input.
            try {
                $preprocessed = $preprocessor->process($github->fetchDiff($case['pr_url']));
            } catch (Throwable $e) {
                $this->error('  could not fetch diff: ' . $e->getMessage());
                continue;
            }

            foreach ($conditions as $condition) {
                [$model, $strategy] = $condition;

                try {
                    if ($model === 'keyword-baseline') {
                        $started = microtime(true);
                        $output = $baseline->analyse($preprocessed, $registry);
                        $usage = [
                            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                            'prompt_tokens' => 0,
                            'completion_tokens' => 0,
                        ];
                    } else {
                        $output = $llm->analyse($model, $strategy, $preprocessed['summary'], $registry);
                        $usage = $llm->lastUsage();
                    }

                    $rows = array_merge($rows, $this->rowsFor($case, $model, $strategy, $output, $usage, $registry));

                    $this->line(sprintf(
                        '  %-22s %-17s %4dms  %d prediction(s)',
                        $model,
                        $strategy,
                        $usage['duration_ms'] ?? 0,
                        count($output['risks'])
                    ));
                } catch (Throwable $e) {
                    $this->warn(sprintf('  %-22s %-17s FAILED  %s', $model, $strategy, $e->getMessage()));

                    $rows[] = $this->baseRow($case, $model, $strategy) + [
                        'feature' => '',
                        'predicted_risk' => '',
                        'expected_risk' => '',
                        'outcome' => 'error',
                        'duration_ms' => '',
                        'prompt_tokens' => '',
                        'completion_tokens' => '',
                        'reason' => substr($e->getMessage(), 0, 300),
                    ];
                }
            }

            $this->newLine();
        }

        $path = $this->writeCsv($rows);
        $this->summarise($rows);

        $this->newLine();
        $this->info('Per-prediction rows written to: ' . $path);
        $this->line('Score these with pandas and scikit-learn to produce Precision, Recall and F1 per condition.');

        return self::SUCCESS;
    }

    /**
     * Build one row per predicted or expected feature, so that true positives,
     * false positives and false negatives are all represented explicitly.
     */
    protected function rowsFor(array $case, string $model, string $strategy, array $output, array $usage, array $registry): array
    {
        $expected = [];

        foreach ($case['expected'] ?? [] as $entry) {
            $expected[$entry['feature']] = $entry['risk_level'];
        }

        $rows = [];
        $seen = [];

        foreach ($output['risks'] as $risk) {
            $feature = $risk['feature'] ?? '';
            $seen[$feature] = true;
            $predicted = $risk['risk_level'] ?? '';

            if (!array_key_exists($feature, $expected)) {
                $outcome = 'false_positive';
            } elseif ($expected[$feature] === $predicted) {
                $outcome = 'true_positive';
            } else {
                // Identified the right feature but graded it differently. Kept
                // distinct so that feature identification (RQ1) and severity
                // grading can be scored separately.
                $outcome = 'true_positive_wrong_level';
            }

            $rows[] = $this->baseRow($case, $model, $strategy) + [
                'feature' => $feature,
                'predicted_risk' => $predicted,
                'expected_risk' => $expected[$feature] ?? '',
                'outcome' => $outcome,
                'duration_ms' => $usage['duration_ms'] ?? '',
                'prompt_tokens' => $usage['prompt_tokens'] ?? '',
                'completion_tokens' => $usage['completion_tokens'] ?? '',
                'reason' => substr((string) ($risk['reason'] ?? ''), 0, 300),
            ];
        }

        // Expected features the condition never mentioned.
        foreach ($expected as $feature => $level) {
            if (isset($seen[$feature])) {
                continue;
            }

            $rows[] = $this->baseRow($case, $model, $strategy) + [
                'feature' => $feature,
                'predicted_risk' => '',
                'expected_risk' => $level,
                'outcome' => 'false_negative',
                'duration_ms' => $usage['duration_ms'] ?? '',
                'prompt_tokens' => $usage['prompt_tokens'] ?? '',
                'completion_tokens' => $usage['completion_tokens'] ?? '',
                'reason' => '',
            ];
        }

        return $rows;
    }

    protected function baseRow(array $case, string $model, string $strategy): array
    {
        return [
            'pr_url' => $case['pr_url'],
            'repository' => $case['repository'] ?? '',
            'model' => $model,
            'strategy' => $strategy,
        ];
    }

    protected function resolveConditions(): array
    {
        $conditions = [];

        if (!$this->option('skip-baseline')) {
            $conditions[] = ['keyword-baseline', 'none'];
        }

        if ($this->option('baseline-only')) {
            return $conditions;
        }

        $models = $this->option('models')
            ? array_map('trim', explode(',', $this->option('models')))
            : LLMService::MODELS;

        $strategies = $this->option('strategies')
            ? array_map('trim', explode(',', $this->option('strategies')))
            : LLMService::STRATEGIES;

        foreach ($strategies as $strategy) {
            foreach ($models as $model) {
                $conditions[] = [$model, $strategy];
            }
        }

        return $conditions;
    }

    protected function loadDataset(): ?array
    {
        $path = database_path('evaluation/ground_truth.json');

        if (!is_file($path)) {
            $this->error('Ground truth file not found at ' . $path);

            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (!is_array($decoded) || !isset($decoded['pull_requests'])) {
            $this->error('Ground truth file is not valid JSON, or has no pull_requests key.');

            return null;
        }

        $cases = $decoded['pull_requests'];

        if ($limit = $this->option('limit')) {
            $cases = array_slice($cases, 0, (int) $limit);
        }

        return $cases;
    }

    protected function writeCsv(array $rows): string
    {
        $path = $this->option('out')
            ?: storage_path('app/evaluation/' . now()->format('Y-m-d_His') . '.csv');

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $handle = fopen($path, 'w');

        $columns = [
            'pr_url', 'repository', 'model', 'strategy', 'feature',
            'predicted_risk', 'expected_risk', 'outcome',
            'duration_ms', 'prompt_tokens', 'completion_tokens', 'reason',
        ];

        fputcsv($handle, $columns);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn($c) => $row[$c] ?? '', $columns));
        }

        fclose($handle);

        return $path;
    }

    /**
     * Console-only convenience view. The authoritative metrics come from the CSV.
     */
    protected function summarise(array $rows): void
    {
        $byCondition = [];

        foreach ($rows as $row) {
            $key = $row['model'] . ' / ' . $row['strategy'];

            $byCondition[$key] ??= ['tp' => 0, 'level' => 0, 'fp' => 0, 'fn' => 0, 'err' => 0, 'ms' => [], 'tokens' => 0];

            match ($row['outcome']) {
                'true_positive' => $byCondition[$key]['tp']++,
                'true_positive_wrong_level' => $byCondition[$key]['level']++,
                'false_positive' => $byCondition[$key]['fp']++,
                'false_negative' => $byCondition[$key]['fn']++,
                default => $byCondition[$key]['err']++,
            };

            if ($row['duration_ms'] !== '') {
                $byCondition[$key]['ms'][] = (int) $row['duration_ms'];
            }

            $byCondition[$key]['tokens'] += (int) ($row['prompt_tokens'] ?: 0) + (int) ($row['completion_tokens'] ?: 0);
        }

        $table = [];

        foreach ($byCondition as $condition => $c) {
            $precision = ($c['tp'] + $c['fp'] + $c['level']) > 0
                ? $c['tp'] / ($c['tp'] + $c['fp'] + $c['level'])
                : 0.0;

            $recall = ($c['tp'] + $c['fn'] + $c['level']) > 0
                ? $c['tp'] / ($c['tp'] + $c['fn'] + $c['level'])
                : 0.0;

            $f1 = ($precision + $recall) > 0 ? 2 * $precision * $recall / ($precision + $recall) : 0.0;

            $table[] = [
                $condition,
                $c['tp'],
                $c['level'],
                $c['fp'],
                $c['fn'],
                number_format($precision, 2),
                number_format($recall, 2),
                number_format($f1, 2),
                $c['ms'] ? round(array_sum($c['ms']) / count($c['ms'])) . 'ms' : '-',
                $c['tokens'] ?: '-',
            ];
        }

        $this->newLine();
        $this->table(
            ['Condition', 'TP', 'Wrong level', 'FP', 'FN', 'P', 'R', 'F1', 'Avg time', 'Tokens'],
            $table
        );
        $this->comment('Indicative only. Exact-match on feature name and risk level; score the CSV for the reported figures.');
    }
}
