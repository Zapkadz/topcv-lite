<?php

require_once __DIR__ . '/ai_screening_config.php';
require_once __DIR__ . '/ai_screening_runtime.php';

if (!function_exists('ai_screening_debug_api_enabled')) {
    function ai_screening_debug_api_enabled(): bool
    {
        $cfg = ai_screening_config();

        return !empty($cfg['debug_api_payload']);
    }
}

if (!function_exists('ai_screening_debug_api_dir')) {
    function ai_screening_debug_api_dir(): string
    {
        $cfg = ai_screening_config();

        return rtrim(trim((string) ($cfg['debug_api_dir'] ?? '')), '\\/');
    }
}

if (!function_exists('ai_screening_debug_api_file_prefix')) {
    function ai_screening_debug_api_file_prefix(int $jobId): string
    {
        return date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '-job-' . $jobId;
    }
}

if (!function_exists('ai_screening_debug_api_write_file')) {
    function ai_screening_debug_api_write_file(string $dir, string $filename, string $content): ?string
    {
        try {
            if ($dir === '') {
                throw new RuntimeException('debug_api_dir empty');
            }
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('cannot create debug dir: ' . $dir);
            }

            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            if (file_put_contents($path, $content, LOCK_EX) === false) {
                throw new RuntimeException('cannot write debug file: ' . $path);
            }

            return $path;
        } catch (Throwable $e) {
            ai_screening_log('API debug write failed: ' . $e->getMessage());

            return null;
        }
    }
}

if (!function_exists('ai_screening_log_debug_request_sanity')) {
    /**
     * @param array<string, mixed> $payload
     */
    function ai_screening_log_debug_request_sanity(array $payload): void
    {
        $job = is_array($payload['job'] ?? null) ? $payload['job'] : [];
        $requirements = $job['requirements'] ?? [];
        $description = (string) ($job['description'] ?? '');

        $hasHtmlReq = false;
        if (is_array($requirements)) {
            foreach ($requirements as $line) {
                if (is_string($line) && preg_match('/<[^>]+>/', $line)) {
                    $hasHtmlReq = true;
                    break;
                }
            }
        }

        $hasHtmlDesc = (bool) preg_match('/<[^>]+>/', $description);

        $candidates = is_array($payload['candidates'] ?? null) ? $payload['candidates'] : [];
        $first = is_array($candidates[0] ?? null) ? $candidates[0] : [];
        $appId = $first['application_id'] ?? '?';
        $cvLen = strlen(trim((string) ($first['cv_text'] ?? '')));

        ai_screening_log(
            'API debug payload sanity'
            . ' requirements_html=' . ($hasHtmlReq ? 'yes' : 'no')
            . ' description_html=' . ($hasHtmlDesc ? 'yes' : 'no')
            . ' first_candidate_app_id=' . $appId
            . ' cv_text_len=' . $cvLen
        );
    }
}

if (!function_exists('ai_screening_log_api_response_metadata')) {
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $result
     */
    function ai_screening_log_api_response_metadata(array $payload, array $result): void
    {
        $jobId = (int) ($payload['job']['job_id'] ?? ($result['job']['job_id'] ?? 0));
        $candidateCount = is_array($payload['candidates'] ?? null) ? count($payload['candidates']) : 0;

        $respJob = is_array($result['job'] ?? null) ? $result['job'] : [];
        $responseJobTitle = trim((string) ($respJob['job_title'] ?? $respJob['title'] ?? ''));

        $openSet = $respJob['open_set_requirements'] ?? [];
        $openSetCount = is_array($openSet) ? count($openSet) : 0;

        $screeningConfidence = is_array($respJob['screening_confidence'] ?? null)
            ? $respJob['screening_confidence']
            : [];
        $embeddingEnabled = $screeningConfidence['embedding_enabled'] ?? null;
        $embeddingLabel = $embeddingEnabled === null
            ? 'null'
            : ($embeddingEnabled ? 'true' : 'false');

        $respCandidates = is_array($result['candidates'] ?? null) ? $result['candidates'] : [];
        $top = null;
        foreach ($respCandidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            if ($top === null) {
                $top = $candidate;
                continue;
            }
            $rank = (int) ($candidate['rank'] ?? PHP_INT_MAX);
            $topRank = (int) ($top['rank'] ?? PHP_INT_MAX);
            if ($rank < $topRank) {
                $top = $candidate;
            }
        }

        $topLine = 'top_candidate=none';
        if (is_array($top)) {
            $topLine = sprintf(
                'top_candidate app_id=%s name=%s final_score=%s recommendation=%s',
                (string) ($top['application_id'] ?? '?'),
                (string) ($top['candidate_name'] ?? $top['name'] ?? '?'),
                (string) ($top['final_score'] ?? '?'),
                (string) ($top['recommendation'] ?? '?')
            );
        }

        ai_screening_log(
            'API response metadata'
            . ' job_id=' . $jobId
            . ' candidate_count=' . $candidateCount
            . ' response_job_title=' . $responseJobTitle
            . ' response_open_set_count=' . $openSetCount
            . ' response_embedding_enabled=' . $embeddingLabel
            . ' ' . $topLine
        );
    }
}

