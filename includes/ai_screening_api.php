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
        $health = ai_screening_fetch_api_health_meta();
        $phase = $health['phase'] !== '' ? $health['phase'] : 'unknown';
        $traceId = trim((string) ($result['trace_id'] ?? ''));
        $diagnostics = is_array($result['diagnostics'] ?? null) ? $result['diagnostics'] : [];
        $diagPayload = is_array($diagnostics['payload'] ?? null) ? $diagnostics['payload'] : [];
        $diagRuntime = is_array($diagnostics['runtime'] ?? null) ? $diagnostics['runtime'] : [];
        $diagJob = is_array($diagPayload['job'] ?? null) ? $diagPayload['job'] : [];
        $diagCandidates = is_array($diagPayload['candidates'] ?? null) ? $diagPayload['candidates'] : [];
        $jobFlags = is_array($diagJob['flags'] ?? null) ? $diagJob['flags'] : [];
        $candidateFlaggedCount = (int) ($diagCandidates['flagged_count'] ?? 0);
        $rankedCandidateCount = (int) ($diagRuntime['ranked_candidate_count'] ?? 0);

        if ($health['ok'] && !preg_match('/Phase 3[3-9]/', $phase)) {
            ai_screening_log('Screening API WARN: health phase is not Phase 33-39: ' . $phase);
        }
        if ($health['ok'] && preg_match('/Phase 3[3-9]/', $phase) && $traceId === '') {
            ai_screening_log('Screening API WARN: missing trace_id in response while health phase is ' . $phase);
        }
        if ($health['ok'] && preg_match('/Phase 3[3-9]/', $phase) && $diagnostics === []) {
            ai_screening_log('Screening API WARN: missing diagnostics in response while health phase is ' . $phase);
        }

        $jobId = (int) ($payload['job']['job_id'] ?? ($result['job']['job_id'] ?? 0));
        $candidateCount = is_array($payload['candidates'] ?? null) ? count($payload['candidates']) : 0;

        $respJob = is_array($result['job'] ?? null) ? $result['job'] : [];
        $responseJobTitle = trim((string) ($respJob['job_title'] ?? $respJob['title'] ?? ''));

        $openSet = $respJob['open_set_requirements'] ?? [];
        $openSetCount = is_array($openSet) ? count($openSet) : 0;

        $confidenceGuardrails = is_array($respJob['confidence_guardrails'] ?? null)
            ? $respJob['confidence_guardrails']
            : [];
        $promotedRequirements = is_array($respJob['promoted_requirements'] ?? null)
            ? $respJob['promoted_requirements']
            : [];

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
        $topConfidenceLine = 'top_confidence=none';
        if (is_array($top)) {
            $topDecisionConfidence = is_array($top['decision_confidence'] ?? null)
                ? $top['decision_confidence']
                : null;
            $topLine = sprintf(
                'top_candidate app_id=%s name=%s final_score=%s recommendation=%s',
                (string) ($top['application_id'] ?? '?'),
                (string) ($top['candidate_name'] ?? $top['name'] ?? '?'),
                (string) ($top['final_score'] ?? '?'),
                (string) ($top['recommendation'] ?? '?')
            );
            $topConfidenceLine = 'top_confidence=' . ai_screening_format_confidence_summary($topDecisionConfidence);
        }

        ai_screening_log(
            'API response metadata'
            . ' endpoint=screening'
            . ' trace_id=' . ($traceId !== '' ? $traceId : 'none')
            . ' health_phase=' . $phase
            . ' job_id=' . $jobId
            . ' candidate_count=' . $candidateCount
            . ' job_payload_flags=' . ($jobFlags !== [] ? implode(',', array_map('strval', $jobFlags)) : 'none')
            . ' candidate_flagged_count=' . $candidateFlaggedCount
            . ' ranked_candidate_count=' . $rankedCandidateCount
            . ' response_job_title=' . $responseJobTitle
            . ' response_open_set_count=' . $openSetCount
            . ' response_promoted_requirements_count=' . count($promotedRequirements)
            . ' response_confidence_guardrails=' . ($confidenceGuardrails !== [] ? 'yes' : 'no')
            . ' response_embedding_enabled=' . $embeddingLabel
            . ' ' . $topLine
            . ' ' . $topConfidenceLine
        );
    }
}

