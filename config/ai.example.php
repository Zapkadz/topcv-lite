<?php
/**
 * Mẫu cấu hình AI cho CV import (phase CV-E).
 * Copy -> config/ai.local.php và điền API key thật (file đã gitignore).
 *
 * Provider:
 * - groq (khuyến nghị) — key từ https://console.groq.com/keys (dạng gsk_...)
 * - openrouter — key dạng sk-or-v1-...
 * - gemini — key dạng AIza...
 */
return [
    'provider' => 'groq',
    'api_key' => 'YOUR_GROQ_API_KEY_HERE',
    // Xem model tại https://console.groq.com/docs/models
    'model' => 'llama-3.3-70b-versatile',
    'timeout_seconds' => 28,
    'max_text_chars' => 14000,
    'groq_base_url' => 'https://api.groq.com/openai/v1',
    // Chỉ dùng khi provider = openrouter
    'openrouter_base_url' => 'https://openrouter.ai/api/v1',
    'site_url' => 'http://localhost/topcv_lite',
    'app_name' => 'TopCV Lite',
];
