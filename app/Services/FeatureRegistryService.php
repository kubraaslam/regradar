<?php

namespace App\Services;

use App\Models\FeatureRegistry;

class FeatureRegistryService
{
    /**
     * The registry in the shape sent to the model.
     *
     * When a repository is given, only entries that apply to it are returned:
     * entries scoped to that repository, plus unscoped entries unless strict
     * mode is on. The evaluation uses strict mode so that every pull request is
     * scored against exactly one repository's registry.
     */
    public function getFormattedForLLM(int $userId, ?string $repository = null, bool $strict = false): array
    {
        return FeatureRegistry::where('user_id', $userId)
            ->forRepository($repository !== null ? strtolower($repository) : null, $strict)
            ->orderBy('feature_name')
            ->get()
            ->map(fn($f) => [
                'feature' => $f->feature_name,
                'code_areas' => $f->code_areas,
                'endpoints' => $f->endpoints,
                'description' => $f->description,
            ])
            ->values()
            ->toArray();
    }
}
