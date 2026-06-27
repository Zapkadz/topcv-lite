<?php

if (!function_exists('ai_screening_driver')) {
    function ai_screening_driver(): string
    {
        $cfg = ai_screening_config();
        $driver = strtolower(trim((string) ($cfg['driver'] ?? 'api')));

        return in_array($driver, ['api', 'cli'], true) ? $driver : 'api';
    }
}

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
            'driver' => 'api',
            'api_url' => 'http://127.0.0.1:8000/screening',
            'health_url' => 'http://127.0.0.1:8000/health',
            'api_timeout_seconds' => 180,
            'connect_timeout_seconds' => 5,
            'python_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\.venv\\Scripts\\python.exe',
            'main_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\main.py',
            'taxonomy_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\data\\taxonomy\\skills.json',
            'runtime_dir' => 'C:\\topcv_ai_runtime',
            'cli_timeout_seconds' => 180,
            'enabled' => true,
            'hf_hub_offline' => true,
            'enable_embedding' => true,
            'embedding_model' => 'BAAI/bge-m3',
            'embedding_local_only' => true,
            'debug_api_payload' => false,
            'debug_ui_diagnostics' => false,
            'debug_api_dir' => 'C:\\topcv_ai_runtime\\api-debug',
            'recommend_jobs_api_url' => 'http://127.0.0.1:8000/recommend-jobs',
            'recommend_top_k' => 10,
            'recommend_retrieval_top_n' => 50,
            'recommend_min_cv_text_length' => 150,
        ];

        $localPath = __DIR__ . '/../config/ai_screening.local.php';
        if (is_file($localPath)) {
            $loaded = require $localPath;
            if (is_array($loaded)) {
                $defaults = array_merge($defaults, $loaded);
            }
        }

        $envDriver = getenv('AI_SCREENING_DRIVER');
        if (is_string($envDriver) && trim($envDriver) !== '') {
            $defaults['driver'] = trim($envDriver);
        }

        $envApiUrl = getenv('AI_SCREENING_API_URL');
        if (is_string($envApiUrl) && trim($envApiUrl) !== '') {
            $defaults['api_url'] = trim($envApiUrl);
        }

        $envHealthUrl = getenv('AI_SCREENING_HEALTH_URL');
        if (is_string($envHealthUrl) && trim($envHealthUrl) !== '') {
            $defaults['health_url'] = trim($envHealthUrl);
        }

        $envPython = getenv('AI_PYTHON_PATH');
        if (is_string($envPython) && trim($envPython) !== '') {
            $defaults['python_path'] = trim($envPython);
        }

        $envMain = getenv('AI_MAIN_PATH');
        if (is_string($envMain) && trim($envMain) !== '') {
            $defaults['main_path'] = trim($envMain);
        }

        $envTaxonomy = getenv('AI_TAXONOMY_PATH');
        if (is_string($envTaxonomy) && trim($envTaxonomy) !== '') {
            $defaults['taxonomy_path'] = trim($envTaxonomy);
        }

        $envRuntime = getenv('AI_RUNTIME_DIR');
        if (is_string($envRuntime) && trim($envRuntime) !== '') {
            $defaults['runtime_dir'] = trim($envRuntime);
        }

        $defaults['driver'] = strtolower(trim((string) ($defaults['driver'] ?? 'api')));
        if (!in_array($defaults['driver'], ['api', 'cli'], true)) {
            $defaults['driver'] = 'api';
        }

        $defaults['api_url'] = trim((string) ($defaults['api_url'] ?? ''));
        $defaults['health_url'] = trim((string) ($defaults['health_url'] ?? ''));
        $defaults['python_path'] = trim((string) ($defaults['python_path'] ?? ''));
        $defaults['main_path'] = trim((string) ($defaults['main_path'] ?? ''));
        $defaults['taxonomy_path'] = trim((string) ($defaults['taxonomy_path'] ?? ''));
        $defaults['runtime_dir'] = rtrim(trim((string) ($defaults['runtime_dir'] ?? '')), '\\/');
        $defaults['api_timeout_seconds'] = max(30, (int) ($defaults['api_timeout_seconds'] ?? 180));
        $defaults['connect_timeout_seconds'] = max(1, (int) ($defaults['connect_timeout_seconds'] ?? 5));
        $defaults['cli_timeout_seconds'] = max(30, (int) ($defaults['cli_timeout_seconds'] ?? 180));
        $defaults['enable_embedding'] = !empty($defaults['enable_embedding']);
        $defaults['embedding_local_only'] = !empty($defaults['embedding_local_only']);
        $defaults['hf_hub_offline'] = !empty($defaults['hf_hub_offline']);
        $defaults['embedding_model'] = trim((string) ($defaults['embedding_model'] ?? 'BAAI/bge-m3'));
        $defaults['debug_api_payload'] = !empty($defaults['debug_api_payload']);
        $defaults['debug_ui_diagnostics'] = !empty($defaults['debug_ui_diagnostics']);
        $defaults['debug_api_dir'] = rtrim(trim((string) ($defaults['debug_api_dir'] ?? '')), '\\/');
        if ($defaults['debug_api_dir'] === '') {
            $defaults['debug_api_dir'] = rtrim((string) ($defaults['runtime_dir'] ?? ''), '\\/') . '\\api-debug';
        }

        $defaults['recommend_jobs_api_url'] = trim((string) ($defaults['recommend_jobs_api_url'] ?? ''));
        if ($defaults['recommend_jobs_api_url'] === '' && $defaults['api_url'] !== '') {
            $defaults['recommend_jobs_api_url'] = preg_replace('#/screening/?$#', '/recommend-jobs', $defaults['api_url'])
                ?: rtrim($defaults['api_url'], '/') . '/recommend-jobs';
        }
        $defaults['recommend_top_k'] = max(1, min(50, (int) ($defaults['recommend_top_k'] ?? 10)));
        $defaults['recommend_retrieval_top_n'] = max(1, (int) ($defaults['recommend_retrieval_top_n'] ?? 50));
        $defaults['recommend_min_cv_text_length'] = max(50, (int) ($defaults['recommend_min_cv_text_length'] ?? 150));

        if ($defaults['taxonomy_path'] !== '' && !preg_match('/^[a-zA-Z]:[\\\\\\/]/', $defaults['taxonomy_path'])) {
            $aiRoot = dirname($defaults['main_path']);
            $defaults['taxonomy_path'] = $aiRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $defaults['taxonomy_path']);
        }

        $config = $defaults;

        return $config;
    }
}

