<?php
/**
 * Mẫu cấu hình AI cho CV import.
 * Copy -> config/ai.local.php và điền API key thật (file đã gitignore).
 *
 * CV-E (text path):
 * - groq (khuyến nghị) — https://console.groq.com/keys (gsk_...)
 * - openrouter — sk-or-v1-...
 * - gemini — AIza...
 *
 * CV-F (GPT vision scan PDF) — block `openai` bên dưới (cùng file).
 * ShopAIKey: base_url = https://api.shopaikey.com/v1
 * OpenAI chính thức: base_url = https://api.openai.com/v1
 */
return [
    // --- CV-E: trích text → AI text ---
    'provider' => 'groq',
    'api_key' => 'YOUR_GROQ_API_KEY_HERE',
    'model' => 'llama-3.3-70b-versatile',
    'timeout_seconds' => 28,
    'max_text_chars' => 14000,
    'groq_base_url' => 'https://api.groq.com/openai/v1',
    'openrouter_base_url' => 'https://openrouter.ai/api/v1',
    'site_url' => 'http://localhost/topcv_lite',
    'app_name' => 'TopCV Lite',

    // --- CV-F: GPT vision PDF (tuỳ chọn) ---
    'openai' => [
        'api_key' => 'YOUR_OPENAI_OR_SHOPAIKEY_API_KEY_HERE',
        'base_url' => 'https://api.shopaikey.com/v1',
        'model' => 'gpt-4o',
        'timeout_seconds' => 55,
        'max_pdf_pages' => 5,
        'enabled' => true,
    ],
];
