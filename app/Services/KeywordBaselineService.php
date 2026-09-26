<?php

namespace App\Services;

/**
 * Deterministic baseline used for benchmarking, not for production analysis.
 *
 * This is the naive alternative a practitioner would reach for before
 * considering a language model: match each registry feature's declared code
 * areas and endpoints against the changed files, functions and endpoints of the
 * diff, and flag any feature with a direct textual hit.
 *
 * It exists to answer the question "why not just search the file names?" with a
 * measured figure rather than an assertion. Two properties make it a fair floor:
 * it uses exactly the same inputs and produces exactly the same output shape as
 * the LLM path, so it can be scored with the same metrics.
 *
 * Its structural limitation is the point of the comparison. Because it can only
 * observe textual overlap, it can never identify a feature that is at risk
 * through an indirect dependency on changed code. Every Medium rating in the
 * ground truth is therefore unreachable for this baseline by construction, and
 * that gap is what the language model has to justify its cost against.
 */
class KeywordBaselineService
{
    /**
     * @param array $preprocessed Output of DiffPreprocessor::process()
     * @param array $registry     Output of FeatureRegistryService::getFormattedForLLM()
     */
    public function analyse(array $preprocessed, array $registry): array
    {
        // Production and test paths are matched separately so that this baseline
        // is given the same production-versus-test signal as the language model.
        // Handicapping it would overstate the benefit of the LLM pipeline; the
        // comparison is only meaningful if the simple alternative is given every
        // fair advantage.
        $productionHaystack = $this->haystackFor($preprocessed, 'production');
        $testHaystack = $this->haystackFor($preprocessed, 'test');

        $risks = [];

        foreach ($registry as $feature) {
            $needles = $this->needlesFor($feature);

            $productionMatches = $this->match($needles, $productionHaystack);

            if ($productionMatches !== []) {
                $risks[] = [
                    'feature' => $feature['feature'],
                    // Always High on a production match: a textual hit is by
                    // definition a direct overlap, and this baseline has no
                    // mechanism for recognising an indirect dependency.
                    'risk_level' => 'High',
                    'reason' => 'Direct textual match on ' . implode(', ', $productionMatches)
                        . ' in the changed production files or functions.',
                ];

                continue;
            }

            $testMatches = $this->match($needles, $testHaystack);

            if ($testMatches !== []) {
                $risks[] = [
                    'feature' => $feature['feature'],
                    'risk_level' => 'Low',
                    'reason' => 'Textual match on ' . implode(', ', $testMatches)
                        . ' in changed test files only. No production code changed.',
                ];
            }
        }

        return [
            'risks' => $risks,
            'summary' => $this->summarise($risks),
            'testing_focus' => [],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function needlesFor(array $feature): array
    {
        $needles = array_merge($feature['code_areas'] ?? [], $feature['endpoints'] ?? []);

        return array_values(array_filter(array_map(
            fn($n) => trim((string) $n),
            $needles
        )));
    }

    /**
     * @return array<int, string>
     */
    protected function match(array $needles, array $haystack): array
    {
        $matched = [];

        foreach ($needles as $needle) {
            foreach ($haystack as $candidate) {
                if (stripos($candidate, $needle) !== false) {
                    $matched[] = $needle;
                    break;
                }
            }
        }

        return array_values(array_unique($matched));
    }

    /**
     * Every string from the diff that a registry entry could match against,
     * restricted to one side of the production and test split.
     *
     * @return array<int, string>
     */
    protected function haystackFor(array $preprocessed, string $side): array
    {
        $parts = $side === 'production'
            ? array_merge(
                $preprocessed['production_files'] ?? $preprocessed['changed_files'] ?? [],
                $preprocessed['changed_functions'] ?? [],
                $preprocessed['changed_endpoints'] ?? [],
            )
            : array_merge(
                $preprocessed['test_files'] ?? [],
                $preprocessed['test_functions'] ?? [],
            );

        return array_values(array_filter($parts));
    }

    protected function summarise(array $risks): string
    {
        if ($risks === []) {
            return 'No registry feature matched the changed files or functions by name.';
        }

        $names = array_column($risks, 'feature');

        return count($names) . ' feature(s) matched the diff by name: ' . implode(', ', $names) . '.';
    }
}
