<?php

namespace App\Console\Commands;

use App\Models\Analysis;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * SRS NFR8: remove analyses older than the retention period whose owner has
 * not consented to longer retention.
 *
 * A dry run unless --force is given, so running it by hand never deletes
 * anything by accident. The scheduler passes --force; see routes/console.php.
 *
 * Only the analyses table is affected. Evaluation output in
 * storage/app/evaluation is research data derived from public repositories
 * and is outside the scope of NFR8; see docs/deviations.md.
 */
class PruneAnalyses extends Command
{
    protected $signature = 'regradar:prune-analyses
        {--force : Delete the analyses. Without it the command only reports what it would delete.}';

    protected $description = 'Delete analyses past the retention period whose owners have not consented to keep them';

    public function handle(): int
    {
        $days = config('regradar.retention_days');
        $cutoff = now()->subDays($days);
        $query = $this->expired($cutoff);
        $count = (clone $query)->count();

        $this->line(sprintf('Retention period: %d days (analyses created before %s).', $days, $cutoff->format('j M Y H:i')));

        if ($count === 0) {
            $this->info('Nothing to delete.');

            return self::SUCCESS;
        }

        $byOwner = (clone $query)->with('user:id,name')->get()
            ->groupBy(fn(Analysis $a) => $a->user?->name ?? 'Deleted user')
            ->map->count();

        $this->table(['Owner', 'Analyses past retention'], $byOwner->map(fn($n, $owner) => [$owner, $n])->values());

        if (!$this->option('force')) {
            $this->warn("Dry run: {$count} analyses would be deleted. Re-run with --force to delete them.");
            $this->line('Owners can keep their analyses by consenting under Profile, Data retention.');

            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Deleted {$deleted} analyses.");

        return self::SUCCESS;
    }

    /**
     * Analyses past the cutoff whose owner has not consented. An analysis whose
     * owner no longer exists has no one who could consent, so it is included.
     */
    protected function expired($cutoff): Builder
    {
        return Analysis::query()
            ->where('created_at', '<', $cutoff)
            ->where(fn(Builder $q) => $q
                ->whereDoesntHave('user')
                ->orWhereHas('user', fn(Builder $u) => $u->whereNull('retention_consent_at')));
    }
}
