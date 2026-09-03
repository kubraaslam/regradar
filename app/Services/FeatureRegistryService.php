<?php

namespace App\Services;

use App\Models\FeatureRegistry;

class FeatureRegistryService
{
    public function getFormattedForLLM(int $userId): array
    {
        return FeatureRegistry::where('user_id', $userId)
            ->get()
            ->map(fn($f) => [
                'feature' => $f->feature_name,
                'code_areas' => $f->code_areas,
                'endpoints' => $f->endpoints,
                'description' => $f->description,
            ])
            ->toArray();
    }
}