<?php
declare(strict_types=1);

require_once __DIR__ . '/../ai_screening_config.php';
require_once __DIR__ . '/../ai_job_recommendation_payload.php';
require_once __DIR__ . '/../ai_job_recommendation_api.php';
require_once __DIR__ . '/../job_recommendation_rules.php';
require_once __DIR__ . '/../cv_snapshot_text.php';
require_once __DIR__ . '/../schema_cvs.php';
require_once __DIR__ . '/../repositories/JobRepository.php';
require_once __DIR__ . '/CvService.php';

class JobRecommendationService
{
    /**
     * @return array{ok: bool, message: string, hint: string}
     */
    public static function buildPanelHint(PDO $conn, int $userId, int $cvProfileId): array
    {
        if (!cvs_schema_ready($conn)) {
            return ['ok' => false, 'message' => '', 'hint' => 'Schema CV chưa sẵn sàng. Liên hệ quản trị hoặc chạy migration CV-A.'];
        }

        if (!ai_screening_config_ready()) {
            return ['ok' => false, 'message' => '', 'hint' => ai_screening_config_status_message()];
        }

        $cvs = CvService::listForUser($conn, $userId);
        if ($cvs === []) {
            return ['ok' => false, 'message' => '', 'hint' => 'Bạn chưa có CV online. Hãy tạo CV trước khi dùng AI gợi ý.'];
        }

        if ($cvProfileId <= 0) {
            return ['ok' => false, 'message' => '', 'hint' => 'Vui lòng chọn CV để phân tích.'];
        }

        $loaded = CvService::getFullForUser($conn, $userId, $cvProfileId);
        if (!$loaded['ok'] || !is_array($loaded['data'])) {
            return ['ok' => false, 'message' => '', 'hint' => 'CV không tồn tại hoặc bạn không có quyền.'];
        }

        $cvText = trim(cv_snapshot_text_from_array($loaded['data']));

        $minLen = (int) (ai_screening_config()['recommend_min_cv_text_length'] ?? 150);
        if (strlen($cvText) < $minLen) {
            return [
                'ok' => false,
                'message' => '',
                'hint' => 'CV hiện tại chưa đủ dữ liệu text để AI phân tích. Hãy bổ sung Kỹ năng, Kinh nghiệm và Mục tiêu nghề nghiệp.',
            ];
        }

        if (JobRepository::countOpenForRecommendation($conn) === 0) {
            return ['ok' => false, 'message' => '', 'hint' => 'Chưa có tin tuyển dụng đang mở để gợi ý.'];
        }

        return ['ok' => true, 'message' => '', 'hint' => ''];
    }

