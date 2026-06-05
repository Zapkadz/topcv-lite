<?php

require_once __DIR__ . '/../ai_config.php';
require_once __DIR__ . '/../cv_parse_prompt.php';
require_once __DIR__ . '/../cv_import_rules.php';

/**
 * AI CV parser: text -> structured draft JSON (Mức B).
 * Providers: gemini | openrouter | groq
 */
class AiCvParserService
{
    /**
     * @return array{ok: bool, draft?: array<string, mixed>, message: string, provider?: string}
     */
    public static function parseTextToDraft(string $text): array
    {
        $cfg = ai_config();
        $provider = (string) ($cfg['provider'] ?? 'gemini');

        if (!ai_config_ready()) {
            return [
                'ok' => false,
                'message' => 'Chưa cấu hình AI (thiếu API key).',
                'provider' => $provider,
            ];
        }

        $maxChars = (int) ($cfg['max_text_chars'] ?? 14000);
        if (function_exists('cv_import_truncate_text')) {
            $text = cv_import_truncate_text($text, $maxChars);
        }

        return match ($provider) {
            'gemini' => self::parseViaGemini($text, $cfg),
            'openrouter' => self::parseViaOpenRouter($text, $cfg),
            'groq' => self::parseViaGroq($text, $cfg),
            default => [
                'ok' => false,
                'message' => 'Provider AI không hỗ trợ: ' . $provider,
                'provider' => $provider,
            ],
        };
    }

