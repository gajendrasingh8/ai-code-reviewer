<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $apiUrl;

    // Max attempts on 503 / transient errors
    private int $maxRetries = 3;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1/models/gemini-3.8-flash:generateContent';
    }

    /**
     * Analyze code using Google Gemini AI.
     * Retries up to $maxRetries times on 503 / network errors.
     */
    public function analyze(string $code, string $language): array
    {
        $prompt  = $this->buildPrompt($code, $language);
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                $response = Http::timeout(45)
                    ->withOptions([
                        'verify' => 'C:/wamp64/bin/php/php8.3.14/extras/ssl/cacert.pem',
                    ])
                    ->post("{$this->apiUrl}?key={$this->apiKey}", [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature'     => 0.2,   // lower = more deterministic JSON
                            'maxOutputTokens' => 2048,  // increased to prevent truncation
                            'responseMimeType' => 'application/json', // force JSON mode
                        ],
                    ]);

                // Retry on 503 (Gemini overloaded) or 429 (rate limit)
                if ($response->status() === 503 || $response->status() === 429) {
                    Log::warning("Gemini {$response->status()} on attempt {$attempt}, retrying...");
                    sleep($attempt); // wait 1s, 2s, 3s between retries
                    continue;
                }

                if ($response->failed()) {
                    Log::error('Gemini API error', [
                        'status' => $response->status(),
                        'body'   => $response->body(),
                    ]);
                    throw new \Exception('Gemini API returned status ' . $response->status());
                }

                return $this->parseResponse($response->json());

            } catch (\Exception $e) {
                $lastException = $e;

                // Only retry on transient/network errors, not logic errors
                $isTransient = str_contains($e->getMessage(), 'cURL')
                            || str_contains($e->getMessage(), '503')
                            || str_contains($e->getMessage(), '429');

                if (!$isTransient || $attempt >= $this->maxRetries) {
                    Log::error('GeminiService exception', [
                        'attempt' => $attempt,
                        'message' => $e->getMessage(),
                    ]);
                    throw $e;
                }

                Log::warning("GeminiService transient error on attempt {$attempt}, retrying...", [
                    'message' => $e->getMessage(),
                ]);
                sleep($attempt);
            }
        }

        throw $lastException ?? new \Exception('Gemini analysis failed after retries.');
    }

    /**
     * Build the structured prompt for Gemini.
     */
    private function buildPrompt(string $code, string $language): string
    {
        return <<<PROMPT
You are an expert code reviewer. Analyze the following {$language} code.

Return ONLY a valid JSON object — no markdown, no code fences, no explanation outside the JSON.

Use exactly this structure:
{
  "quality_score": 7,
  "issues": [
    {
      "severity": "high",
      "issue": "Short title of the problem",
      "suggestion": "How to fix it specifically"
    }
  ]
}

Requirements:
- quality_score: integer from 1 to 10 (10 = perfect)
- issues: array of 3 to 5 objects
- severity: must be exactly "high", "medium", or "low"
- Be specific — reference actual variable names, lines, or patterns from the code
- If the code is good, still list minor improvements as "low" severity

Code to review ({$language}):
{$code}
PROMPT;
    }

    /**
     * Parse Gemini's raw response into our expected array shape.
     */
    private function parseResponse(array $responseData): array
    {
        $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Strip accidental markdown fences if Gemini ignores the MIME type instruction
        $text = preg_replace('/^```(?:json)?\s*/im', '', $text);
        $text = preg_replace('/```\s*$/im', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($decoded['quality_score'], $decoded['issues'])) {
            Log::warning('Gemini returned unparseable JSON', ['raw' => substr($text, 0, 500)]);
            throw new \Exception('AI returned an unexpected response format. Please try again.');
        }

        // Clamp score between 1 and 10
        $score = (int) $decoded['quality_score'];
        $score = max(1, min(10, $score));

        // Normalise issues — ensure every item has required keys
        $issues = array_map(function (array $issue): array {
            return [
                'severity'   => $issue['severity']   ?? 'low',
                'issue'      => $issue['issue']       ?? 'Unknown issue',
                'suggestion' => $issue['suggestion']  ?? 'No suggestion provided',
            ];
        }, (array) $decoded['issues']);

        return [
            'quality_score' => $score,
            'issues'        => $issues,
        ];
    }
}
