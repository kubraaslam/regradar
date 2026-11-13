<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\FeatureRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SRS FR2: three roles, with Developer read-only. Also covers the ownership
 * rules that stop one QA engineer reading or changing another's records.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function analysisFor(User $owner): Analysis
    {
        return Analysis::create([
            'user_id' => $owner->id,
            'pr_url' => 'https://github.com/laravel/framework/pull/41969',
            'repo_owner' => 'laravel',
            'repo_name' => 'framework',
            'pr_number' => 41969,
            'status' => 'completed',
            'results' => ['risks' => [], 'summary' => '', 'total_high' => 0, 'total_medium' => 0, 'total_low' => 0],
        ]);
    }

    private function featureFor(User $owner): FeatureRegistry
    {
        return FeatureRegistry::create([
            'user_id' => $owner->id,
            'feature_name' => 'Validation Rules',
            'code_areas' => ['Validator', 'Rules'],
            'endpoints' => [],
        ]);
    }

    // ---------------------------------------------------------------------
    // Developer: read-only
    // ---------------------------------------------------------------------

    public function test_developer_can_read_history_reports_and_registry(): void
    {
        $qa = $this->user(User::ROLE_QA_ENGINEER);
        $analysis = $this->analysisFor($qa);
        $this->featureFor($qa);

        $developer = $this->user(User::ROLE_DEVELOPER);

        $this->actingAs($developer)->get(route('analysis.index'))->assertOk();
        $this->actingAs($developer)->get(route('analysis.show', $analysis))->assertOk();
        $this->actingAs($developer)->get(route('feature-registry.index'))->assertOk();
    }

    public function test_developer_sees_the_teams_analyses_not_an_empty_list(): void
    {
        $qa = $this->user(User::ROLE_QA_ENGINEER);
        $this->analysisFor($qa);

        $this->actingAs($this->user(User::ROLE_DEVELOPER))
            ->get(route('analysis.index'))
            ->assertSee('laravel/framework/pull/41969')
            ->assertSee('by ' . $qa->name);
    }

    public function test_developer_cannot_run_an_analysis(): void
    {
        $developer = $this->user(User::ROLE_DEVELOPER);

        $this->actingAs($developer)->get(route('analysis.create'))->assertForbidden();
        $this->actingAs($developer)->post(route('analysis.store'), [
            'pr_url' => 'https://github.com/laravel/framework/pull/41969',
            'llm_model' => 'gpt-6-luna',
            'prompting_strategy' => 'zero-shot',
        ])->assertForbidden();

        $this->assertSame(0, Analysis::count());
    }

    public function test_developer_cannot_change_the_registry(): void
    {
        $feature = $this->featureFor($this->user(User::ROLE_QA_ENGINEER));
        $developer = $this->user(User::ROLE_DEVELOPER);

        $this->actingAs($developer)->get(route('feature-registry.create'))->assertForbidden();
        $this->actingAs($developer)->get(route('feature-registry.edit', $feature))->assertForbidden();
        $this->actingAs($developer)->post(route('feature-registry.store'), [
            'feature_name' => 'Injected',
            'code_areas' => 'Anything',
        ])->assertForbidden();
        $this->actingAs($developer)->delete(route('feature-registry.destroy', $feature))->assertForbidden();

        $this->assertDatabaseHas('feature_registries', ['id' => $feature->id]);
        $this->assertDatabaseMissing('feature_registries', ['feature_name' => 'Injected']);
    }

    public function test_developer_is_not_offered_write_actions(): void
    {
        $this->featureFor($this->user(User::ROLE_QA_ENGINEER));

        $this->actingAs($this->user(User::ROLE_DEVELOPER))
            ->get(route('feature-registry.index'))
            ->assertDontSee('Add feature')
            ->assertDontSee(route('feature-registry.create'))
            ->assertSee('Read-only access');
    }

    // ---------------------------------------------------------------------
    // QA engineers: their own records only
    // ---------------------------------------------------------------------

    public function test_qa_engineer_cannot_open_another_engineers_analysis(): void
    {
        $other = $this->analysisFor($this->user(User::ROLE_QA_ENGINEER));

        $this->actingAs($this->user(User::ROLE_QA_ENGINEER))
            ->get(route('analysis.show', $other))
            ->assertForbidden();
    }

    public function test_qa_engineer_history_lists_only_their_own_analyses(): void
    {
        $this->analysisFor($this->user(User::ROLE_QA_ENGINEER));

        $this->actingAs($this->user(User::ROLE_QA_ENGINEER))
            ->get(route('analysis.index'))
            ->assertDontSee('laravel/framework/pull/41969');
    }

    public function test_qa_engineer_cannot_edit_or_delete_another_engineers_feature(): void
    {
        $feature = $this->featureFor($this->user(User::ROLE_QA_ENGINEER));
        $intruder = $this->user(User::ROLE_QA_ENGINEER);

        $this->actingAs($intruder)->get(route('feature-registry.edit', $feature))->assertForbidden();
        $this->actingAs($intruder)->put(route('feature-registry.update', $feature), [
            'feature_name' => 'Renamed',
            'code_areas' => 'Validator',
        ])->assertForbidden();
        $this->actingAs($intruder)->delete(route('feature-registry.destroy', $feature))->assertForbidden();

        $this->assertDatabaseHas('feature_registries', ['id' => $feature->id, 'feature_name' => 'Validation Rules']);
    }

    // ---------------------------------------------------------------------
    // Admin: everything
    // ---------------------------------------------------------------------

    public function test_admin_can_view_and_change_any_record(): void
    {
        $qa = $this->user(User::ROLE_QA_ENGINEER);
        $analysis = $this->analysisFor($qa);
        $feature = $this->featureFor($qa);
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->get(route('analysis.show', $analysis))->assertOk();
        $this->actingAs($admin)->put(route('feature-registry.update', $feature), [
            'feature_name' => 'Validation Rules',
            'code_areas' => 'Validator, Rules, FormRequest',
        ])->assertRedirect(route('feature-registry.index'));

        $this->assertSame(['Validator', 'Rules', 'FormRequest'], $feature->fresh()->code_areas);
    }
}