if (!function_exists('ai_screening_api_config_ready')) {
    function ai_screening_api_config_ready(): bool
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $cfg = ai_screening_config();

        return ($cfg['api_url'] ?? '') !== '' && ($cfg['health_url'] ?? '') !== '';
    }
}

if (!function_exists('ai_screening_cli_config_ready')) {
    function ai_screening_cli_config_ready(): bool
    {
        $cfg = ai_screening_config();
        $python = (string) ($cfg['python_path'] ?? '');
        $main = (string) ($cfg['main_path'] ?? '');
        $runtime = (string) ($cfg['runtime_dir'] ?? '');

        if ($python === '' || $main === '' || $runtime === '') {
            return false;
        }

        if (!is_file($python) || !is_file($main)) {
            return false;
        }

        if (!empty($cfg['enable_embedding'])) {
            $taxonomy = (string) ($cfg['taxonomy_path'] ?? '');
            if ($taxonomy === '' || !is_file($taxonomy)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('ai_screening_config_ready')) {
    function ai_screening_config_ready(): bool
    {
        $cfg = ai_screening_config();
        if (empty($cfg['enabled'])) {
            return false;
        }

        return ai_screening_driver() === 'api'
            ? ai_screening_api_config_ready()
            : ai_screening_cli_config_ready();
    }
}

if (!function_exists('ai_screening_config_status_message')) {
    function ai_screening_config_status_message(): string
    {
        if (ai_screening_config_ready()) {
            return ai_screening_driver() === 'api'
                ? 'Cấu hình AI screening sẵn sàng (FastAPI).'
                : 'Cấu hình AI screening sẵn sàng (Python CLI).';
        }

        $cfg = ai_screening_config();
        if (empty($cfg['enabled'])) {
            return 'AI screening chưa bật.';
        }

        if (ai_screening_driver() === 'api') {
            $missing = [];
            if (!function_exists('curl_init')) {
                $missing[] = 'PHP cURL extension';
            }
            if (($cfg['api_url'] ?? '') === '') {
                $missing[] = 'api_url';
            }
            if (($cfg['health_url'] ?? '') === '') {
                $missing[] = 'health_url';
            }

            if ($missing !== []) {
                return 'Thiếu cấu hình API: ' . implode(', ', $missing)
                    . '. Copy config/ai_screening.example.php → config/ai_screening.local.php';
            }

            return 'Cấu hình API chưa hợp lệ.';
        }

        $missing = [];
        if (!is_file((string) ($cfg['python_path'] ?? ''))) {
            $missing[] = 'python.exe';
        }
        if (!is_file((string) ($cfg['main_path'] ?? ''))) {
            $missing[] = 'main.py';
        }
        if (!empty($cfg['enable_embedding']) && !is_file((string) ($cfg['taxonomy_path'] ?? ''))) {
            $missing[] = 'taxonomy skills.json (absolute path)';
        }
        if ((string) ($cfg['runtime_dir'] ?? '') === '') {
            $missing[] = 'runtime_dir';
        }

        if ($missing === []) {
            return 'AI screening chưa bật hoặc cấu hình chưa hợp lệ.';
        }

        return 'Thiếu hoặc sai đường dẫn CLI: ' . implode(', ', $missing)
            . '. Copy config/ai_screening.example.php → config/ai_screening.local.php';
    }
}

if (!function_exists('ai_screening_quote_path')) {
    function ai_screening_quote_path(string $path): string
    {
        return '"' . str_replace('"', '\\"', $path) . '"';
    }
}
