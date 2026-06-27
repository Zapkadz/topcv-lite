<?php

require_once __DIR__ . '/ai_screening_config.php';
require_once __DIR__ . '/ai_screening_api.php';
require_once __DIR__ . '/ai_screening_runtime.php';

if (!function_exists('ai_recommendation_debug_file_prefix')) {
    function ai_recommendation_debug_file_prefix(int $candidateId): string
    {
        return date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '-candidate-' . $candidateId;
    }
}

if (!function_exists('ai_recommendation_log_request_sanity')) {
    /**
     * @param array<string, mixed> $payload
     */
    function ai_recommendation_log_request_sanity(array $payload): void
    {
        $candidate = is_array($payload['candidate'] ?? null) ? $payload['candidate'] : [];
        $cvText = trim((string) ($candidate['cv_text'] ?? ''));
        $hasHtml = (bool) preg_match('/<[^>]+>/', $cvText);
        $jobs = is_array($payload['jobs'] ?? null) ? $payload['jobs'] : [];

        ai_screening_log(
            'Recommend jobs payload sanity'
            . ' candidate_id=' . (int) ($candidate['candidate_id'] ?? 0)
            . ' cv_text_len=' . strlen($cvText)
            . ' cv_text_html=' . ($hasHtml ? 'yes' : 'no')
            . ' jobs_count=' . count($jobs)
        );
    }
}

if (!function_exists('ai_recommendation_fetch_api_health_meta')) {
    /**
     * @return array{ok: bool, phase: string, service: string, http_code: int}
     */
    function ai_recommendation_fetch_api_health_meta(): array
    {
        $empty = ['ok' => false, 'phase' => '', 'service' => '', 'http_code' => 0];
        if (!function_exists('curl_init')) {
            return $empty;
        }

        $cfg = ai_screening_config();
        $healthUrl = trim((string) ($cfg['health_url'] ?? ''));
        if ($healthUrl === '') {
            return $empty;
        }

        $ch = curl_init($healthUrl);
        if ($ch === false) {
            return $empty;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(1, (int) ($cfg['connect_timeout_seconds'] ?? 5)));
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return ['ok' => false, 'phase' => '', 'service' => '', 'http_code' => $httpCode];
        }

        $decoded = json_decode((string) $response, true);

        return [
            'ok' => true,
            'phase' => is_array($decoded) ? trim((string) ($decoded['phase'] ?? '')) : '',
            'service' => is_array($decoded) ? trim((string) ($decoded['service'] ?? '')) : '',
            'http_code' => $httpCode,
        ];
    }
}

if (!function_exists('ai_recommendation_log_response_metadata')) {
    /**
     * @param array<string, mixed> $result
     */
    function ai_recommendation_log_response_metadata(array $result): void
    {
        $topJobs = is_array($result['top_jobs'] ?? null) ? $result['top_jobs'] : [];
        $excludedJobs = is_array($result['excluded_jobs'] ?? null) ? $result['excluded_jobs'] : [];
        $warnings = is_array($result['warnings'] ?? null) ? $result['warnings'] : [];
        $traceId = trim((string) ($result['trace_id'] ?? ''));
        $diagnostics = is_array($result['diagnostics'] ?? null) ? $result['diagnostics'] : [];
        $diagPayload = is_array($diagnostics['payload'] ?? null) ? $diagnostics['payload'] : [];
        $diagJobs = is_array($diagPayload['jobs'] ?? null) ? $diagPayload['jobs'] : [];
        $diagCandidate = is_array($diagPayload['candidate'] ?? null) ? $diagPayload['candidate'] : [];
        $candidateFlags = is_array($diagCandidate['flags'] ?? null) ? $diagCandidate['flags'] : [];
        $flaggedJobsCount = (int) ($diagJobs['flagged_count'] ?? 0);
        $topIds = [];
        foreach ($topJobs as $job) {
            if (is_array($job)) {
                $topIds[] = (string) ($job['job_id'] ?? '?');
            }
        }
        $excludedIds = [];
        foreach ($excludedJobs as $job) {
            if (is_array($job)) {
                $excludedIds[] = (string) ($job['job_id'] ?? '?');
            }
        }
        $lines = [];
        foreach (array_slice($topJobs, 0, 3) as $job) {
            if (!is_array($job)) {
                continue;
            }
            $decisionConfidence = is_array($job['decision_confidence'] ?? null)
                ? ai_screening_format_confidence_summary($job['decision_confidence'])
                : 'none';
            $lines[] = sprintf(
                'job_id=%s fit=%s score=%s confidence=%s',
                (string) ($job['job_id'] ?? '?'),
                (string) ($job['fit_label'] ?? '?'),
                (string) ($job['fit_score'] ?? '?'),
                $decisionConfidence
            );
        }

        $health = ai_recommendation_fetch_api_health_meta();
        $phaseNote = $health['phase'] !== '' ? $health['phase'] : 'unknown';
        if ($health['ok'] && !preg_match('/Phase 3[3-9]/', $phaseNote)) {
            $phaseNote .= ' [WARN: not Phase 33-39]';
        }
        if ($health['ok'] && preg_match('/Phase 3[3-9]/', $phaseNote) && $traceId === '') {
            ai_screening_log('Recommend API WARN: missing trace_id in response while health phase is ' . $phaseNote);
        }
        if ($health['ok'] && preg_match('/Phase 3[3-9]/', $phaseNote) && $diagnostics === []) {
            ai_screening_log('Recommend API WARN: missing diagnostics in response while health phase is ' . $phaseNote);
        }

        ai_screening_log(
            'Recommend jobs response metadata'
            . ' endpoint=recommend-jobs'
            . ' trace_id=' . ($traceId !== '' ? $traceId : 'none')
            . ' api_phase=' . $phaseNote
            . ' api_service=' . ($health['service'] !== '' ? $health['service'] : 'unknown')
            . ' top_jobs_count=' . count($topJobs)
            . ' excluded_jobs_count=' . count($excludedJobs)
            . ' warnings_count=' . count($warnings)
            . ' candidate_payload_flags=' . ($candidateFlags !== [] ? implode(',', array_map('strval', $candidateFlags)) : 'none')
            . ' flagged_jobs_count=' . $flaggedJobsCount
            . ' top_job_ids=' . ($topIds !== [] ? implode(',', $topIds) : 'none')
            . ' excluded_job_ids=' . ($excludedIds !== [] ? implode(',', $excludedIds) : 'none')
            . ' top3=' . ($lines !== [] ? implode('; ', $lines) : 'none')
        );
    }
}

