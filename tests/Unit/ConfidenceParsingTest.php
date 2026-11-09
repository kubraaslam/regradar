<?php

namespace Tests\Unit;

use App\Services\LLMService;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * SRS FR13: confidence is normalised to a whole number from 0 to 100, whatever
 * scale or type the model actually answers with.
 */
class ConfidenceParsingTest extends TestCase
{
    private function parse(string $json): array
    {
        $method = new ReflectionMethod(LLMService::class, 'parseJson');
        $method->setAccessible(true);

        return $method->invoke(new LLMService(), $json);
    }

    private function confidenceOf(mixed $value): ?int
    {
        $json = json_encode([
            'risks' => [['feature' => 'F', 'risk_level' => 'High', 'confidence' => $value, 'reason' => 'r']],
            'summary' => 's',
        ]);

        return $this->parse($json)['risks'][0]['confidence'];
    }

    public static function scales(): array
    {
        return [
            'whole number as requested' => [85, 85],
            'fraction on a 0 to 1 scale' => [0.85, 85],
            'fraction of exactly 1' => [1, 100],
            'numeric string' => ['72', 72],
            'percentage string' => ['72%', 72],
            'above range is clamped' => [140, 100],
            'below range is clamped' => [-5, 0],
            'zero stays zero' => [0, 0],
            'non-numeric becomes null' => ['high', null],
            'null stays null' => [null, null],
        ];
    }

    #[DataProvider('scales')]
    public function test_confidence_is_normalised(mixed $given, ?int $expected): void
    {
        $this->assertSame($expected, $this->confidenceOf($given));
    }

    public function test_a_missing_confidence_does_not_fail_the_analysis(): void
    {
        $result = $this->parse('{"risks":[{"feature":"F","risk_level":"High","reason":"r"}],"summary":"s"}');

        $this->assertCount(1, $result['risks']);
        $this->assertNull($result['risks'][0]['confidence']);
    }

    public function test_every_few_shot_example_demonstrates_a_confidence(): void
    {
        $prompt = (new LLMService())->buildFewShotPrompt('Production files changed: (none)', []);

        // Only the worked examples, not the output contract appended after them.
        $examples = strstr($prompt, 'Now perform the same task', true);

        // Five worked risks across three examples; each must carry a confidence,
        // or the examples would contradict the output contract.
        $this->assertSame(5, substr_count($examples, '"risk_level":'));
        $this->assertSame(5, substr_count($examples, '"confidence":'));
    }
}
