<?php

namespace App\Services;

use GuzzleHttp\Client;

class LLMService
{
    protected Client $client;
    protected string $apiKey;
    protected string $model = 'gpt-4o-mini';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 60,
        ]);
    }

    public function analyseWithZeroShot(string $diffSummary, array $featureRegistry): array
    {
        $diffSummary = substr($diffSummary, 0, 3000);
        $registryJson = json_encode($featureRegistry, JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
You are a QA assistant specialised in regression risk analysis.

Given the following pull request diff summary and feature registry, identify which features are at risk of regression 
due to the code changes.

PULL REQUEST DIFF SUMMARY:
{$diffSummary}

FEATURE REGISTRY:
{$registryJson}

Analyse each feature in the registry and determine if the changed code areas or endpoints overlap with the feature's dependencies.

Return ONLY a valid JSON object in this exact format, no other text:
{
  "risks": [
    {
      "feature": "Feature name here",
      "risk_level": "High",
      "reason": "Plain language explanation of why this feature is at risk"
    }
  ],
  "summary": "One sentence overall summary of the regression risk"
}

Risk levels: High (direct overlap with changed code), Medium (indirect dependency), Low (minor or unlikely impact). 
Only include features that have some risk. Omit features with no risk.
PROMPT;

        $response = $this->client->post('chat/completions', [
            'json' => [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true);
        $content = $body['choices'][0]['message']['content'] ?? '{}';

        return json_decode($content, true) ?? ['risks' => [], 'summary' => 'Analysis failed.'];
    }
}