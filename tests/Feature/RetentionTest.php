<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SRS NFR8: analyses are not stored beyond the retention period without
 * explicit consent.
 */
class RetentionTest extends TestCase
{
    use RefreshDatabase;

    private function analysis(User $owner, int $daysOld): Analysis
    {
        $a = Analysis::create([
            'user_id' => $owner->id,
            'pr_url' => 'https://github.com/laravel/framework/pull/1',
            'repo_owner' => 'laravel', 'repo_name' => 'framework', 'pr_number' => 1,
            'status' => 'completed',
        ]);

        $a->forceFill(['created_at' => now()->subDays($daysOld)])->save();

        return $a;
    }

    public function test_the_default_run_is_a_dry_run_and_deletes_nothing(): void
    {
        $this->analysis(User::factory()->create(), 120);

        $this->artisan('regradar:prune-analyses')->assertSuccessful();

        $this->assertSame(1, Analysis::count());
    }

    public function test_forced_run_deletes_only_expired_analyses_without_consent(): void
    {
        $unconsented = User::factory()->create();
        $consented = User::factory()->create(['retention_consent_at' => now()]);

        $expired = $this->analysis($unconsented, 91);
        $recent = $this->analysis($unconsented, 10);
        $kept = $this->analysis($consented, 400);

        $this->artisan('regradar:prune-analyses', ['--force' => true])->assertSuccessful();

        $this->assertModelMissing($expired);
        $this->assertModelExists($recent);
        $this->assertModelExists($kept);
    }

    public function test_an_analysis_exactly_inside_the_period_is_kept(): void
    {
        $a = $this->analysis(User::factory()->create(), 89);

        $this->artisan('regradar:prune-analyses', ['--force' => true]);

        $this->assertModelExists($a);
    }

    public function test_consent_is_recorded_and_can_be_withdrawn(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.retention'), ['retain' => '1']);
        $first = $user->fresh()->retention_consent_at;
        $this->assertNotNull($first);

        // Saving again keeps the original consent date rather than resetting it.
        $this->travel(5)->days();
        $this->actingAs($user)->patch(route('profile.retention'), ['retain' => '1']);
        $this->assertTrue($first->equalTo($user->fresh()->retention_consent_at));

        $this->actingAs($user)->patch(route('profile.retention'), ['retain' => '0']);
        $this->assertNull($user->fresh()->retention_consent_at);
    }
}
