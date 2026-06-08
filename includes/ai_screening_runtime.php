<?php

require_once __DIR__ . '/ai_screening_config.php';

if (!function_exists('ai_screening_cv_filename')) {
    function ai_screening_cv_filename(int $applicationId, int $candidateId): string
    {
        return sprintf('application-%d__candidate-%d.txt', $applicationId, $candidateId);
    }
}

if (!function_exists('ai_screening_parse_application_id_from_source_file')) {
    function ai_screening_parse_application_id_from_source_file(string $sourceFile): ?int
    {
        $basename = basename(str_replace('\\', '/', $sourceFile));
        if (preg_match('/^application-(\d+)__candidate-(\d+)\.txt$/', $basename, $m) !== 1) {
            return null;
        }

        return (int) $m[1];
    }
}

if (!function_exists('ai_screening_make_run_directory')) {
    /**
     * @return array{run_id: string, path: string}
     */
    function ai_screening_make_run_directory(int $jobId): array
    {
        $cfg = ai_screening_config();
        $runtimeRoot = (string) ($cfg['runtime_dir'] ?? '');
        if ($runtimeRoot === '') {
            throw new RuntimeException('Chưa cấu hình runtime_dir cho AI screening.');
        }

        $runId = 'run-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $path = $runtimeRoot . DIRECTORY_SEPARATOR . 'job-' . $jobId . DIRECTORY_SEPARATOR . $runId;

        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException('Không tạo được thư mục runtime AI: ' . $path);
        }

        $cvsPath = $path . DIRECTORY_SEPARATOR . 'cvs';
        if (!is_dir($cvsPath) && !mkdir($cvsPath, 0755, true) && !is_dir($cvsPath)) {
            throw new RuntimeException('Không tạo được thư mục CV runtime AI: ' . $cvsPath);
        }

        return ['run_id' => $runId, 'path' => $path];
    }
}

if (!function_exists('ai_screening_log')) {
    function ai_screening_log(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        error_log($line);

        $logDir = dirname(__DIR__) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/ai_screening.log';
        if (is_dir($logDir)) {
            @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        }
    }
}

if (!function_exists('ai_screening_run_cli')) {
    /**
     * @return array{ok: bool, exit_code: int, output: string, message: string, detail?: string}
     */
    function ai_screening_run_cli(string $jdPath, string $cvDir, string $outputJson): array
    {
        if (!ai_screening_config_ready()) {
            return [
                'ok' => false,
                'exit_code' => 1,
                'output' => '',
                'message' => ai_screening_config_status_message(),
            ];
        }

        $cfg = ai_screening_config();
        $timeout = (int) ($cfg['cli_timeout_seconds'] ?? 180);
        if ($timeout > 0) {
            @set_time_limit($timeout + 30);
        }

        if (!function_exists('ai_taxonomy_effective_screening_path')) {
            require_once __DIR__ . '/ai_taxonomy_config.php';
        }
        $taxonomyPath = ai_taxonomy_effective_screening_path();
        if ($taxonomyPath === '') {
            $taxonomyPath = trim((string) ($cfg['taxonomy_path'] ?? ''));
        }
        if (!empty($cfg['enable_embedding'])) {
            if ($taxonomyPath === '' || !is_file($taxonomyPath)) {
                return [
                    'ok' => false,
                    'exit_code' => 1,
                    'output' => '',
                    'message' => 'Thiếu file taxonomy (đường dẫn tuyệt đối): ' . ($taxonomyPath !== '' ? $taxonomyPath : '(chưa cấu hình)'),
                ];
            }
        }

        $cmd = ai_screening_quote_path((string) $cfg['python_path'])
            . ' ' . ai_screening_quote_path((string) $cfg['main_path'])
            . ' --jd ' . ai_screening_quote_path($jdPath)
            . ' --cv-dir ' . ai_screening_quote_path($cvDir);

        if ($taxonomyPath !== '' && is_file($taxonomyPath)) {
            $cmd .= ' --taxonomy ' . ai_screening_quote_path($taxonomyPath);
        }

        $cmd .= ' --output-json ' . ai_screening_quote_path($outputJson);

        if (!empty($cfg['enable_embedding'])) {
            $model = trim((string) ($cfg['embedding_model'] ?? 'BAAI/bge-m3'));
            $cmd .= ' --enable-embedding';
            if ($model !== '') {
                $cmd .= ' --embedding-model ' . ai_screening_quote_path($model);
            }
            if (!empty($cfg['embedding_local_only'])) {
                $cmd .= ' --embedding-local-only';
            }
        }

        $cliLogPath = dirname($outputJson) . DIRECTORY_SEPARATOR . 'cli.log';
        ai_screening_log('CLI: ' . $cmd);
        ai_screening_log('output_json=' . $outputJson);

        if (!empty($cfg['hf_hub_offline'])) {
            putenv('HF_HUB_OFFLINE=1');
        }
        putenv('PYTHONIOENCODING=utf-8');
        putenv('PYTHONUTF8=1');

        $outputLines = [];
        $exitCode = 1;
        exec($cmd . ' 2>&1', $outputLines, $exitCode);
        $output = implode("\n", $outputLines);

        $logBlock = '[' . date('Y-m-d H:i:s') . "] exit={$exitCode}\nCMD: {$cmd}\n--- output ---\n{$output}\n\n";
        @file_put_contents($cliLogPath, $logBlock, FILE_APPEND | LOCK_EX);

        if ($exitCode !== 0) {
            ai_screening_log('CLI failed exit=' . $exitCode . ' output=' . substr($output, 0, 2000));
            $detail = ai_screening_cli_user_detail($output);

            return [
                'ok' => false,
                'exit_code' => $exitCode,
                'output' => $output,
                'message' => 'AI CLI failed. Please try again later.',
                'detail' => $detail !== '' ? $detail : 'Xem storage/logs/ai_screening.log và cli.log trong runtime folder.',
            ];
        }

        if (!is_file($outputJson)) {
            return [
                'ok' => false,
                'exit_code' => $exitCode,
                'output' => $output,
                'message' => 'AI CLI failed. Please try again later.',
                'detail' => 'Không tìm thấy ranking_results.json sau khi chạy CLI.',
            ];
        }

        return [
            'ok' => true,
            'exit_code' => 0,
            'output' => $output,
            'message' => 'AI screening hoàn tất.',
        ];
    }
}

if (!function_exists('ai_screening_parse_ranking_json')) {
    /**
     * @return list<array<string, mixed>>
     */
    function ai_screening_parse_ranking_json(string $jsonPath): array
    {
        $raw = file_get_contents($jsonPath);
        if ($raw === false || trim($raw) === '') {
            throw new RuntimeException('File ranking JSON rỗng hoặc không đọc được.');
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('Không parse được ranking JSON.');
        }

        if (isset($data['candidates']) && is_array($data['candidates'])) {
            return array_values($data['candidates']);
        }

        if (array_is_list($data)) {
            return $data;
        }

        throw new RuntimeException('JSON ranking không có mảng candidates.');
    }
}

if (!function_exists('ai_screening_cli_user_detail')) {
    function ai_screening_cli_user_detail(string $output, int $maxLen = 400): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $output) ?: [])));
        if ($lines === []) {
            return '';
        }

        $snippet = implode("\n", array_slice($lines, -6));
        if (strlen($snippet) > $maxLen) {
            return substr($snippet, 0, $maxLen) . '…';
        }

        return $snippet;
    }
}
