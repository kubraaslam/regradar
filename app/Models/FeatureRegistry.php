<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FeatureRegistry extends Model
{
    protected $fillable = [
        'user_id',
        'repository',
        'feature_name',
        'code_areas',
        'endpoints',
        'description'
    ];

    protected $casts = [
        'code_areas' => 'array',
        'endpoints' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Entries the given user may see: their own, or every entry for roles that
     * read across the whole team.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->canViewAll() ? $query : $query->where('user_id', $user->id);
    }

    /**
     * Entries that apply to a pull request from the given repository.
     *
     * Inclusive mode also returns unscoped entries, which describe features in
     * general. Strict mode returns only entries scoped to that repository, which
     * is what the evaluation requires so that each pull request is scored
     * against exactly one repository's registry.
     */
    public function scopeForRepository(Builder $query, ?string $repository, bool $strict = false): Builder
    {
        if ($repository === null) {
            return $query;
        }

        if ($strict) {
            return $query->where('repository', $repository);
        }

        return $query->where(fn(Builder $q) => $q->where('repository', $repository)->orWhereNull('repository'));
    }
}
