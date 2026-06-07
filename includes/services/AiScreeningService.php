<?php
declare(strict_types=1);

require_once __DIR__ . '/../ai_screening_config.php';
require_once __DIR__ . '/../ai_screening_job_text.php';
require_once __DIR__ . '/../ai_screening_runtime.php';
require_once __DIR__ . '/../schema_ai_screening.php';
require_once __DIR__ . '/../schema_applications_cv.php';
require_once __DIR__ . '/ApplicationService.php';
require_once __DIR__ . '/../repositories/AiScreeningRepository.php';

class AiScreeningService
{
    /**
     * @return array{
     *   ok: bool,
     *   message: string,
     *   run_id?: string,
     *   ranked_count?: int,
     *   skipped_count?: int,
     *   runtime_path?: string,
     *   detail?: string
     * }
     */
    public static function runForJob(PDO $conn, int $jobId, int $companyId): array
    {
        if (!ai_screening_config_ready()) {
            return ['ok' => false, 'message' => ai_screening_config_status_message()];
        }

        if (!ai_screening_results_ready($conn)) {
            return ['ok' => false, 'message' => 'Chưa có bảng ai_screening_results. Chạy migration EMP-B.'];
        }

        if (!applications_cv_snapshot_text_ready($conn)) {
            return [
                'ok' => false,
                'message' => 'Chưa có cột cv_snapshot_text. Chạy migrate-phase-emp-b-cv-snapshot-text.php.',
            ];
        }

        $job = ApplicationService::getJobOwnedByCompany($conn, $jobId, $companyId);
        if ($job === null) {
            return ['ok' => false, 'message' => 'Không tìm thấy tin tuyển dụng hoặc bạn không có quyền.'];
        }

        $jdError = ai_screening_job_text_validation_error($job);
        if ($jdError !== '') {
            return ['ok' => false, 'message' => $jdError];
        }

        $jdText = ai_screening_build_job_text($job);
        $applications = ApplicationService::listApplicationsForAiScreening($conn, $jobId, $companyId);
        if ($applications === []) {
            return ['ok' => false, 'message' => 'Chưa có ứng viên nào cho tin này.'];
        }

        try {
            $prepared = self::prepareRuntimeFiles($jobId, $jdText, $applications);
        } catch (Throwable $e) {
            ai_screening_log('prepareRuntimeFiles: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Không thể chuẩn bị file AI: ' . $e->getMessage()];
        }

        if ($prepared['written'] === 0) {
            $skipped = (int) $prepared['skipped'];
            $detail = $skipped > 0
                ? 'Các hồ sơ cũ (PDF) hoặc apply trước migration không có cv_snapshot_text.'
                : null;

            return [
                'ok' => false,
                'message' => 'Không có ứng viên nào có CV text hợp lệ để chạy AI.',
                'detail' => $detail,
                'skipped_count' => $skipped,
            ];
        }

        $cli = ai_screening_run_cli(
            $prepared['jd_path'],
            $prepared['cv_dir'],
            $prepared['output_json']
        );
        if (!$cli['ok']) {
            return [
                'ok' => false,
                'message' => $cli['message'],
                'detail' => $cli['detail'] ?? ai_screening_cli_user_detail($cli['output'] ?? ''),
                'run_id' => $prepared['run_id'],
                'skipped_count' => $prepared['skipped'],
                'runtime_path' => $prepared['run_path'],
            ];
        }

        try {
            $saved = self::saveResultsFromJson(
                $conn,
                $jobId,
                $prepared['output_json'],
                $prepared['run_id'],
                $prepared['app_map']
            );
        } catch (Throwable $e) {
            ai_screening_log('saveResults: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'AI chạy xong nhưng lưu kết quả thất bại: ' . $e->getMessage(),
                'detail' => 'Kiểm tra storage/logs/ai_screening.log.',
                'run_id' => $prepared['run_id'],
                'runtime_path' => $prepared['run_path'],
            ];
        }

        if ($saved === 0) {
            return [
                'ok' => false,
                'message' => 'AI chạy xong nhưng không map được kết quả với ứng viên.',
                'detail' => 'Kiểm tra tên file CV runtime hoặc log AI.',
                'run_id' => $prepared['run_id'],
                'skipped_count' => $prepared['skipped'],
                'runtime_path' => $prepared['run_path'],
            ];
        }

        $msg = 'Đã xếp hạng ' . $saved . ' ứng viên bằng AI.';
        if ($prepared['skipped'] > 0) {
            $msg .= ' (' . $prepared['skipped'] . ' UV bỏ qua vì thiếu CV text)';
        }

        return [
            'ok' => true,
            'message' => $msg,
            'run_id' => $prepared['run_id'],
            'ranked_count' => $saved,
            'skipped_count' => $prepared['skipped'],
            'runtime_path' => $prepared['run_path'],
        ];
    }

