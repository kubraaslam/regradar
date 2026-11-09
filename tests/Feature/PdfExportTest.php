<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SRS FR11: the risk report can be exported as a PDF document.
 */
class PdfExportTest extends TestCase
{
    use RefreshDatabase;

    private function analysisFor(User $owner, string $status = 'completed'): Analysis
    {
        return Analysis::create([
            'user_id' => $owner->id,
            'pr_url' => 'https://github.com/laravel/framework/pull/55213',
            'repo_owner' => 'laravel',
            'repo_name' => 'framework',
            'pr_number' => 55213,
            'status' => $status,
            'llm_model' => 'gpt-4o-mini',
            'prompting_strategy' => 'few-shot',
            'results' => $status === 'completed' ? [
                'risks' => [[
                    'feature' => 'Route URL Generation', 'risk_level' => 'High', 'confidence' => 92,
                    'reason' => 'RouteUrlGenerator.php changed directly.',
                ]],
                'summary' => 'Changes URL generation.',
                'testing_focus' => ['Generate URLs for named routes'],
                'total_high' => 1, 'total_medium' => 0, 'total_low' => 0,
                'changed_files' => ['src/Illuminate/Routing/RouteUrlGenerator.php'],
            ] : null,
        ]);
    }

    public function test_owner_downloads_the_report_as_a_pdf(): void
    {
        $qa = User::factory()->create(['role' => User::ROLE_QA_ENGINEER]);
        $analysis = $this->analysisFor($qa);

        $response = $this->actingAs($qa)->get(route('analysis.pdf', $analysis));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'regradar-laravel-framework-pr-55213.pdf',
            $response->headers->get('content-disposition')
        );
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_developer_can_export_a_team_report(): void
    {
        $analysis = $this->analysisFor(User::factory()->create(['role' => User::ROLE_QA_ENGINEER]));

        $this->actingAs(User::factory()->create(['role' => User::ROLE_DEVELOPER]))
            ->get(route('analysis.pdf', $analysis))
            ->assertOk();
    }

    public function test_another_qa_engineer_cannot_export_someone_elses_report(): void
    {
        $analysis = $this->analysisFor(User::factory()->create(['role' => User::ROLE_QA_ENGINEER]));

        $this->actingAs(User::factory()->create(['role' => User::ROLE_QA_ENGINEER]))
            ->get(route('analysis.pdf', $analysis))
            ->assertForbidden();
    }

    public function test_filename_falls_back_to_the_pull_request_url(): void
    {
        $analysis = $this->analysisFor(User::factory()->create());
        $analysis->forceFill(['repo_owner' => '', 'repo_name' => '', 'pr_number' => 0]);

        $this->assertSame('regradar-laravel-framework-pr-55213.pdf', $analysis->reportFilename());

        $analysis->pr_url = 'not a github url';
        $this->assertSame('regradar-analysis-' . $analysis->id . '.pdf', $analysis->reportFilename());
    }

    public function test_a_failed_analysis_cannot_be_exported(): void
    {
        $qa = User::factory()->create(['role' => User::ROLE_QA_ENGINEER]);

        $this->actingAs($qa)
            ->get(route('analysis.pdf', $this->analysisFor($qa, 'failed')))
            ->assertNotFound();
    }
}
