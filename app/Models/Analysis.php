<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Analysis extends Model
{
    protected $fillable = [
        'user_id',
        'pr_url',
        'repo_owner',
        'repo_name',
        'pr_number',
        'results',
        'status',
        'llm_model',
        'prompting_strategy',
        'duration_ms',
        'prompt_tokens',
        'completion_tokens',
    ];

    protected $casts = [
        'results' => 'array',
        'duration_ms' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Analyses the given user may see: their own, or every analysis for roles
     * that read across the whole team.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->canViewAll() ? $query : $query->where('user_id', $user->id);
    }

    public function repository(): ?string
    {
        return $this->repo_owner && $this->repo_name
            ? $this->repo_owner . '/' . $this->repo_name
            : null;
    }
}