    /**
     * @param list<array<string, mixed>> $applications
     * @return array{
     *   run_id: string,
     *   run_path: string,
     *   jd_path: string,
     *   cv_dir: string,
     *   output_json: string,
     *   written: int,
     *   skipped: int,
     *   app_map: array<int, array<string, mixed>>
     * }
     */
    public static function prepareRuntimeFiles(int $jobId, string $jdText, array $applications): array
    {
        $run = ai_screening_make_run_directory($jobId);
        $runPath = $run['path'];
        $cvDir = $runPath . DIRECTORY_SEPARATOR . 'cvs';
        $jdPath = $runPath . DIRECTORY_SEPARATOR . 'jd.txt';
        $outputJson = $runPath . DIRECTORY_SEPARATOR . 'ranking_results.json';

        if (file_put_contents($jdPath, $jdText) === false) {
            throw new RuntimeException('Không ghi được jd.txt');
        }

        $written = 0;
        $skipped = 0;
        $appMap = [];

        foreach ($applications as $app) {
            $appId = (int) ($app['app_id'] ?? 0);
            $candidateId = (int) ($app['candidate_id'] ?? 0);
            $cvText = trim((string) ($app['cv_snapshot_text'] ?? ''));

            if ($appId <= 0 || $candidateId <= 0 || $cvText === '') {
                $skipped++;
                continue;
            }

            $filename = ai_screening_cv_filename($appId, $candidateId);
            $filePath = $cvDir . DIRECTORY_SEPARATOR . $filename;
            if (file_put_contents($filePath, $cvText) === false) {
                $skipped++;
                continue;
            }

            $appMap[$appId] = $app;
            $written++;
        }

        return [
            'run_id' => $run['run_id'],
            'run_path' => $runPath,
            'jd_path' => $jdPath,
            'cv_dir' => $cvDir,
            'output_json' => $outputJson,
            'written' => $written,
            'skipped' => $skipped,
            'app_map' => $appMap,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $appMap
     */
    public static function saveResultsFromJson(
        PDO $conn,
        int $jobId,
        string $jsonPath,
        string $runId,
        array $appMap
    ): int {
        $candidates = ai_screening_parse_ranking_json($jsonPath);
        $saved = 0;

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $sourceFile = (string) ($candidate['source_file'] ?? '');
            $appId = ai_screening_parse_application_id_from_source_file($sourceFile);
            if ($appId === null || !isset($appMap[$appId])) {
                ai_screening_log('Skip candidate — cannot map source_file: ' . $sourceFile);
                continue;
            }

            $app = $appMap[$appId];
            $scores = $candidate['scores'] ?? null;
            $reviewCard = $candidate['review_card'] ?? null;

            AiScreeningRepository::upsert($conn, [
                'job_id' => $jobId,
                'application_id' => $appId,
                'candidate_id' => (int) ($app['candidate_id'] ?? 0),
                'ai_rank' => isset($candidate['rank']) ? (int) $candidate['rank'] : null,
                'final_score' => isset($candidate['final_score']) ? (int) round((float) $candidate['final_score']) : null,
                'recommendation' => isset($candidate['recommendation']) ? (string) $candidate['recommendation'] : null,
                'scores_json' => is_array($scores)
                    ? json_encode($scores, JSON_UNESCAPED_UNICODE)
                    : null,
                'review_card_json' => is_array($reviewCard)
                    ? json_encode($reviewCard, JSON_UNESCAPED_UNICODE)
                    : null,
                'raw_result_json' => json_encode($candidate, JSON_UNESCAPED_UNICODE),
                'run_id' => $runId,
            ]);
            $saved++;
        }

        return $saved;
    }
}
