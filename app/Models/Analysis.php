<?php

namespace App\Models;

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
}