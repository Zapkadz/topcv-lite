<?php

require_once __DIR__ . '/ai_screening_config.php';
require_once __DIR__ . '/ai_screening_runtime.php';

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

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
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
        $jobId = $payload['job']['job_id'] ?? '?';
        ai_screening_log("API POST {$apiUrl} job_id={$jobId} candidates={$candidateCount}");

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
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(1, (int) ($cfg['connect_timeout_seconds'] ?? 5)));
        curl_setopt($ch, CURLOPT_TIMEOUT, max(30, (int) ($cfg['api_timeout_seconds'] ?? 180)));

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $body = is_string($response) ? $response : '';

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

        return [
            'ok' => true,
            'data' => $result,
            'http_code' => $httpCode,
            'error' => '',
            'response_body' => $body,
        ];
    }
}
