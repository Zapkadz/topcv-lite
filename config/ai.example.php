<?php
/**
 * Mẫu cấu hình AI cho CV import (phase CV-E).
 * Copy file này thành config/ai.local.php và điền API key thật.
 * File ai.local.php đã được gitignore — không commit key.
 */
return [
  'provider' => 'gemini',
  'api_key' => 'YOUR_GEMINI_API_KEY_HERE',
  'model' => 'gemini-2.0-flash',
  'timeout_seconds' => 28,
  'max_text_chars' => 14000,
];
