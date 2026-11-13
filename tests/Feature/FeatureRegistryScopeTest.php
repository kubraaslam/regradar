<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\FeatureRegistry;
use App\Models\User;
use App\Services\FeatureRegistryService;
use App\Services\GitHubService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Repository scoping of the Feature Registry, and the form rules documented in
 * docs/feature-registry.schema.json (NFR6).
 */
class FeatureRegistryScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $qa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->qa = User::factory()->create(['role' => User::ROLE_QA_ENGINEER]);
    }

    private function feature(string $name, ?string $repository): void
    {
        FeatureRegistry::create([
            'user_id' => $this->qa->id,
            'repository' => $repository,
            'feature_name' => $name,
            'code_areas' => ['X'],
            'endpoints' => [],
        ]);
    }

    private function names(array $registry): array
    {
        return array_column($registry, 'feature');
    }

    public function test_inclusive_scope_returns_matching_and_unscoped_entries(): void
    {
        $this->feature('Validation Rules', 'laravel/framework');
        $this->feature('Query Results', 'rails/rails');
        $this->feature('User Authentication', null);

        $registry = app(FeatureRegistryService::class)->getFormattedForLLM($this->qa->id, 'laravel/framework');

        $this->assertSame(['User Authentication', 'Validation Rules'], $this->names($registry));
    }

    public function test_strict_scope_used_by_the_evaluation_excludes_unscoped_entries(): void
    {
        $this->feature('Validation Rules', 'laravel/framework');
        $this->feature('User Authentication', null);

        $registry = app(FeatureRegistryService::class)->getFormattedForLLM($this->qa->id, 'laravel/framework', strict: true);

        $this->assertSame(['Validation Rules'], $this->names($registry));
    }

    public function test_repository_matching_is_case_insensitive(): void
    {
        $this->feature('Validation Rules', 'laravel/framework');

        $registry = app(FeatureRegistryService::class)->getFormattedForLLM($this->qa->id, 'Laravel/Framework', strict: true);

        $this->assertCount(1, $registry);
    }

    public function test_registry_order_is_deterministic(): void
    {
        $this->feature('Zebra', null);
        $this->feature('Alpha', null);
        $this->feature('Mango', null);

        $registry = app(FeatureRegistryService::class)->getFormattedForLLM($this->qa->id);

        $this->assertSame(['Alpha', 'Mango', 'Zebra'], $this->names($registry));
    }

    public function test_pull_request_url_is_parsed(): void
    {
        $this->assertSame([
            'owner' => 'laravel',
            'repo' => 'framework',
            'repository' => 'laravel/framework',
            'number' => 41969,
        ], GitHubService::parsePullRequestUrl('https://github.com/laravel/framework/pull/41969'));
    }

    public function test_a_url_that_is_not_a_pull_request_is_rejected_without_creating_an_analysis(): void
    {
        $this->feature('Validation Rules', null);

        $this->actingAs($this->qa)
            ->from(route('analysis.create'))
            ->post(route('analysis.store'), [
                'pr_url' => 'https://github.com/laravel/framework/issues/41969',
                'llm_model' => 'gpt-6-luna',
                'prompting_strategy' => 'zero-shot',
            ])
            ->assertRedirect(route('analysis.create'))
            ->assertSessionHasErrors('pr_url');

        $this->assertSame(0, Analysis::count());
    }

    public function test_an_empty_registry_for_the_repository_does_not_leave_a_pending_analysis(): void
    {
        $this->feature('Query Results', 'rails/rails');

        $this->actingAs($this->qa)
            ->post(route('analysis.store'), [
                'pr_url' => 'https://github.com/laravel/framework/pull/41969',
                'llm_model' => 'gpt-6-luna',
                'prompting_strategy' => 'zero-shot',
            ])
            ->assertRedirect(route('feature-registry.create'));

        $this->assertSame(0, Analysis::count());
    }

    public function test_repository_is_stored_lower_case(): void
    {
        $this->actingAs($this->qa)->post(route('feature-registry.store'), [
            'repository' => 'Laravel/Framework',
            'feature_name' => 'Validation Rules',
            'code_areas' => 'Validator',
        ])->assertRedirect(route('feature-registry.index'));

        $this->assertDatabaseHas('feature_registries', ['repository' => 'laravel/framework']);
    }

    public function test_repository_must_be_owner_slash_name(): void
    {
        $this->actingAs($this->qa)->post(route('feature-registry.store'), [
            'repository' => 'https://github.com/laravel/framework',
            'feature_name' => 'Validation Rules',
            'code_areas' => 'Validator',
        ])->assertSessionHasErrors('repository');
    }

    public function test_code_areas_that_split_to_nothing_are_rejected(): void
    {
        $this->actingAs($this->qa)->post(route('feature-registry.store'), [
            'feature_name' => 'Validation Rules',
            'code_areas' => ' , , ',
        ])->assertSessionHasErrors('code_areas');

        $this->assertSame(0, FeatureRegistry::count());
    }

    public function test_trailing_commas_do_not_store_empty_code_areas(): void
    {
        $this->actingAs($this->qa)->post(route('feature-registry.store'), [
            'feature_name' => 'Validation Rules',
            'code_areas' => 'Validator, Rules,',
            'endpoints' => '/api/login, ,',
        ]);

        $feature = FeatureRegistry::first();

        $this->assertSame(['Validator', 'Rules'], $feature->code_areas);
        $this->assertSame(['/api/login'], $feature->endpoints);
    }
}
