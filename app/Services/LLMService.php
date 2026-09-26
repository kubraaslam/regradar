<?php

namespace App\Services;

use GuzzleHttp\Client;
use InvalidArgumentException;
use RuntimeException;

class LLMService
{
    /**
     * Models evaluated by this project, one per provider.
     *
     * Pinned to explicit versions rather than moving aliases such as
     * gemini-flash-latest, so that evaluation runs remain reproducible.
     */
    public const MODEL_OPENAI = 'gpt-4o-mini';

    /**
     * Flash-Lite rather than full Flash. Measured on 2026-09-26, the larger
     * Gemini flash models returned HTTP 503 "experiencing high demand" on every
     * attempt (3.6 and 3.7 at 0/3, 3.5 at 1/3), while Flash-Lite served 3/3 at
     * around 4 seconds. It is also the closer tier match to GPT-4o-mini.
     * Re-verify availability before starting an evaluation run.
     */
    public const MODEL_GEMINI = 'gemini-3.1-flash-lite';

    public const MODEL_GROQ = 'qwen/qwen3.8-27b';

    public const MODELS = [
        self::MODEL_OPENAI,
        self::MODEL_GEMINI,
        self::MODEL_GROQ,
    ];

    public const STRATEGY_ZERO_SHOT = 'zero-shot';
    public const STRATEGY_FEW_SHOT = 'few-shot';
    public const STRATEGY_COT = 'chain-of-thought';

    public const STRATEGIES = [
        self::STRATEGY_ZERO_SHOT,
        self::STRATEGY_FEW_SHOT,
        self::STRATEGY_COT,
    ];

    /** Display names for the submission form and the risk report. */
    public const MODEL_LABELS = [
        self::MODEL_OPENAI => 'GPT-4o-mini',
        self::MODEL_GEMINI => 'Gemini 3.1 Flash Lite',
        self::MODEL_GROQ => 'Qwen 3.8 27B',
    ];

    public const STRATEGY_LABELS = [
        self::STRATEGY_ZERO_SHOT => 'Zero-shot',
        self::STRATEGY_FEW_SHOT => 'Few-shot',
        self::STRATEGY_COT => 'Chain-of-thought',
    ];

    /** Lowered across all providers to minimise variance between runs. */
    protected const TEMPERATURE = 0.1;

    /** Characters of diff summary sent to the model, identical for every model. */
    protected const DIFF_LIMIT = 3000;

    /**
     * Transient failures are common on hosted model APIs: providers return 429
     * when rate limited and 503 when a model is at capacity. Without retries an
     * evaluation run of 20 pull requests across nine combinations would abort
     * part way through, so each request is retried with exponential backoff.
     */
    protected const MAX_ATTEMPTS = 3;
    protected const RETRY_BASE_SECONDS = 1.5;

    protected Client $client;
    protected string $openaiKey;
    protected string $geminiKey;
    protected string $groqKey;

    /**
     * Cost and latency of the most recent analyse() call.
     *
     * Held on the instance rather than returned, so that the shape of the
     * analysis result stays unchanged for existing callers.
     */
    protected array $lastUsage = [
        'duration_ms' => null,
        'prompt_tokens' => null,
        'completion_tokens' => null,
    ];

    public function __construct()
    {
        $this->openaiKey = (string) config('services.openai.key');
        $this->geminiKey = (string) config('services.gemini.key');
        $this->groqKey = (string) config('services.groq.key');

        // Deliberately bare: each provider supplies its own URL and credentials
        // per request, so that one provider's key is never sent to another.
        $this->client = new Client(['timeout' => 60]);
    }

    // =====================================================================
    // Dispatcher
    // =====================================================================