    /**
     * @param array<string, mixed> $cfg
     * @return array{ok: bool, draft?: array<string, mixed>, message: string, provider: string}
     */
    private static function parseViaGemini(string $text, array $cfg): array
    {
        $provider = 'gemini';
        $apiKey = (string) ($cfg['api_key'] ?? '');
        $model = (string) ($cfg['model'] ?? 'gemini-2.0-flash');
        $timeout = (int) ($cfg['timeout_seconds'] ?? 28);

        $systemPrompt = cv_parse_build_system_prompt();
        $userPrompt = cv_parse_build_user_prompt($text);

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($model)
            . ':generateContent?key=' . rawurlencode($apiKey);

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $userPrompt]],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
            ],
        ];

        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (!is_string($jsonBody)) {
            return ['ok' => false, 'message' => 'Không thể encode payload JSON cho AI.', 'provider' => $provider];
        }

        $resp = self::requestWithRetry($url, $jsonBody, $timeout, ['Content-Type: application/json']);
        if (!$resp['ok']) {
            return ['ok' => false, 'message' => 'AI parse thất bại: ' . ($resp['message'] ?? ''), 'provider' => $provider];
        }

        $draft = self::decodeGeminiJsonDraft((string) $resp['body']);
        if (!$draft['ok']) {
            return ['ok' => false, 'message' => 'AI parse thất bại: ' . ($draft['message'] ?? ''), 'provider' => $provider];
        }

        return ['ok' => true, 'draft' => $draft['draft'], 'message' => '', 'provider' => $provider];
    }

    /**
     * @param array<string, mixed> $cfg
     * @return array{ok: bool, draft?: array<string, mixed>, message: string, provider: string}
     */
    private static function parseViaOpenRouter(string $text, array $cfg): array
    {
        $siteUrl = (string) ($cfg['site_url'] ?? 'http://localhost/topcv_lite');
        $appName = (string) ($cfg['app_name'] ?? 'TopCV Lite');
        $baseUrl = rtrim((string) ($cfg['openrouter_base_url'] ?? 'https://openrouter.ai/api/v1'), '/');

        return self::parseViaOpenAiChat($text, $cfg, 'openrouter', $baseUrl, [
            'HTTP-Referer: ' . $siteUrl,
            'X-Title: ' . $appName,
        ]);
    }

    /**
     * @param array<string, mixed> $cfg
     * @return array{ok: bool, draft?: array<string, mixed>, message: string, provider: string}
     */
    private static function parseViaGroq(string $text, array $cfg): array
    {
        $baseUrl = rtrim((string) ($cfg['groq_base_url'] ?? 'https://api.groq.com/openai/v1'), '/');

        return self::parseViaOpenAiChat($text, $cfg, 'groq', $baseUrl, []);
    }

    /**
     * OpenAI-compatible chat/completions (OpenRouter, Groq).
     *
     * @param array<string, mixed> $cfg
     * @param list<string> $extraHeaders
     * @return array{ok: bool, draft?: array<string, mixed>, message: string, provider: string}
     */
    private static function parseViaOpenAiChat(
        string $text,
        array $cfg,
        string $provider,
        string $baseUrl,
        array $extraHeaders
    ): array {
        $apiKey = (string) ($cfg['api_key'] ?? '');
        $model = (string) ($cfg['model'] ?? '');
        $timeout = (int) ($cfg['timeout_seconds'] ?? 28);

        if ($model === '') {
            $model = $provider === 'groq' ? 'llama-3.3-70b-versatile' : 'google/gemini-2.0-flash-exp:free';
        }

        $systemPrompt = cv_parse_build_system_prompt();
        $userPrompt = cv_parse_build_user_prompt($text);

        $url = $baseUrl . '/chat/completions';
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object'],
        ];

        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (!is_string($jsonBody)) {
            return ['ok' => false, 'message' => 'Không thể encode payload JSON cho ' . $provider . '.', 'provider' => $provider];
        }

        $headers = array_merge(
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            $extraHeaders
        );

        $resp = self::requestWithRetry($url, $jsonBody, $timeout, $headers);
        if (!$resp['ok']) {
            return ['ok' => false, 'message' => 'AI parse thất bại: ' . ($resp['message'] ?? ''), 'provider' => $provider];
        }

        $draft = self::decodeOpenAiChatJsonDraft((string) $resp['body']);
        if (!$draft['ok']) {
            return ['ok' => false, 'message' => 'AI parse thất bại: ' . ($draft['message'] ?? ''), 'provider' => $provider];
        }

        return ['ok' => true, 'draft' => $draft['draft'], 'message' => '', 'provider' => $provider];
    }

    /**
     * @param list<string> $headers
     * @return array{ok: bool, body?: string, message?: string}
     */
    private static function requestWithRetry(string $url, string $jsonBody, int $timeout, array $headers): array
    {
        $attempts = 2;
        $lastError = '';
        for ($i = 0; $i < $attempts; $i++) {
            $resp = self::postJson($url, $jsonBody, $timeout, $headers);
            if ($resp['ok']) {
                return $resp;
            }

            $lastError = $resp['message'] ?? 'Lỗi gọi AI.';
            $isRetryable = str_contains($lastError, 'HTTP 5') || str_contains($lastError, 'HTTP 502')
                || str_contains($lastError, 'HTTP 503') || str_contains($lastError, 'HTTP 504');
            if ($i === 0 && $isRetryable) {
                sleep(2);
            } elseif (!$isRetryable) {
                break;
            }
        }

        return ['ok' => false, 'message' => $lastError];
    }

    /**
     * @param list<string> $headers
     * @return array{ok: bool, body?: string, message?: string}
     */
    private static function postJson(string $url, string $jsonBody, int $timeoutSeconds, array $headers): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $jsonBody,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);

            $raw = curl_exec($ch);
            if ($raw === false) {
                $err = curl_error($ch) ?: 'unknown curl error';
                curl_close($ch);
                return ['ok' => false, 'message' => $err];
            }

            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 400) {
                return ['ok' => false, 'message' => self::formatHttpError($httpCode, (string) $raw)];
            }

            return ['ok' => true, 'body' => (string) $raw];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $jsonBody,
                'timeout' => $timeoutSeconds,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return ['ok' => false, 'message' => 'Không gọi được AI bằng file_get_contents().'];
        }

        return ['ok' => true, 'body' => (string) $raw];
    }

    /**
     * @return array{ok: bool, draft?: array<string, mixed>, message?: string}
     */
    private static function decodeGeminiJsonDraft(string $rawBody): array
    {
        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'AI trả về body không phải JSON hợp lệ.'];
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (!is_string($text) || trim($text) === '') {
            return ['ok' => false, 'message' => 'Không thấy phần text trong response Gemini.'];
        }

        return self::decodeJsonDraftFromText($text);
    }

    /**
     * @return array{ok: bool, draft?: array<string, mixed>, message?: string}
     */
    private static function decodeOpenAiChatJsonDraft(string $rawBody): array
    {
        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'AI trả về body không phải JSON hợp lệ.'];
        }

        $text = $data['choices'][0]['message']['content'] ?? '';
        if (!is_string($text) || trim($text) === '') {
            return ['ok' => false, 'message' => 'Không thấy nội dung trong response chat/completions.'];
        }

        return self::decodeJsonDraftFromText($text);
    }

    /**
     * @return array{ok: bool, draft?: array<string, mixed>, message?: string}
     */
    private static function decodeJsonDraftFromText(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $draft = json_decode((string) $text, true);
        if (!is_array($draft)) {
            return ['ok' => false, 'message' => 'Không decode được JSON draft từ response AI.'];
        }

        return ['ok' => true, 'draft' => $draft];
    }

    private static function formatHttpError(int $httpCode, string $rawBody): string
    {
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded) && isset($decoded['error'])) {
            $err = $decoded['error'];
            if (is_string($err)) {
                return 'HTTP ' . $httpCode . ': ' . $err;
            }
            if (is_array($err)) {
                $message = trim((string) ($err['message'] ?? ''));
                $status = (string) ($err['status'] ?? $err['code'] ?? '');
                if ($message !== '' && $status !== '') {
                    return 'HTTP ' . $httpCode . ' (' . $status . '): ' . $message;
                }
                if ($message !== '') {
                    return 'HTTP ' . $httpCode . ': ' . $message;
                }
            }
        }

        return 'HTTP ' . $httpCode;
    }
}