if (!function_exists('ai_screening_check_api_health')) {
    function ai_screening_check_api_health(): bool
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $cfg = ai_screening_config();
        $healthUrl = trim((string) ($cfg['health_url'] ?? ''));
        if ($healthUrl === '') {
            return false;
        }

        $ch = curl_init($healthUrl);
        if ($ch === false) {
            return false;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(1, (int) ($cfg['connect_timeout_seconds'] ?? 5)));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response !== false && $httpCode >= 200 && $httpCode < 300;
    }
}

if (!function_exists('ai_screening_call_api')) {
    /**
     * @param array<string, mixed> $payload
     * @return array{
     *   ok: bool,
     *   data: array<string, mixed>|null,
     *   http_code: int,
     *   error: string,
     *   response_body: string
     * }
     */
    function ai_screening_call_api(array $payload): array
    {
        if (!function_exists('curl_init')) {
            return [
                'ok' => false,
                'data' => null,
                'http_code' => 0,
                'error' => 'PHP cURL extension chưa bật.',
                'response_body' => '',
            ];
        }

        $cfg = ai_screening_config();
        $apiUrl = trim((string) ($cfg['api_url'] ?? ''));
        if ($apiUrl === '') {
            return [
                'ok' => false,
                'data' => null,
                'http_code' => 0,
                'error' => 'Chưa cấu hình api_url.',
                'response_body' => '',
            ];
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            return [
                'ok' => false,
                'data' => null,
                'http_code' => 0,
                'error' => 'Cannot encode AI payload: ' . json_last_error_msg(),
                'response_body' => '',
            ];
        }

        $candidateCount = is_array($payload['candidates'] ?? null) ? count($payload['candidates']) : 0;
        $jobId = (int) ($payload['job']['job_id'] ?? 0);
        ai_screening_log("API POST {$apiUrl} job_id={$jobId} candidates={$candidateCount}");

        $debugPrefix = null;
        if (ai_screening_debug_api_enabled()) {
            ai_screening_log_debug_request_sanity($payload);
            $debugPrefix = ai_screening_debug_api_file_prefix($jobId > 0 ? $jobId : 0);
            $requestPath = ai_screening_debug_api_write_file(
                ai_screening_debug_api_dir(),
                $debugPrefix . '-request.json',
                $json . PHP_EOL
            );
            if ($requestPath !== null) {
                ai_screening_log('API debug request file=' . $requestPath);
            }
        }

        $postJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($postJson === false) {
            return [
                'ok' => false,
                'data' => null,
                'http_code' => 0,
                'error' => 'Cannot encode AI payload: ' . json_last_error_msg(),
                'response_body' => '',
            ];
        }

        $ch = curl_init($apiUrl);
        if ($ch === false) {
            return [
                'ok' => false,
                'data' => null,
                'http_code' => 0,
                'error' => 'curl_init failed.',
                'response_body' => '',
            ];
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postJson);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(1, (int) ($cfg['connect_timeout_seconds'] ?? 5)));
        curl_setopt($ch, CURLOPT_TIMEOUT, max(30, (int) ($cfg['api_timeout_seconds'] ?? 180)));

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $body = is_string($response) ? $response : '';

        if ($debugPrefix !== null) {
            $prettyBody = $body;
            $decodedBody = json_decode($body, true);
            if (is_array($decodedBody)) {
                $encoded = json_encode($decodedBody, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                if (is_string($encoded)) {
                    $prettyBody = $encoded;
                }
            }
            $responsePath = ai_screening_debug_api_write_file(
                ai_screening_debug_api_dir(),
                $debugPrefix . '-response.json',
                $prettyBody . PHP_EOL
            );
            if ($responsePath !== null) {
                ai_screening_log('API debug response file=' . $responsePath);
            }
        }

        if ($response === false) {
            ai_screening_log('API cURL failed: ' . $curlError);

            return [
                'ok' => false,
                'data' => null,
                'http_code' => $httpCode,
                'error' => $curlError,
                'response_body' => $body,
            ];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            ai_screening_log('API HTTP ' . $httpCode . ' body=' . substr($body, 0, 2000));

            return [
                'ok' => false,
                'data' => null,
                'http_code' => $httpCode,
                'error' => "HTTP {$httpCode}",
                'response_body' => $body,
            ];
        }

        $result = json_decode($body, true);
        if (!is_array($result)) {
            ai_screening_log('API invalid JSON: ' . json_last_error_msg());

            return [
                'ok' => false,
                'data' => null,
                'http_code' => $httpCode,
                'error' => 'Invalid JSON: ' . json_last_error_msg(),
                'response_body' => $body,
            ];
        }

        ai_screening_log_api_response_metadata($payload, $result);

        return [
            'ok' => true,
            'data' => $result,
            'http_code' => $httpCode,
            'error' => '',
            'response_body' => $body,
        ];
    }
}
