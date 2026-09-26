<?php

namespace App\Services;

class RiskScoringService
{
    public function scoreAndRank(array $llmOutput, array $preprocessedDiff): array
    {
        $risks = $llmOutput['risks'] ?? [];

        // Sort by risk level
        $order = ['High' => 0, 'Medium' => 1, 'Low' => 2];

        usort($risks, function ($a, $b) use ($order) {
            return ($order[$a['risk_level']] ?? 3) <=> ($order[$b['risk_level']] ?? 3);
        });

        // Add metadata to each risk
        foreach ($risks as &$risk) {
            $risk['badge_class'] = match ($risk['risk_level']) {
                'High' => 'badge-high',
                'Medium' => 'badge-medium',
                'Low' => 'badge-low',
                default => 'badge-neutral',
            };
        }

        return [
            'risks' => $risks,
            'summary' => $llmOutput['summary'] ?? '',
            'testing_focus' => $llmOutput['testing_focus'] ?? [],
            'total_high' => count(array_filter($risks, fn($r) => $r['risk_level'] === 'High')),
            'total_medium' => count(array_filter($risks, fn($r) => $r['risk_level'] === 'Medium')),
            'total_low' => count(array_filter($risks, fn($r) => $r['risk_level'] === 'Low')),
            'changed_files' => $preprocessedDiff['changed_files'] ?? [],
        ];
    }
}