    /**
     * @return array{
     *   ok: bool,
     *   message: string,
     *   detail?: string,
     *   trace_id?: string,
     *   diagnostics?: array<string, mixed>,
     *   top_jobs?: list<array<string, mixed>>,
     *   retrieval_stats?: array<string, mixed>
     * }
     */
    public static function runForCandidate(PDO $conn, int $userId, int $cvProfileId): array
    {
        $hint = self::buildPanelHint($conn, $userId, $cvProfileId);
        if (!$hint['ok']) {
            return ['ok' => false, 'message' => $hint['hint']];
        }

        if (!ai_screening_check_api_health()) {
            return [
                'ok' => false,
                'message' => 'Không kết nối được AI service. Vui lòng bật Python API và thử lại.',
                'detail' => 'Chạy: uvicorn api:app --host 127.0.0.1 --port 8000 trong SEMANTIC_SKILLS_RESUME',
            ];
        }

        $candidateId = CvService::ensureCandidateId($conn, $userId);
        $loaded = CvService::getFullForUser($conn, $userId, $cvProfileId);
        if (!$loaded['ok'] || !is_array($loaded['data'])) {
            return ['ok' => false, 'message' => 'Không thể tải CV để phân tích.'];
        }

        $userRow = self::loadUserRow($conn, $userId);
        $candidatePayload = ai_recommendation_build_candidate_payload($candidateId, $loaded['data'], $userRow);
        $candidatePayload['candidate_id'] = $candidateId;

        $jobs = JobRepository::listOpenForRecommendation($conn);
        $jobsPayload = ai_recommendation_build_jobs_payload($jobs);
        if ($jobsPayload === []) {
            return ['ok' => false, 'message' => 'Chưa có tin tuyển dụng hợp lệ để gợi ý.'];
        }

        $requestPayload = ai_recommendation_build_request_payload($candidatePayload, $jobsPayload);
        $api = ai_recommendation_call_api($requestPayload);

        if (!$api['ok'] || !is_array($api['data'])) {
            $detail = trim((string) ($api['error'] ?? ''));
            if ($api['http_code'] === 422 && $api['response_body'] !== '') {
                $decoded = json_decode((string) $api['response_body'], true);
                if (is_array($decoded['detail'] ?? null)) {
                    $parts = [];
                    foreach ($decoded['detail'] as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $loc = is_array($item['loc'] ?? null) ? implode('.', $item['loc']) : '';
                        $msg = trim((string) ($item['msg'] ?? ''));
                        if ($loc !== '' && $msg !== '') {
                            $parts[] = $loc . ': ' . $msg;
                        }
                    }
                    if ($parts !== []) {
                        $detail = implode(' | ', array_slice($parts, 0, 3));
                    }
                }
            }
            if ($detail === '') {
                $detail = substr((string) ($api['response_body'] ?? ''), 0, 500);
            }

            return [
                'ok' => false,
                'message' => 'Không thể chạy AI gợi ý lúc này. Vui lòng thử lại sau.',
                'detail' => $detail,
            ];
        }

        $topJobs = is_array($api['data']['top_jobs'] ?? null) ? $api['data']['top_jobs'] : [];
        $excludedJobs = is_array($api['data']['excluded_jobs'] ?? null) ? $api['data']['excluded_jobs'] : [];
        $topJobs = self::filterEligibleTopJobs($topJobs, $excludedJobs);
        $jobQualityStats = is_array($api['data']['job_quality_stats'] ?? null) ? $api['data']['job_quality_stats'] : [];
        $warnings = is_array($api['data']['warnings'] ?? null) ? $api['data']['warnings'] : [];
        $traceId = trim((string) ($api['data']['trace_id'] ?? ''));
        $diagnostics = is_array($api['data']['diagnostics'] ?? null) ? $api['data']['diagnostics'] : [];
        $diagPayload = is_array($diagnostics['payload'] ?? null) ? $diagnostics['payload'] : [];
        $diagCandidate = is_array($diagPayload['candidate'] ?? null) ? $diagPayload['candidate'] : [];
        $diagJobs = is_array($diagPayload['jobs'] ?? null) ? $diagPayload['jobs'] : [];
        $diagCandidateFlags = is_array($diagCandidate['flags'] ?? null) ? $diagCandidate['flags'] : [];
        $diagJobsWarnings = is_array($diagJobs['warnings'] ?? null) ? $diagJobs['warnings'] : [];

        foreach (array_merge($diagCandidateFlags, $diagJobsWarnings) as $warning) {
            if (!is_string($warning) || trim($warning) === '') {
                continue;
            }
            $warnings[] = trim($warning);
        }
        $warnings = array_values(array_unique($warnings));

        $enrichedTop = self::enrichJobRows($topJobs, $jobs);
        $enrichedExcluded = self::enrichJobRows($excludedJobs, $jobs);
        $appliedJobIds = self::loadAppliedJobIds($conn, $candidateId);
        $profile = is_array($loaded['data']['profile'] ?? null) ? $loaded['data']['profile'] : [];

        $excludedCount = count($enrichedExcluded);
        $eligibleCount = (int) ($jobQualityStats['eligible_jobs'] ?? count($enrichedTop));
        $receivedCount = (int) ($jobQualityStats['jobs_received'] ?? count($jobsPayload));

        if ($enrichedTop === [] && $excludedCount === 0 && $eligibleCount === 0) {
            return [
                'ok' => false,
                'message' => 'AI chưa tìm thấy công việc phù hợp đủ điều kiện. Hãy bổ sung thêm kinh nghiệm và kỹ năng trong CV.',
            ];
        }

        self::saveSessionResult([
            'schema_version' => job_recommendation_session_schema_version(),
            'ran_at' => time(),
            'trace_id' => $traceId,
            'cv_profile_id' => $cvProfileId,
            'cv_title' => trim((string) ($profile['title'] ?? 'CV')),
            'jobs_received' => $receivedCount,
            'top_jobs' => $enrichedTop,
            'excluded_jobs' => $enrichedExcluded,
            'job_quality_stats' => $jobQualityStats,
            'warnings' => $warnings,
            'diagnostics' => $diagnostics,
            'retrieval_stats' => is_array($api['data']['retrieval_stats'] ?? null)
                ? $api['data']['retrieval_stats']
                : [],
            'applied_job_ids' => $appliedJobIds,
        ]);

        if ($enrichedTop === []) {
            $msg = 'Hiện chưa có tin tuyển dụng đủ dữ liệu để AI gợi ý chính xác.';
        } elseif ($excludedCount > 0) {
            $msg = 'Đã gợi ý ' . count($enrichedTop) . ' công việc từ '
                . $receivedCount . ' tin đã phân tích ('
                . $excludedCount . ' tin bị loại vì JD thiếu dữ liệu).';
        } else {
            $msg = 'Đã gợi ý ' . count($enrichedTop) . ' công việc phù hợp từ ' . $receivedCount . ' tin đang tuyển.';
        }

        return [
            'ok' => true,
            'message' => $msg,
            'trace_id' => $traceId,
            'top_jobs' => $enrichedTop,
            'excluded_jobs' => $enrichedExcluded,
            'job_quality_stats' => $jobQualityStats,
            'warnings' => $warnings,
            'diagnostics' => $diagnostics,
            'retrieval_stats' => is_array($api['data']['retrieval_stats'] ?? null)
                ? $api['data']['retrieval_stats']
                : [],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getSessionResult(): ?array
    {
        $key = job_recommendation_session_key();
        $data = $_SESSION[$key] ?? null;

        if (!is_array($data)) {
            return null;
        }

        $version = (int) ($data['schema_version'] ?? 0);
        if ($version < job_recommendation_session_schema_version()) {
            unset($_SESSION[$key]);

            return null;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function saveSessionResult(array $data): void
    {
        $_SESSION[job_recommendation_session_key()] = $data;
    }

    /**
     * @return list<int>
     */
    public static function loadAppliedJobIds(PDO $conn, int $candidateId): array
    {
        if ($candidateId <= 0) {
            return [];
        }

        $stmt = $conn->prepare('SELECT job_id FROM applications WHERE candidate_id = ?');
        $stmt->execute([$candidateId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', is_array($rows) ? $rows : []);
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadUserRow(PDO $conn, int $userId): array
    {
        $stmt = $conn->prepare('SELECT fullname, email, phone FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : [];
    }

    /**
     * Remove placeholder/insufficient jobs from the main recommendation list.
     *
     * @param list<array<string, mixed>> $topJobs
     * @param list<array<string, mixed>> $excludedJobs
     * @return list<array<string, mixed>>
     */
    private static function filterEligibleTopJobs(array $topJobs, array $excludedJobs): array
    {
        $excludedIds = [];
        foreach ($excludedJobs as $row) {
            if (!is_array($row)) {
                continue;
            }
            $jobId = (int) ($row['job_id'] ?? 0);
            if ($jobId > 0) {
                $excludedIds[$jobId] = true;
            }
        }

        $out = [];
        foreach ($topJobs as $row) {
            if (!is_array($row)) {
                continue;
            }

            $jobId = (int) ($row['job_id'] ?? 0);
            if ($jobId <= 0 || isset($excludedIds[$jobId])) {
                continue;
            }

            $quality = is_array($row['job_quality'] ?? null) ? $row['job_quality'] : [];
            $label = strtolower(trim((string) ($quality['quality_label'] ?? '')));
            if ($label === 'insufficient_jd_data') {
                continue;
            }

            $flags = is_array($quality['flags'] ?? null) ? array_map('strval', $quality['flags']) : [];
            $placeholderFlags = ['placeholder_title', 'placeholder_jd', 'placeholder_content', 'placeholder_like_jd'];
            if (array_intersect($flags, $placeholderFlags) !== []) {
                continue;
            }

            $out[] = $row;
        }

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $apiJobs
     * @param list<array<string, mixed>> $dbJobs
     * @return list<array<string, mixed>>
     */
    private static function enrichJobRows(array $apiJobs, array $dbJobs): array
    {
        $jobMap = [];
        foreach ($dbJobs as $job) {
            if (!is_array($job)) {
                continue;
            }
            $jobMap[(int) ($job['id'] ?? 0)] = $job;
        }

        $out = [];
        foreach ($apiJobs as $row) {
            if (!is_array($row)) {
                continue;
            }
            $jobId = (int) ($row['job_id'] ?? 0);
            $db = $jobMap[$jobId] ?? null;
            if (is_array($db)) {
                $row['company_name'] = (string) ($db['company_name'] ?? '');
                $row['city'] = (string) ($db['city'] ?? '');
                $row['salary_range'] = (string) ($db['salary_range'] ?? '');
                $row['deadline'] = (string) ($db['deadline'] ?? '');
            }
            $out[] = $row;
        }

        return $out;
    }
}
