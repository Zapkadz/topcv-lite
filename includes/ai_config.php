<?php

if (!function_exists('ai_config')) {
    /**
     * @return array<string, mixed>
     */
    function ai_config(): array
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $defaults = [
            'provider' => 'groq',
            'api_key' => '',
            'model' => 'llama-3.3-70b-versatile',
            'timeout_seconds' => 28,
            'max_text_chars' => 14000,
            'groq_base_url' => 'https://api.groq.com/openai/v1',
            'openrouter_base_url' => 'https://openrouter.ai/api/v1',
            'site_url' => 'http://localhost/topcv_lite',
            'app_name' => 'TopCV Lite',
        ];

        $localPath = __DIR__ . '/../config/ai.local.php';
        if (is_file($localPath)) {
            $loaded = require $localPath;
            if (is_array($loaded)) {
                $defaults = array_merge($defaults, $loaded);
            }
        }

        $groqKey = getenv('GROQ_API_KEY');
        if (is_string($groqKey) && trim($groqKey) !== '') {
            $defaults['api_key'] = trim($groqKey);
            $defaults['provider'] = 'groq';
        }

        $openRouterKey = getenv('OPENROUTER_API_KEY');
        if (is_string($openRouterKey) && trim($openRouterKey) !== ''
            && trim((string) ($defaults['api_key'] ?? '')) === '') {
            $defaults['api_key'] = trim($openRouterKey);
            $defaults['provider'] = 'openrouter';
        }

        $geminiKey = getenv('GEMINI_API_KEY');
        if (is_string($geminiKey) && trim($geminiKey) !== ''
            && trim((string) ($defaults['api_key'] ?? '')) === '') {
            $defaults['api_key'] = trim($geminiKey);
            $defaults['provider'] = 'gemini';
        }

        $config = $defaults;

        return $config;
    }
}

if (!function_exists('ai_config_ready')) {
    function ai_config_ready(): bool
    {
        $config = ai_config();
        $key = trim((string) ($config['api_key'] ?? ''));
        $placeholders = [
            'YOUR_GEMINI_API_KEY_HERE',
            'YOUR_OPENROUTER_API_KEY_HERE',
            'YOUR_GROQ_API_KEY_HERE',
            'sk-or-v1-xxxxxxxx',
            'gsk_xxxxxxxx',
        ];

        if ($key === '' || in_array($key, $placeholders, true)) {
            return false;
        }

        return true;
    }
}
