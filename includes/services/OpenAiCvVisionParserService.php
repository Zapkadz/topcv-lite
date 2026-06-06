<?php

require_once __DIR__ . '/../ai_config.php';
require_once __DIR__ . '/../cv_import_rules.php';
require_once __DIR__ . '/../cv_parse_vision_prompt.php';
require_once __DIR__ . '/PdfVisionRasterizer.php';
require_once __DIR__ . '/PdfTextExtractor.php';

/**
 * GPT vision / PDF parse qua OpenAI-format API (ShopAIKey hoặc OpenAI).
 */
class OpenAiCvVisionParserService
{
    /**
     * @return array{ok: bool, draft?: array<string, mixed>, message: string, provider: string, method?: string}
     */
    public static function parsePdfToDraft(string $absolutePath, string $supplementaryText = ''): array
    {
        $provider = 'openai_vision';

        if (!ai_openai_ready()) {
            return [
                'ok' => false,
                'message' => 'Chưa cấu hình GPT vision (openai trong ai.local.php).',
                'provider' => $provider,
            ];
        }

        if (!is_file($absolutePath)) {
            return ['ok' => false, 'message' => 'File PDF không tồn tại.', 'provider' => $provider];
        }

        $cfg = ai_openai_config();
        $maxPages = max(1, (int) ($cfg['max_pdf_pages'] ?? 5));
        $pageCount = PdfVisionRasterizer::countPages($absolutePath);
        if ($pageCount > $maxPages) {
            return [
                'ok' => false,
                'message' => 'PDF có ' . $pageCount . ' trang — tối đa ' . $maxPages . ' trang. Vui lòng rút gọn CV.',
                'provider' => $provider,
            ];
        }

        $systemPrompt = cv_parse_build_vision_system_prompt();
        $userPrompt = cv_parse_build_vision_user_prompt($supplementaryText);

        $attempts = [
            ['method' => 'chat_pdf_file', 'fn' => 'requestChatWithPdfFile'],
            ['method' => 'chat_page_images', 'fn' => 'requestChatWithPageImages'],
            ['method' => 'responses_pdf', 'fn' => 'requestResponsesWithPdf'],
        ];

        $errors = [];
        foreach ($attempts as $attempt) {
            $method = (string) $attempt['method'];
            $fn = (string) $attempt['fn'];
            if (!method_exists(self::class, $fn)) {
                continue;
            }

            $resp = self::$fn($absolutePath, $systemPrompt, $userPrompt, $cfg, $maxPages);
            if ($resp['ok'] && !empty($resp['draft']) && is_array($resp['draft'])) {
                return [
                    'ok' => true,
                    'draft' => $resp['draft'],
                    'message' => '',
                    'provider' => $provider,
                    'method' => $method,
                ];
            }

            $errors[] = $method . ': ' . trim((string) ($resp['message'] ?? 'fail'));
        }

        return [
            'ok' => false,
            'message' => 'GPT vision parse thất bại. ' . implode(' | ', $errors),
            'provider' => $provider,
        ];
    }

    /**
     * @param array<string, mixed> $cfg
     * @return array{ok: bool, draft?: array<string, mixed>, message?: string}
     */
    private static function requestChatWithPdfFile(
        string $absolutePath,
        string $systemPrompt,
        string $userPrompt,
        array $cfg,
        int $maxPages
    ): array {
        $pdfDataUri = self::buildPdfDataUri($absolutePath);
        if ($pdfDataUri === '') {
            return ['ok' => false, 'message' => 'Không đọc được bytes PDF.'];
        }

        $filename = basename($absolutePath) ?: 'cv.pdf';
        $content = [
            [
                'type' => 'file',
                'file' => [
                    'filename' => $filename,
                    'file_data' => $pdfDataUri,
                ],
            ],
            [
                'type' => 'text',
                'text' => $userPrompt,
            ],
        ];

        return self::requestChatCompletions($systemPrompt, $content, $cfg);
    }

