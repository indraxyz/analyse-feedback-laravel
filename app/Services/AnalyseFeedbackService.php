<?php

namespace App\Services;

use App\Exceptions\AnthropicApiKeyMissingException;
use App\Exceptions\AnthropicServiceException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyseFeedbackService
{
    private const ANTHROPIC_MESSAGES_URL = 'https://api.anthropic.com/v1/messages';

    /**
     * @return array{summary: string, sentiment: 'positive'|'neutral'|'negative', language: string}
     *
     * @throws AnthropicApiKeyMissingException
     * @throws AnthropicServiceException
     */
    public function analyse(string $feedbackText): array
    {
        $apiKey = config('services.anthropic.key');
        if (empty($apiKey)) {
            Log::warning('Anthropic API key not configured');
            throw new AnthropicApiKeyMissingException;
        }

        $model = config('services.anthropic.model', 'claude-sonnet-4-6');
        $prompt = $this->buildPrompt($feedbackText);
        $verifySsl = config('services.anthropic.verify_ssl', true);
        if ($verifySsl === false) {
            Log::warning('SSL verification disabled for Anthropic API (HTTP_VERIFY_SSL=false). Do not use in production.');
        }

        try {
            $client = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60);
            if ($verifySsl === false) {
                $client = $client->withOptions(['verify' => false]);
            }
            $response = $client->post(self::ANTHROPIC_MESSAGES_URL, [
                'model' => $model,
                'max_tokens' => 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Anthropic API request failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw new AnthropicServiceException('AI service error or invalid response');
        }

        if (! $response->successful()) {
            Log::error('Anthropic API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new AnthropicServiceException('AI service error or invalid response');
        }

        $body = $response->json();
        if (! is_array($body)) {
            Log::error('Anthropic API returned invalid JSON', ['body' => $response->body()]);

            throw new AnthropicServiceException('AI service error or invalid response');
        }

        $text = $this->extractTextFromResponse($body);
        if ($text === null) {
            Log::error('Anthropic API returned no text content', ['body' => $body]);
            throw new AnthropicServiceException('AI service error or invalid response');
        }

        $result = $this->parseAnalysisResult($text);
        if ($result === null) {
            Log::error('Failed to parse Anthropic response as JSON', ['text' => $text]);
            throw new AnthropicServiceException('AI service error or invalid response');
        }

        return $result;
    }

    private function buildPrompt(string $feedbackText): string
    {
        return <<<PROMPT
Analyse the following customer feedback. The feedback may be in any language.

Return ONLY a single JSON object (no markdown, no code fence) with exactly these keys:
- "summary": string — a short English summary of the feedback (1–3 sentences).
- "sentiment": string — one of: "positive", "neutral", "negative".
- "language": string — the detected language of the feedback in lowercase (e.g. "english", "indonesian", "japanese").

Feedback to analyse:

{$feedbackText}
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function extractTextFromResponse(array $body): ?string
    {
        $content = $body['content'] ?? null;
        if (! is_array($content)) {
            return null;
        }
        $parts = [];
        foreach ($content as $block) {
            if (! is_array($block) || ($block['type'] ?? '') !== 'text') {
                continue;
            }
            $text = $block['text'] ?? null;
            if (is_string($text) && $text !== '') {
                $parts[] = $text;
            }
        }
        if ($parts === []) {
            return null;
        }

        return trim(implode("\n", $parts));
    }

    /**
     * @return array{summary: string, sentiment: 'positive'|'neutral'|'negative', language: string}|null
     */
    private function parseAnalysisResult(string $text): ?array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*\n?|\n?\s*```$/m', '', trim($text));
        $decoded = json_decode($cleaned, true);
        if (! is_array($decoded)) {
            $start = strpos($text, '{');
            if ($start !== false) {
                $end = strrpos($text, '}');
                if ($end !== false && $end > $start) {
                    $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
                }
            }
            if (! is_array($decoded)) {
                return null;
            }
        }
        $summary = isset($decoded['summary']) && is_string($decoded['summary'])
            ? $decoded['summary']
            : null;
        $sentiment = isset($decoded['sentiment']) && is_string($decoded['sentiment'])
            ? strtolower(trim($decoded['sentiment']))
            : null;
        $language = isset($decoded['language']) && is_string($decoded['language'])
            ? $decoded['language']
            : null;
        $validSentiments = ['positive', 'neutral', 'negative'];
        if ($summary === null || $sentiment === null || $language === null || ! in_array($sentiment, $validSentiments, true)) {
            return null;
        }

        return [
            'summary' => $summary,
            'sentiment' => $sentiment,
            'language' => $language,
        ];
    }
}
