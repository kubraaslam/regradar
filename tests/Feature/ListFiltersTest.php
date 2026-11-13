<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\FeatureRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filters on the History page and search on the Feature Registry page.
 */
class ListFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function analysis(User $owner, string $repo, int $pr, array $attrs = []): Analysis
    {
        [$o, $n] = explode('/', $repo);

        return Analysis::create($attrs + [
            'user_id' => $owner->id,
            'pr_url' => "https://github.com/{$repo}/pull/{$pr}",
            'repo_owner' => $o, 'repo_name' => $n, 'pr_number' => $pr,
            'status' => 'completed',
            'llm_model' => 'gpt-4o-mini',
            'prompting_strategy' => 'few-shot',
            'results' => ['total_high' => 1, 'total_medium' => 0, 'total_low' => 0, 'risks' => []],
        ]);
    }

    private function feature(User $owner, string $name, ?string $repo = null, array $areas = []): FeatureRegistry
    {
        return FeatureRegistry::create([
            'user_id' => $owner->id, 'feature_name' => $name, 'repository' => $repo,
            'code_areas' => $areas, 'endpoints' => [], 'description' => null,
        ]);
    }

    public function test_history_filters_by_repository_risk_model_and_status(): void
    {
        $qa = User::factory()->create(['role' => User::ROLE_QA_ENGINEER]);

        $this->analysis($qa, 'laravel/framework', 1001);
        $this->analysis($qa, 'rails/rails', 2002, [
            'llm_model' => 'gemini-3.1-flash-lite',
            'results' => ['total_high' => 0, 'total_medium' => 0, 'total_low' => 2, 'risks' => []],
        ]);
        $this->analysis($qa, 'django/django', 3003, ['status' => 'failed', 'results' => null]);

        $get = fn(array $q) => $this->actingAs($qa)->get(route('analysis.index', $q))->assertOk();

        $get(['repository' => 'rails/rails'])->assertSee('rails/rails/pull/2002')->assertDontSee('laravel/framework/pull/1001');
        $get(['risk' => 'High'])->assertSee('pull/1001')->assertDontSee('pull/2002')->assertDontSee('pull/3003');
        $get(['risk' => 'Low'])->assertSee('pull/2002')->assertDontSee('pull/1001');
        $get(['model' => 'gemini-3.1-flash-lite'])->assertSee('pull/2002')->assertDontSee('pull/1001');
        $get(['status' => 'failed'])->assertSee('pull/3003')->assertDontSee('pull/1001');
        $get(['risk' => 'High', 'repository' => 'rails/rails'])->assertSee('No reports match these filters');
    }

    public function test_history_repository_filter_covers_analyses_with_only_a_pr_url(): void
    {
        $qa = User::factory()->create(['role' => User::ROLE_QA_ENGINEER]);
        $this->analysis($qa, 'laravel/framework', 1001, ['repo_owner' => '', 'repo_name' => '', 'pr_number' => 0]);

        $this->actingAs($qa)->get(route('analysis.index', ['repository' => 'laravel/framework']))
            ->assertSee('pull/1001');
    }

    public function test_history_owner_filter_is_ignored_for_qa_engineers(): void
    {
        $qa = User::factory()->create(['role' => User::ROLE_QA_ENGINEER]);
        $other = User::factory()->create(['role' => User::ROLE_QA_ENGINEER]);
        $this->analysis($qa, 'laravel/framework', 1001);
        $this->analysis($other, 'laravel/framework', 1002);

        // A QA engineer sees only their own analyses, whatever the query says.
        $this->actingAs($qa)->get(route('analysis.index', ['owner' => $other->id]))
            ->assertSee('pull/1001')->assertDontSee('pull/1002');

        // An admin can narrow the team view to one person.
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('analysis.index', ['owner' => $other->id]))
            ->assertSee('pull/1002')->assertDontSee('pull/1001');
    }

    public function test_registry_search_matches_name_and_code_areas(): void
    {
        $qa = User::factory()->create(['role' => User::ROLE_QA_ENGINEER]);
        $this->feature($qa, 'Route URL Generation', 'laravel/framework', ['src/Illuminate/Routing/UrlGenerator.php']);
        $this->feature($qa, 'Validation Rules', 'laravel/framework', ['src/Illuminate/Validation']);

        $search = fn(string $q) => $this->actingAs($qa)->get(route('feature-registry.index', ['q' => $q]))->assertOk();

        $search('validation')->assertSee('Validation Rules')->assertDontSee('Route URL Generation');
        // A path with slashes must match, even though JSON storage escapes them.
        $search('Routing/UrlGenerator')->assertSee('Route URL Generation')->assertDontSee('Validation Rules');
        $search('nothing like this')->assertSee('No features match');
    }

    public function test_registry_filters_by_repository_including_unscoped(): void
    {
        $qa = User::factory()->create(['role' => User::ROLE_QA_ENGINEER]);
        $this->feature($qa, 'Query Results', 'rails/rails');
        $this->feature($qa, 'Checkout Flow');

        $this->actingAs($qa)->get(route('feature-registry.index', ['repository' => 'rails/rails']))
            ->assertSee('Query Results')->assertDontSee('Checkout Flow');

        $this->actingAs($qa)->get(route('feature-registry.index', ['repository' => '__all']))
            ->assertSee('Checkout Flow')->assertDontSee('Query Results');
    }
}