if (!function_exists('ai_screening_fetch_api_health_meta')) {
    /**
     * @return array{ok: bool, phase: string, service: string, http_code: int}
     */
    function ai_screening_fetch_api_health_meta(): array
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
        $health = ai_screening_fetch_api_health_meta();
        $phase = $health['phase'] !== '' ? $health['phase'] : 'unknown';
        if ($health['ok'] && !preg_match('/Phase 3[3-9]/', $phase)) {
            ai_screening_log('Screening API WARN: health phase is not Phase 33-39: ' . $phase);
        }
        ai_screening_log(
            "API POST {$apiUrl} job_id={$jobId} candidates={$candidateCount}"
            . ' endpoint=screening health_phase=' . $phase
        );

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

        $traceId = trim((string) ($result['trace_id'] ?? ''));
        $traceFiles = ai_screening_trace_api_files('screening', $jobId, $traceId, $json, $body);
        $topCandidate = null;
        foreach (is_array($result['candidates'] ?? null) ? $result['candidates'] : [] as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            if ($topCandidate === null) {
                $topCandidate = $candidate;
                continue;
            }
            $rank = (int) ($candidate['rank'] ?? PHP_INT_MAX);
            $topRank = (int) ($topCandidate['rank'] ?? PHP_INT_MAX);
            if ($rank < $topRank) {
                $topCandidate = $candidate;
            }
        }
        $topScore = is_array($topCandidate) ? (string) ($topCandidate['final_score'] ?? '?') : '?';
        $topConfidence = is_array($topCandidate) && is_array($topCandidate['decision_confidence'] ?? null)
            ? ai_screening_format_confidence_summary($topCandidate['decision_confidence'])
            : 'none';
        ai_screening_log(
            'API trace files'
            . ' endpoint=screening'
            . ' trace_id=' . ($traceId !== '' ? $traceId : 'none')
            . ' job_id=' . $jobId
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

if (!function_exists('ai_screening_trace_api_dir')) {
    function ai_screening_trace_api_dir(): string
    {
        $cfg = ai_screening_config();
        $root = rtrim(trim((string) ($cfg['runtime_dir'] ?? '')), '\\/');
        if ($root === '') {
            $root = rtrim(str_replace('\\', '/', dirname(__DIR__) . '/storage/ai_runtime'), '/');
        }

        return $root . DIRECTORY_SEPARATOR . 'api-traces';
    }
}

if (!function_exists('ai_screening_trace_api_files')) {
    /**
     * Always write request/response JSON for local rerun verification.
     *
     * @return array{request: ?string, response: ?string, prefix: string}
     */
    function ai_screening_trace_api_files(
        string $endpoint,
        int $entityId,
        string $traceId,
        string $requestBody,
        string $responseBody
    ): array {
        $slug = $traceId !== '' ? preg_replace('/[^a-zA-Z0-9_-]+/', '_', $traceId) : bin2hex(random_bytes(3));
        $prefix = date('Ymd-His') . '-' . $slug . '-' . $endpoint . '-entity-' . max(0, $entityId);
        $dir = ai_screening_trace_api_dir();

        $prettyRequest = $requestBody;
        $decodedReq = json_decode($requestBody, true);
        if (is_array($decodedReq)) {
            $encoded = json_encode($decodedReq, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if (is_string($encoded)) {
                $prettyRequest = $encoded;
            }
        }

        $prettyResponse = $responseBody;
        $decodedRes = json_decode($responseBody, true);
        if (is_array($decodedRes)) {
            $encoded = json_encode($decodedRes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if (is_string($encoded)) {
                $prettyResponse = $encoded;
            }
        }

        $requestPath = ai_screening_debug_api_write_file($dir, $prefix . '-request.json', $prettyRequest . PHP_EOL);
        $responsePath = ai_screening_debug_api_write_file($dir, $prefix . '-response.json', $prettyResponse . PHP_EOL);

        return [
            'request' => $requestPath,
            'response' => $responsePath,
            'prefix' => $prefix,
        ];
    }
}

if (!function_exists('ai_screening_format_confidence_summary')) {
    /**
     * @param array<string, mixed>|null $confidence
     */
    function ai_screening_format_confidence_summary(?array $confidence): string
    {
        if ($confidence === null || $confidence === []) {
            return 'none';
        }

        $level = trim((string) ($confidence['level'] ?? ''));
        $review = !empty($confidence['review_required']) ? 'review_required' : 'ok';
        $reasons = is_array($confidence['reason_codes'] ?? null) ? count($confidence['reason_codes']) : 0;

        return 'level=' . ($level !== '' ? $level : '?')
            . ' review=' . $review
            . ' reason_codes=' . $reasons;
    }
}
