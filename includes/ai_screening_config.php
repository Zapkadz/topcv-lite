<?php

if (!function_exists('ai_screening_config')) {
    /**
     * @return array<string, mixed>
     */
    function ai_screening_config(): array
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $defaults = [
            'python_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\.venv\\Scripts\\python.exe',
            'main_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\main.py',
            'runtime_dir' => 'C:\\topcv_ai_runtime',
            'cli_timeout_seconds' => 120,
            'enabled' => true,
        ];

        $localPath = __DIR__ . '/../config/ai_screening.local.php';
        if (is_file($localPath)) {
            $loaded = require $localPath;
            if (is_array($loaded)) {
                $defaults = array_merge($defaults, $loaded);
            }
        }

        $envPython = getenv('AI_PYTHON_PATH');
        if (is_string($envPython) && trim($envPython) !== '') {
            $defaults['python_path'] = trim($envPython);
        }

        $envMain = getenv('AI_MAIN_PATH');
        if (is_string($envMain) && trim($envMain) !== '') {
            $defaults['main_path'] = trim($envMain);
        }

        $envRuntime = getenv('AI_RUNTIME_DIR');
        if (is_string($envRuntime) && trim($envRuntime) !== '') {
            $defaults['runtime_dir'] = trim($envRuntime);
        }

        $defaults['python_path'] = trim((string) ($defaults['python_path'] ?? ''));
        $defaults['main_path'] = trim((string) ($defaults['main_path'] ?? ''));
        $defaults['runtime_dir'] = rtrim(trim((string) ($defaults['runtime_dir'] ?? '')), '\\/');
        $defaults['cli_timeout_seconds'] = max(30, (int) ($defaults['cli_timeout_seconds'] ?? 120));

        $config = $defaults;

        return $config;
    }
}

if (!function_exists('ai_screening_config_ready')) {
    function ai_screening_config_ready(): bool
    {
        $cfg = ai_screening_config();
        if (empty($cfg['enabled'])) {
            return false;
        }

        $python = (string) ($cfg['python_path'] ?? '');
        $main = (string) ($cfg['main_path'] ?? '');
        $runtime = (string) ($cfg['runtime_dir'] ?? '');

        if ($python === '' || $main === '' || $runtime === '') {
            return false;
        }

        return is_file($python) && is_file($main);
    }
}

if (!function_exists('ai_screening_config_status_message')) {
    function ai_screening_config_status_message(): string
    {
        if (ai_screening_config_ready()) {
            return 'Cấu hình AI screening sẵn sàng (Python CLI).';
        }

        $cfg = ai_screening_config();
        $missing = [];
        if (!is_file((string) ($cfg['python_path'] ?? ''))) {
            $missing[] = 'python.exe';
        }
        if (!is_file((string) ($cfg['main_path'] ?? ''))) {
            $missing[] = 'main.py';
        }
        if ((string) ($cfg['runtime_dir'] ?? '') === '') {
            $missing[] = 'runtime_dir';
        }

        if ($missing === []) {
            return 'AI screening chưa bật hoặc cấu hình chưa hợp lệ.';
        }

        return 'Thiếu hoặc sai đường dẫn: ' . implode(', ', $missing)
            . '. Copy config/ai_screening.example.php → config/ai_screening.local.php';
    }
}

if (!function_exists('ai_screening_quote_path')) {
    function ai_screening_quote_path(string $path): string
    {
        return '"' . str_replace('"', '\\"', $path) . '"';
    }
}