    /**
     * Run one analysis for a given model and prompting strategy combination.
     */
    public function analyse(string $model, string $strategy, string $diffSummary, array $registry): array
    {
        if (!in_array($model, self::MODELS, true)) {
            throw new InvalidArgumentException("Unsupported model: {$model}");
        }

        if (!in_array($strategy, self::STRATEGIES, true)) {
            throw new InvalidArgumentException("Unsupported prompting strategy: {$strategy}");
        }

        $this->lastUsage = ['duration_ms' => null, 'prompt_tokens' => null, 'completion_tokens' => null];
        $startedAt = microtime(true);

        try {
            return match ($model) {
                self::MODEL_OPENAI => $this->analyseWithOpenAI($diffSummary, $registry, $strategy),
                self::MODEL_GEMINI => $this->analyseWithGemini($diffSummary, $registry, $strategy),
                self::MODEL_GROQ => $this->analyseWithGroq($diffSummary, $registry, $strategy),
            };
        } finally {
            // Recorded in a finally block so that a failed run still reports how
            // long it took, including any time spent on retries.
            $this->lastUsage['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
        }
    }

    /**
     * Latency and token counts for the most recent analyse() call.
     *
     * @return array{duration_ms: int|null, prompt_tokens: int|null, completion_tokens: int|null}
     */
    public function lastUsage(): array
    {
        return $this->lastUsage;
    }

    /**
     * Record token counts from a provider response.
     *
     * OpenAI and Groq report usage in the same shape; Gemini uses its own key
     * names, so both are normalised here.
     */
    protected function recordTokens(array $body): void
    {
        $this->lastUsage['prompt_tokens'] = $body['usage']['prompt_tokens']
            ?? $body['usageMetadata']['promptTokenCount']
            ?? null;

        $this->lastUsage['completion_tokens'] = $body['usage']['completion_tokens']
            ?? $body['usageMetadata']['candidatesTokenCount']
            ?? null;
    }

    /**
     * Backwards-compatible entry point for the original proof of concept.
     */
    public function analyseWithZeroShot(string $diffSummary, array $featureRegistry): array
    {
        return $this->analyse(self::MODEL_OPENAI, self::STRATEGY_ZERO_SHOT, $diffSummary, $featureRegistry);
    }

    // =====================================================================
    // Providers
    // =====================================================================

    public function analyseWithOpenAI(string $diffSummary, array $registry, string $strategy): array
    {
        $body = $this->send(
            'OpenAI',
            'https://api.openai.com/v1/chat/completions',
            ['Authorization' => 'Bearer ' . $this->openaiKey],
            [
                'model' => self::MODEL_OPENAI,
                'messages' => [
                    ['role' => 'user', 'content' => $this->buildPrompt($strategy, $diffSummary, $registry)],
                ],
                'temperature' => self::TEMPERATURE,
                'response_format' => ['type' => 'json_object'],
            ]
        );

        $this->recordTokens($body);

        return $this->parseJson($body['choices'][0]['message']['content'] ?? null);
    }

    public function analyseWithGemini(string $diffSummary, array $registry, string $strategy): array
    {
        $body = $this->send(
            'Gemini',
            'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL_GEMINI . ':generateContent',
            // Key travels as a header rather than a ?key= query string, so it is
            // never written into request logs or browser history.
            ['x-goog-api-key' => $this->geminiKey],
            [
                'contents' => [
                    ['parts' => [['text' => $this->buildPrompt($strategy, $diffSummary, $registry)]]],
                ],
                'generationConfig' => [
                    'temperature' => self::TEMPERATURE,
                    'responseMimeType' => 'application/json',
                ],
            ]
        );

        $this->recordTokens($body);

        return $this->parseJson($this->extractGeminiText($body));
    }

    public function analyseWithGroq(string $diffSummary, array $registry, string $strategy): array
    {
        // Groq exposes an OpenAI-compatible endpoint, so only the base URL,
        // credentials and model string differ from analyseWithOpenAI().
        $body = $this->send(
            'Groq',
            'https://api.groq.com/openai/v1/chat/completions',
            ['Authorization' => 'Bearer ' . $this->groqKey],
            [
                'model' => self::MODEL_GROQ,
                'messages' => [
                    ['role' => 'user', 'content' => $this->buildPrompt($strategy, $diffSummary, $registry)],
                ],
                'temperature' => self::TEMPERATURE,
                'response_format' => ['type' => 'json_object'],
            ]
        );

        $this->recordTokens($body);

        return $this->parseJson($body['choices'][0]['message']['content'] ?? null);
    }

    /**
     * Gemini 3.x models interleave reasoning parts with the answer, so the text
     * is not reliably at parts[0]. Take the first part that carries real text.
     */
    protected function extractGeminiText(array $body): ?string
    {
        foreach ($body['candidates'][0]['content']['parts'] ?? [] as $part) {
            if (isset($part['text']) && trim($part['text']) !== '') {
                return $part['text'];
            }
        }

        return null;
    }

    /**
     * POST a payload to a provider, retrying transient failures.
     *
     * Retries on 429 (rate limited) and any 5xx (provider side), which includes
     * the 503 "model is currently experiencing high demand" that Gemini returns
     * under load. Client errors such as 400 and 401 are not retried, because
     * repeating a malformed or unauthorised request cannot succeed.
     */
    protected function send(string $provider, string $url, array $headers, array $payload): array
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            $response = $this->client->post($url, [
                'headers' => $headers + ['Content-Type' => 'application/json'],
                'json' => $payload,
                'http_errors' => false,
            ]);

            $status = $response->getStatusCode();
            $raw = (string) $response->getBody();

            if ($status === 200) {
                return json_decode($raw, true) ?? [];
            }

            $isTransient = $status === 429 || $status >= 500;

            if (!$isTransient || $attempt >= self::MAX_ATTEMPTS) {
                $decoded = json_decode($raw, true);
                $message = $decoded['error']['message'] ?? substr($raw, 0, 200);

                throw new RuntimeException(
                    "{$provider} request failed with HTTP {$status} after {$attempt} attempt(s): {$message}"
                );
            }

            // 1.5s, then 3s.
            usleep((int) (self::RETRY_BASE_SECONDS * (2 ** ($attempt - 1)) * 1_000_000));
        }
    }

    // =====================================================================
    // Prompt construction
    // =====================================================================

    protected function buildPrompt(string $strategy, string $diffSummary, array $registry): string
    {
        return match ($strategy) {
            self::STRATEGY_ZERO_SHOT => $this->buildZeroShotPrompt($diffSummary, $registry),
            self::STRATEGY_FEW_SHOT => $this->buildFewShotPrompt($diffSummary, $registry),
            self::STRATEGY_COT => $this->buildCoTPrompt($diffSummary, $registry),
            default => throw new InvalidArgumentException("Unsupported prompting strategy: {$strategy}"),
        };
    }

    /**
     * Task instruction only, with no examples and no reasoning guidance.
     * This is the baseline strategy carried over from the proof of concept.
     */
    public function buildZeroShotPrompt(string $diffSummary, array $registry): string
    {
        return $this->preamble()
            . $this->inputs($diffSummary, $registry)
            . <<<PROMPT

            Analyse each feature in the registry and determine if the changed code areas or endpoints overlap with the feature's dependencies.

            {$this->outputContract()}
            PROMPT;
    }

    /**
     * Two worked examples prepended to the task.
     *
     * The examples are drawn from a generic e-commerce application rather than
     * from any framework repository, so that they cannot leak answers into the
     * evaluation dataset, which is curated from Laravel, Django and Rails.
     *
     * Between them the pair demonstrates every behaviour the task requires:
     * a High rating from a direct code area change, a Medium rating from an
     * indirect dependency, a Low rating from a peripheral overlap, omission of
     * an unaffected feature, and selecting a subset of a multi-entry registry.
     */
    public function buildFewShotPrompt(string $diffSummary, array $registry): string
    {
        $registryOne = json_encode([
            ['feature' => 'Checkout Payment', 'code_areas' => ['PaymentController', 'StripeGateway'], 'endpoints' => ['/api/checkout/pay']],
            ['feature' => 'Invoice Export', 'code_areas' => ['InvoiceExporter', 'CurrencyFormatter'], 'endpoints' => ['/api/invoices/export']],
            ['feature' => 'Product Search', 'code_areas' => ['SearchController', 'SearchIndexer'], 'endpoints' => ['/api/search']],
        ], JSON_UNESCAPED_SLASHES);

        $outputOne = json_encode([
            'risks' => [
                [
                    'feature' => 'Checkout Payment',
                    'risk_level' => 'High',
                    'reason' => 'PaymentController is a code area for this feature and its capture and refund methods were modified directly.',
                ],
                [
                    'feature' => 'Invoice Export',
                    'risk_level' => 'Low',
                    'reason' => 'CurrencyFormatter is listed under this feature, but it only formats values for display, so a regression is possible although unlikely.',
                ],
            ],
            'summary' => 'This pull request modifies the payment controller and a shared currency formatting helper. Checkout Payment is directly affected because both the capture and refund methods it depends on were rewritten, so any fault here would stop customers paying. Invoice Export is touched only through the formatter, which affects how values are displayed rather than how they are calculated. A full regression pass over the payment flow is recommended before merging, with a lighter check on invoice output.',
            'testing_focus' => [
                'Complete a card payment end to end and confirm the charge is captured',
                'Issue a partial and a full refund and confirm both amounts are correct',
                'Export an invoice and confirm currency values display with the correct symbol and decimal places',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $registryTwo = json_encode([
            ['feature' => 'Order Checkout', 'code_areas' => ['OrderService', 'CheckoutController'], 'endpoints' => ['/api/orders']],
            ['feature' => 'Email Notifications', 'code_areas' => ['NotificationService', 'MailTemplate'], 'endpoints' => ['/api/notifications']],
        ], JSON_UNESCAPED_SLASHES);

        $outputTwo = json_encode([
            'risks' => [
                [
                    'feature' => 'Order Checkout',
                    'risk_level' => 'High',
                    'reason' => 'OrderService is a code area for this feature and both the place and cancel methods were modified.',
                ],
                [
                    'feature' => 'Email Notifications',
                    'risk_level' => 'Medium',
                    'reason' => 'NotificationService was not changed, but order confirmation emails are dispatched from OrderService, so this feature depends indirectly on the modified code.',
                ],
            ],
            'summary' => 'This pull request changes how orders are placed and cancelled inside the order service. Order Checkout is directly affected, since that service is one of its own code areas and both of its main methods were modified. Email Notifications is affected indirectly, because order confirmation messages are dispatched from the same service even though the notification code itself was untouched. Regression testing should cover the order lifecycle first, then confirm that the messages triggered by it still send.',
            'testing_focus' => [
                'Place a new order and confirm it reaches the correct status',
                'Cancel an existing order and confirm stock and payment are reversed',
                'Confirm the order confirmation and cancellation emails are still sent and contain the right details',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $this->preamble()
            . <<<PROMPT

            Here are two worked examples of the task.

            EXAMPLE 1
            Diff summary:
            Changed files: app/Http/Controllers/PaymentController.php, app/Support/CurrencyFormatter.php
            Modified functions: capture, refund, format
            Feature registry:
            {$registryOne}
            Expected output:
            {$outputOne}
            Product Search is omitted because none of its code areas or endpoints appear in the diff.

            EXAMPLE 2
            Diff summary:
            Changed files: app/Services/OrderService.php
            Modified functions: place, cancel
            Feature registry:
            {$registryTwo}
            Expected output:
            {$outputTwo}

            Now perform the same task on the following real input.

            PROMPT
            . $this->inputs($diffSummary, $registry)
            . $this->outputContract();
    }

    /**
     * Explicit step-by-step reasoning instruction. The final step suppresses the
     * intermediate reasoning, which small open-weight models otherwise emit
     * alongside the JSON and break parsing.
     */
    public function buildCoTPrompt(string $diffSummary, array $registry): string
    {
        return $this->preamble()
            . $this->inputs($diffSummary, $registry)
            . <<<PROMPT

            Work through the following steps in order before answering.

            Step 1: List the code areas that changed, based on the changed files and modified functions in the diff summary.
            Step 2: For each feature in the registry, check whether any of its code areas or endpoints appear in the changed areas from Step 1.
            Step 3: Assign a risk level to each affected feature. Assign High where a feature's own code area changed directly, Medium where the feature depends indirectly on a changed area, and Low where the overlap is minor or unlikely to cause a regression.
            Step 4: Return the final answer.

            Carry out steps 1 to 3 internally. Do not include your reasoning steps in the output.

            {$this->outputContract()}
            PROMPT;
    }

    /** Role statement shared by all three strategies. */
    protected function preamble(): string
    {
        return <<<PROMPT
        You are a QA assistant specialised in regression risk analysis.

        Given a pull request diff summary and a feature registry, identify which features are at risk of regression due to the code changes.

        PROMPT;
    }

    /** The two inputs, formatted identically for all three strategies. */
    protected function inputs(string $diffSummary, array $registry): string
    {
        $diffSummary = substr($diffSummary, 0, self::DIFF_LIMIT);
        $registryJson = json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT

        PULL REQUEST DIFF SUMMARY:
        {$diffSummary}

        FEATURE REGISTRY:
        {$registryJson}

        PROMPT;
    }

    /**
     * Output contract shared by all three strategies. Keeping this identical is
     * what makes the strategies comparable during evaluation.
     */
    protected function outputContract(): string
    {
        return <<<PROMPT

        Return ONLY a valid JSON object in this exact format, with no other text:
        {
          "risks": [
            {
              "feature": "Feature name here",
              "risk_level": "High",
              "reason": "Plain language explanation of why this feature is at risk"
            }
          ],
          "summary": "Three to four sentences written for a QA engineer. State what the pull request changed, which features are affected and how they connect to those changes, and what the overall recommendation is before merging.",
          "testing_focus": [
            "A specific thing to regression test, phrased as an action",
            "Another specific thing to regression test"
          ]
        }

        Risk levels: High (direct overlap with changed code), Medium (indirect dependency), Low (minor or unlikely impact).
        Only include features that have some risk. Omit features with no risk.

        Write summary as continuous prose, not a list, and do not repeat the reason fields verbatim.
        Give between two and four testing_focus entries, each naming something concrete a tester can carry out.
        PROMPT;
    }

    // =====================================================================
    // Response handling
    // =====================================================================

    /**
     * Decode a model response into the expected shape.
     *
     * Only OpenAI and Groq honour a JSON response format parameter, and even
     * then smaller models sometimes wrap the object in markdown fences, so every
     * provider is routed through here rather than calling json_decode directly.
     */
    protected function parseJson(?string $content): array
    {
        $content = trim((string) $content);

        // Strip ```json ... ``` fences if the model added them.
        $content = preg_replace('/^```[a-zA-Z]*\s*|\s*```$/', '', $content);

        // Fall back to the outermost JSON object if the model added prose around it.
        if (!str_starts_with($content, '{')) {
            $start = strpos($content, '{');
            $end = strrpos($content, '}');

            if ($start !== false && $end !== false && $end > $start) {
                $content = substr($content, $start, $end - $start + 1);
            }
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded) || !isset($decoded['risks']) || !is_array($decoded['risks'])) {
            return [
                'risks' => [],
                'summary' => 'The model did not return a valid analysis for this pull request.',
                'testing_focus' => [],
            ];
        }

        // testing_focus is advisory, so a model that omits it still yields a
        // usable report rather than failing the whole analysis.
        $testingFocus = $decoded['testing_focus'] ?? [];

        return [
            'risks' => $decoded['risks'],
            'summary' => $decoded['summary'] ?? '',
            'testing_focus' => is_array($testingFocus) ? array_values(array_filter($testingFocus, 'is_string')) : [],
        ];
    }
}
