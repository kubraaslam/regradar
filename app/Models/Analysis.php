<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    /**
     * The overall verdict for a completed analysis, driven by its most severe
     * finding. Shared by the report page and the PDF export so the two can
     * never give a different recommendation for the same analysis.
     *
     * @return array{label: string, level: string, tone: string, copy: string}
     */
    public function verdict(): array
    {
        $results = is_array($this->results) ? $this->results : [];

        if (($results['total_high'] ?? 0) > 0) {
            return ['label' => 'High risk', 'level' => 'High', 'tone' => 'flare',
                'copy' => 'Run a full regression pass on the features below before merging.'];
        }

        if (($results['total_medium'] ?? 0) > 0) {
            return ['label' => 'Moderate risk', 'level' => 'Medium', 'tone' => 'amber',
                'copy' => 'A targeted smoke test over the affected features should be enough.'];
        }

        return ['label' => 'Low risk', 'level' => 'Low', 'tone' => 'brand',
            'copy' => 'Nothing high or medium risk surfaced for this pull request.'];
    }

    /**
     * A descriptive download name, for example
     * regradar-laravel-framework-pr-41969.pdf. Older analyses stored before the
     * repository was recorded fall back to their ID.
     */
    public function reportFilename(): string
    {
        $owner = $this->repo_owner;
        $repo = $this->repo_name;
        $number = $this->pr_number;

        // Analyses created before the repository columns were populated
        // only carry the pull request URL.
        if (! ($owner && $repo && $number)
            && preg_match('#github\.com/([^/]+)/([^/]+)/pull/(\d+)#', (string) $this->pr_url, $m)) {
            [, $owner, $repo, $number] = $m;
        }

        if ($owner && $repo && $number) {
            return sprintf('regradar-%s-%s-pr-%d.pdf', Str::slug($owner), Str::slug($repo), $number);
        }

        return 'regradar-analysis-' . $this->id . '.pdf';
    }

    public function repository(): ?string
    {
        return $this->repo_owner && $this->repo_name
            ? $this->repo_owner . '/' . $this->repo_name
            : null;
    }
}