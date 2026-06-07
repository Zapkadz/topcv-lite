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
     * @return array{ok: bool, exit_code: int, output: string, message: string}
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
        $timeout = (int) ($cfg['cli_timeout_seconds'] ?? 120);
        if ($timeout > 0) {
            @set_time_limit($timeout + 15);
        }

        $cmd = ai_screening_quote_path((string) $cfg['python_path'])
            . ' ' . ai_screening_quote_path((string) $cfg['main_path'])
            . ' --jd ' . ai_screening_quote_path($jdPath)
            . ' --cv-dir ' . ai_screening_quote_path($cvDir);

        $taxonomyPath = trim((string) ($cfg['taxonomy_path'] ?? ''));
        if ($taxonomyPath !== '' && is_file($taxonomyPath)) {
            $cmd .= ' --taxonomy ' . ai_screening_quote_path($taxonomyPath);
        }

        $cmd .= ' --output-json ' . ai_screening_quote_path($outputJson);

        ai_screening_log('CLI: ' . $cmd);

        putenv('PYTHONIOENCODING=utf-8');
        putenv('PYTHONUTF8=1');

        $outputLines = [];
        $exitCode = 1;
        exec($cmd . ' 2>&1', $outputLines, $exitCode);
        $output = implode("\n", $outputLines);

        if ($exitCode !== 0) {
            ai_screening_log('CLI failed exit=' . $exitCode . ' output=' . substr($output, 0, 2000));

            return [
                'ok' => false,
                'exit_code' => $exitCode,
                'output' => $output,
                'message' => 'Python AI screening thất bại (mã ' . $exitCode . ').',
            ];
        }

        if (!is_file($outputJson)) {
            return [
                'ok' => false,
                'exit_code' => $exitCode,
                'output' => $output,
                'message' => 'Không tìm thấy file kết quả JSON sau khi chạy AI.',
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
