<?php

/**
 * DEV ONLY — Kiểm tra cấu hình OpenAI / ShopAIKey (CV-F). Không expose qua web.
 *
 * Usage: php docs/migrations/_test-openai-config.php
 *
 * @see https://shopaikey.com/docs/openai-format — base_url https://api.shopaikey.com/v1
 */

require_once __DIR__ . '/../../includes/ai_config.php';

echo "=== CV-E text AI (Groq/Gemini) ===\n";
echo 'ai_config_ready=' . (ai_config_ready() ? 'true' : 'false') . "\n";

$textCfg = ai_config();
echo 'text_provider=' . (string) ($textCfg['provider'] ?? '') . "\n";
echo 'text_model=' . (string) ($textCfg['model'] ?? '') . "\n";

$textKey = trim((string) ($textCfg['api_key'] ?? ''));
if ($textKey !== '') {
    echo 'text_key_prefix=' . substr($textKey, 0, 8) . "...\n";
}

echo "\n=== CV-F OpenAI-format (vision) ===\n";
echo 'ai_openai_ready=' . (ai_openai_ready() ? 'true' : 'false') . "\n";

$openaiCfg = ai_openai_config();
echo 'openai_enabled=' . (!empty($openaiCfg['enabled']) ? 'true' : 'false') . "\n";
echo 'openai_base_url=' . (string) ($openaiCfg['base_url'] ?? '') . "\n";
echo 'openai_model=' . (string) ($openaiCfg['model'] ?? '') . "\n";
echo 'openai_timeout=' . (int) ($openaiCfg['timeout_seconds'] ?? 0) . "s\n";
echo 'openai_max_pdf_pages=' . (int) ($openaiCfg['max_pdf_pages'] ?? 0) . "\n";

$openaiKey = trim((string) ($openaiCfg['api_key'] ?? ''));
if ($openaiKey !== '') {
    echo 'openai_key_prefix=' . substr($openaiKey, 0, 8) . "...\n";
}

if (!ai_openai_ready()) {
    echo "\nOpenAI chưa sẵn sàng. Thêm block openai trong config/ai.local.php\n";
    exit(1);
}

$url = ai_openai_endpoint('/chat/completions');
$timeout = max(10, min(60, (int) ($openaiCfg['timeout_seconds'] ?? 55)));
$model = (string) ($openaiCfg['model'] ?? 'gpt-4o');

$payload = json_encode([
    'model' => $model,
    'messages' => [
        ['role' => 'user', 'content' => 'Reply with exactly: pong'],
    ],
    'max_tokens' => 16,
    'temperature' => 0,
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $openaiKey,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

$body = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "\n=== API ping (POST /chat/completions) ===\n";
echo 'endpoint=' . $url . "\n";
echo 'http_code=' . $httpCode . "\n";

if ($curlError !== '') {
    echo 'curl_error=' . $curlError . "\n";
    exit(1);
}

if ($httpCode === 401) {
    echo "message=401 — kiểm tra api_key và base_url (ShopAIKey: https://api.shopaikey.com/v1)\n";
    exit(1);
}

if ($httpCode < 200 || $httpCode >= 300) {
    echo "message=HTTP lỗi từ API\n";
    if (is_string($body) && $body !== '') {
        echo 'body_preview=' . substr($body, 0, 300) . "\n";
    }
    exit(1);
}

$decoded = json_decode((string) $body, true);
$content = '';
if (is_array($decoded)) {
    $content = (string) ($decoded['choices'][0]['message']['content'] ?? '');
}

echo 'reply=' . trim($content) . "\n";
echo "openai_ping=OK\n";