    /**
     * @param array<string, mixed> $cfg
     * @return array{ok: bool, draft?: array<string, mixed>, message?: string}
     */
    private static function requestChatWithPageImages(
        string $absolutePath,
        string $systemPrompt,
        string $userPrompt,
        array $cfg,
        int $maxPages
    ): array {
        $raster = PdfVisionRasterizer::rasterizePages($absolutePath, $maxPages);
        if (!$raster['ok'] || empty($raster['images']) || !is_array($raster['images'])) {
            return ['ok' => false, 'message' => (string) ($raster['message'] ?? 'Không có ảnh trang PDF.')];
        }

        $content = [
            ['type' => 'text', 'text' => $userPrompt],
        ];

        foreach ($raster['images'] as $idx => $dataUrl) {
            $content[] = [
                'type' => 'text',
                'text' => 'Trang PDF ' . ((int) $idx + 1) . ':',
            ];
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $dataUrl,
                    'detail' => 'low',
                ],
            ];
        }

        return self::requestChatCompletions($systemPrompt, $content, $cfg);
    }

    /**
     * @param array<string, mixed> $cfg
     * @return array{ok: bool, draft?: array<string, mixed>, message?: string}
     */
    private static function requestResponsesWithPdf(
        string $absolutePath,
        string $systemPrompt,
        string $userPrompt,
        array $cfg,
        int $maxPages
    ): array {
        $pdfDataUri = self::buildPdfDataUri($absolutePath);
        if ($pdfDataUri === '') {
            return ['ok' => false, 'message' => 'Không đọc được bytes PDF.'];
        }

        $filename = basename($absolutePath) ?: 'cv.pdf';
        $model = (string) ($cfg['model'] ?? 'gpt-4o');
        $timeout = (int) ($cfg['timeout_seconds'] ?? 55);
        $apiKey = (string) ($cfg['api_key'] ?? '');

        $payload = [
            'model' => $model,
            'instructions' => $systemPrompt,
            'input' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_file',
                            'filename' => $filename,
                            'file_data' => $pdfDataUri,
                        ],
                        [
                            'type' => 'input_text',
                            'text' => $userPrompt,
                        ],
                    ],
                ],
            ],
            'text' => [
                'format' => ['type' => 'json_object'],
            ],
        ];

        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (!is_string($jsonBody)) {
            return ['ok' => false, 'message' => 'Encode JSON responses thất bại.'];
        }

        $url = ai_openai_endpoint('/responses');
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $apiKey,
        ];

        $resp = self::requestWithRetry($url, $jsonBody, $timeout, $headers);
        if (!$resp['ok']) {
            return ['ok' => false, 'message' => (string) ($resp['message'] ?? '')];
        }

        return self::decodeResponsesJsonDraft((string) ($resp['body'] ?? ''));
    }

    /**
     * @param list<array<string, mixed>> $userContent
     * @param array<string, mixed> $cfg
     * @return array{ok: bool, draft?: array<string, mixed>, message?: string}
     */
    private static function requestChatCompletions(string $systemPrompt, array $userContent, array $cfg): array
    {
        $model = (string) ($cfg['model'] ?? 'gpt-4o');
        $timeout = (int) ($cfg['timeout_seconds'] ?? 55);
        $apiKey = (string) ($cfg['api_key'] ?? '');

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userContent],
            ],
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
        ];

        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (!is_string($jsonBody)) {
            return ['ok' => false, 'message' => 'Encode JSON chat thất bại.'];
        }

        $url = ai_openai_endpoint('/chat/completions');
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $apiKey,
        ];

        $resp = self::requestWithRetry($url, $jsonBody, $timeout, $headers);
        if (!$resp['ok']) {
            return ['ok' => false, 'message' => (string) ($resp['message'] ?? '')];
        }

        return self::decodeChatJsonDraft((string) ($resp['body'] ?? ''));
    }

    private static function buildPdfDataUri(string $absolutePath): string
    {
        $bytes = @file_get_contents($absolutePath);
        if (!is_string($bytes) || $bytes === '') {
            return '';
        }

        return 'data:application/pdf;base64,' . base64_encode($bytes);
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

            $lastError = (string) ($resp['message'] ?? 'Lỗi API.');
            $retryable = str_contains($lastError, 'HTTP 429')
                || str_contains($lastError, 'HTTP 5')
                || str_contains($lastError, 'HTTP 502')
                || str_contains($lastError, 'HTTP 503')
                || str_contains($lastError, 'HTTP 504');

            if ($i === 0 && $retryable) {
                sleep(2);
                continue;
            }

            break;
        }

        return ['ok' => false, 'message' => $lastError];
    }

    /**
     * @param list<string> $headers
     * @return array{ok: bool, body?: string, message?: string}
     */
    private static function postJson(string $url, string $jsonBody, int $timeoutSeconds, array $headers): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'message' => 'Thiếu PHP curl extension.'];
        }

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
            $err = curl_error($ch) ?: 'curl error';
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

    /**
     * @return array{ok: bool, draft?: array<string, mixed>, message?: string}
     */
    private static function decodeChatJsonDraft(string $rawBody): array
    {
        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'Response không phải JSON.'];
        }

        $text = $data['choices'][0]['message']['content'] ?? '';
        if (!is_string($text) || trim($text) === '') {
            return ['ok' => false, 'message' => 'Không có content trong chat/completions.'];
        }

        return self::decodeJsonDraftFromText($text);
    }

    /**
     * @return array{ok: bool, draft?: array<string, mixed>, message?: string}
     */
    private static function decodeResponsesJsonDraft(string $rawBody): array
    {
        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'Response không phải JSON.'];
        }

        if (isset($data['output_text']) && is_string($data['output_text']) && trim($data['output_text']) !== '') {
            return self::decodeJsonDraftFromText($data['output_text']);
        }

        $output = $data['output'] ?? null;
        if (is_array($output)) {
            foreach ($output as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $content = $item['content'] ?? null;
                if (!is_array($content)) {
                    continue;
                }
                foreach ($content as $part) {
                    if (!is_array($part)) {
                        continue;
                    }
                    $text = $part['text'] ?? $part['output_text'] ?? '';
                    if (is_string($text) && trim($text) !== '') {
                        return self::decodeJsonDraftFromText($text);
                    }
                }
            }
        }

        return ['ok' => false, 'message' => 'Không parse được output từ /responses.'];
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
            return ['ok' => false, 'message' => 'JSON draft không hợp lệ.'];
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
                if ($message !== '') {
                    return 'HTTP ' . $httpCode . ': ' . $message;
                }
            }
        }

        $preview = trim(substr($rawBody, 0, 180));

        return 'HTTP ' . $httpCode . ($preview !== '' ? ': ' . $preview : '');
    }
}
