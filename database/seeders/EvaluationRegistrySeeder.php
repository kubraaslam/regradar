<?php

namespace Database\Seeders;

use App\Models\FeatureRegistry;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Feature Registries for the three evaluation repositories (RO4, proposal 5.3).
 *
 * Each registry describes its repository's features as a QA engineer would
 * know them, constructed from the repository's documentation and structure
 * rather than from the evaluation pull requests. Building it from the pull
 * requests would make the evaluation circular.
 *
 * Code areas are deliberately not exhaustive. Validation Rules, for example,
 * does not list ValidationRuleParser: a QA engineer would not know that
 * internal class, and its absence is what tests whether the model can connect
 * a change to a feature without an exact name match.
 *
 * Each registry also contains decoy features that no evaluation pull request
 * touches. Without something available to wrongly flag, precision would be
 * trivially 1.00 and would measure nothing.
 *
 * Idempotent: entries are keyed on owner, repository and feature name, so
 * re-seeding updates them in place rather than duplicating them.
 */
class EvaluationRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'qa@regradar.com')->first()
            ?? User::where('role', User::ROLE_QA_ENGINEER)->orderBy('id')->first();

        if (!$owner) {
            $this->command?->warn('No QA engineer account exists. Run UserSeeder first.');

            return;
        }

        foreach ($this->registries() as $repository => $features) {
            foreach ($features as [$name, $codeAreas, $description]) {
                FeatureRegistry::updateOrCreate(
                    ['user_id' => $owner->id, 'repository' => $repository, 'feature_name' => $name],
                    ['code_areas' => $codeAreas, 'endpoints' => [], 'description' => $description]
                );
            }
        }
    }

    /**
     * @return array<string, array<int, array{0: string, 1: array<int, string>, 2: string}>>
     */
    protected function registries(): array
    {
        return [
            'laravel/framework' => [
                ['Validation Rules', ['Validator', 'ValidatesWhenResolved', 'FormRequest', 'Rules'],
                    'Rule parsing and evaluation for form and request validation, including conditional rules'],
                ['Route URL Generation', ['UrlGenerator', 'RouteUrlGenerator', 'Route'],
                    'Building URLs from named routes, including optional and scoped parameters'],
                ['Route Model Binding', ['ImplicitRouteBinding', 'SubstituteBindings'],
                    'Resolving route parameters into model instances before a controller runs'],
                ['Database Test Assertions', ['InteractsWithDatabase', 'assertDatabaseHas', 'assertModelExists'],
                    'Test helpers that assert records exist or are missing in the database'],
                ['Model Factories', ['Factory', 'HasFactory'],
                    'Generating model instances with fake data for tests and seeding'],
                ['Query Builder', ['Builder', 'Grammar', 'Processor'],
                    'Fluent construction and compilation of SQL queries'],
                ['Eloquent Relations', ['HasManyThrough', 'BelongsToMany', 'HasMany'],
                    'Relationship definitions and the queries used to load related models'],
                // Decoys
                ['Queue Worker', ['WorkCommand', 'Worker'],
                    'The long-running process that reserves and executes queued jobs'],
                ['Mail Sending', ['Mailer', 'Mailable', 'MailManager'],
                    'Composing and dispatching outbound email through configured transports'],
                ['Authentication Guards', ['SessionGuard', 'TokenGuard'],
                    'Determining the authenticated user for a request from session or token'],
            ],

            'rails/rails' => [
                ['Query Results', ['Result', 'column_types'],
                    'The result object returned by database queries and the type casting of its columns'],
                ['Attribute Access', ['attribute_methods', 'read_attribute', 'write_attribute'],
                    'Reading and writing model attributes, including generated accessor methods'],
                ['Application Info Routes', ['rails_info_controller', 'Rails::InfoController'],
                    'Development-only routes that display application routes and environment information'],
                // Decoys
                ['Associations', ['has_many', 'belongs_to', 'Associations'],
                    'Model relationship definitions and the loading of associated records'],
                ['Migrations', ['Migration', 'schema'],
                    'Versioned schema changes and the schema definition they produce'],
                ['Action Mailer', ['ActionMailer', 'deliver_now'],
                    'Composing and delivering email from the application'],
            ],

            'django/django' => [
                ['Text and HTML Filters', ['urlize', 'escape', 'strip_tags', 'utils/html'],
                    'Template filters and utilities that transform or sanitise text and HTML'],
                ['Template Tags', ['defaulttags', 'querystring', 'Library'],
                    'Built-in template tags, including query string construction'],
                ['Database Introspection', ['inspectdb', 'introspection'],
                    'Reading an existing database schema to generate models'],
                // Decoys
                ['Query Aggregation', ['aggregates', 'annotate'],
                    'Aggregate functions and annotations applied to querysets'],
                ['Form Validation', ['forms', 'clean', 'ValidationError'],
                    'Form field cleaning and validation error handling'],
                ['URL Routing', ['urls', 'resolvers', 'reverse'],
                    'Matching request paths to views and reversing named URLs'],
            ],
        ];
    }
}
