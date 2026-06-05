<?php

/**
 * DEV ONLY — Chẩn đoán kết nối AI. Không expose qua web.
 * @see docs/setup-cv-import.md
 * Usage: php _test-gemini-diagnose.php
 */

require_once __DIR__ . '/../../includes/ai_config.php';
require_once __DIR__ . '/../../includes/services/AiCvParserService.php';

if (!ai_config_ready()) {
    echo "ai_config: not ready\n";
    exit(1);
}

$cfg = ai_config();
$provider = (string) ($cfg['provider'] ?? '');
$model = (string) ($cfg['model'] ?? '');
$key = (string) ($cfg['api_key'] ?? '');

echo 'provider=' . $provider . "\n";
echo 'model=' . $model . "\n";
echo 'key_prefix=' . substr($key, 0, 10) . "...\n";

$sampleText = "Nguyễn Văn A\nEmail: test@example.com\nSĐT: 0912345678\n"
    . "Kinh nghiệm: 01/2022 - 03/2024 Công ty ABC, Developer\n"
    . "Học vấn: Đại học Bách Khoa, CNTT\nKỹ năng: PHP, MySQL";

$result = AiCvParserService::parseTextToDraft($sampleText);
echo 'ok=' . ($result['ok'] ? 'true' : 'false') . "\n";
echo 'provider_used=' . (string) ($result['provider'] ?? '') . "\n";
if (!empty($result['message'])) {
    echo 'message=' . $result['message'] . "\n";
}
if (!empty($result['draft']) && is_array($result['draft'])) {
    echo 'full_name=' . (string) ($result['draft']['full_name'] ?? '') . "\n";
    echo 'email=' . (string) ($result['draft']['email'] ?? '') . "\n";
}
