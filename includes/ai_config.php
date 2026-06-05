<?php

if (!function_exists('ai_config')) {
    /**
     * @return array{provider: string, api_key: string, model: string, timeout_seconds: int, max_text_chars: int}
     */
    function ai_config(): array
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $defaults = [
            'provider' => 'gemini',
            'api_key' => '',
            'model' => 'gemini-2.0-flash',
            'timeout_seconds' => 28,
            'max_text_chars' => 14000,
        ];

        $localPath = __DIR__ . '/../config/ai.local.php';
        if (is_file($localPath)) {
            $loaded = require $localPath;
            if (is_array($loaded)) {
                $defaults = array_merge($defaults, $loaded);
            }
        }

        $envKey = getenv('GEMINI_API_KEY');
        if (is_string($envKey) && trim($envKey) !== '') {
            $defaults['api_key'] = trim($envKey);
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

        return $key !== '' && $key !== 'YOUR_GEMINI_API_KEY_HERE';
    }
}