if (!function_exists('ai_recommendation_call_api')) {
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
    function ai_recommendation_call_api(array $payload): array
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
        $apiUrl = trim((string) ($cfg['recommend_jobs_api_url'] ?? ''));
        if ($apiUrl === '') {
            return [
                'ok' => false,
                'data' => null,
                'http_code' => 0,
                'error' => 'Chưa cấu hình recommend_jobs_api_url.',
                'response_body' => '',
            ];
        }

        $candidateId = (int) ($payload['candidate']['candidate_id'] ?? 0);
        $jobsCount = is_array($payload['jobs'] ?? null) ? count($payload['jobs']) : 0;
        $health = ai_recommendation_fetch_api_health_meta();
        $phaseLog = $health['phase'] !== '' ? $health['phase'] : 'unknown';
        if ($health['ok'] && !preg_match('/Phase 3[3-9]/', $phaseLog)) {
            ai_screening_log('Recommend API WARN: health phase is not Phase 33-39: ' . $phaseLog);
        }
        ai_screening_log(
            "API POST {$apiUrl} candidate_id={$candidateId} jobs={$jobsCount}"
            . ' endpoint=recommend-jobs'
            . ' health_phase=' . $phaseLog
        );

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            return [
                'ok' => false,
                'data' => null,
                'http_code' => 0,
                'error' => 'Cannot encode payload: ' . json_last_error_msg(),
                'response_body' => '',
            ];
        }

        $debugPrefix = null;
        if (ai_screening_debug_api_enabled()) {
            ai_recommendation_log_request_sanity($payload);
            $debugPrefix = ai_recommendation_debug_file_prefix($candidateId > 0 ? $candidateId : 0);
            $requestPath = ai_screening_debug_api_write_file(
                ai_screening_debug_api_dir(),
                $debugPrefix . '-recommend-request.json',
                $json . PHP_EOL
            );
            if ($requestPath !== null) {
                ai_screening_log('Recommend debug request file=' . $requestPath);
            }
        }

        $postJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($postJson === false) {
            return [
                'ok' => false,
                'data' => null,
                'http_code' => 0,
                'error' => 'Cannot encode payload: ' . json_last_error_msg(),
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
                $debugPrefix . '-recommend-response.json',
                $prettyBody . PHP_EOL
            );
            if ($responsePath !== null) {
                ai_screening_log('Recommend debug response file=' . $responsePath);
            }
        }

        if ($response === false) {
            ai_screening_log('Recommend API cURL failed: ' . $curlError);

            return [
                'ok' => false,
                'data' => null,
                'http_code' => $httpCode,
                'error' => $curlError,
                'response_body' => $body,
            ];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            ai_screening_log('Recommend API HTTP ' . $httpCode . ' body=' . substr($body, 0, 2000));

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
            ai_screening_log('Recommend API invalid JSON: ' . json_last_error_msg());

            return [
                'ok' => false,
                'data' => null,
                'http_code' => $httpCode,
                'error' => 'Invalid JSON: ' . json_last_error_msg(),
                'response_body' => $body,
            ];
        }

        ai_recommendation_log_response_metadata($result);

        $traceId = trim((string) ($result['trace_id'] ?? ''));
        $traceFiles = ai_screening_trace_api_files('recommend-jobs', $candidateId, $traceId, $json, $body);
        $topJob = is_array($result['top_jobs'][0] ?? null) ? $result['top_jobs'][0] : null;
        $topScore = is_array($topJob) ? (string) ($topJob['fit_score'] ?? '?') : '?';
        $topConfidence = is_array($topJob) && is_array($topJob['decision_confidence'] ?? null)
            ? ai_screening_format_confidence_summary($topJob['decision_confidence'])
            : 'none';
        ai_screening_log(
            'API trace files'
            . ' endpoint=recommend-jobs'
            . ' trace_id=' . ($traceId !== '' ? $traceId : 'none')
            . ' candidate_id=' . $candidateId
            . ' request_file=' . ($traceFiles['request'] ?? 'none')
            . ' response_file=' . ($traceFiles['response'] ?? 'none')
            . ' top_score=' . $topScore
            . ' top_confidence=' . $topConfidence
        );

        return [
            'ok' => true,
            'data' => $result,
            'http_code' => $httpCode,
            'error' => '',
            'response_body' => $body,
        ];
    }
}
