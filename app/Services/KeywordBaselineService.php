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
        $haystack = $this->buildHaystack($preprocessed);
        $risks = [];

        foreach ($registry as $feature) {
            $matched = [];

            foreach (array_merge($feature['code_areas'] ?? [], $feature['endpoints'] ?? []) as $needle) {
                $needle = trim((string) $needle);

                if ($needle === '') {
                    continue;
                }

                foreach ($haystack as $candidate) {
                    if (stripos($candidate, $needle) !== false) {
                        $matched[] = $needle;
                        break;
                    }
                }
            }

            $matched = array_values(array_unique($matched));

            if ($matched === []) {
                continue;
            }

            $risks[] = [
                'feature' => $feature['feature'],
                // Always High: a textual match is by definition a direct overlap.
                // This baseline has no mechanism for grading severity.
                'risk_level' => 'High',
                'reason' => 'Direct textual match on ' . implode(', ', $matched)
                    . ' in the changed files or functions.',
            ];
        }

        return [
            'risks' => $risks,
            'summary' => $this->summarise($risks),
            'testing_focus' => [],
        ];
    }

    /**
     * Every string from the diff that a registry entry could match against.
     */
    protected function buildHaystack(array $preprocessed): array
    {
        return array_values(array_filter(array_merge(
            $preprocessed['changed_files'] ?? [],
            $preprocessed['changed_functions'] ?? [],
            $preprocessed['changed_endpoints'] ?? [],
        )));
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
