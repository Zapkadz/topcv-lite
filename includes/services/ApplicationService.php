<?php
declare(strict_types=1);

require_once __DIR__ . '/CvService.php';
require_once __DIR__ . '/../schema_cvs.php';
require_once __DIR__ . '/../schema_applications_cv.php';
require_once __DIR__ . '/../job_rules.php';

class ApplicationService
{
    /**
     * @return array{ok: bool, message: string}
     */
    public static function applyToJob(
        PDO $conn,
        int $userId,
        int $jobId,
        int $cvProfileId,
        string $coverLetter
    ): array {
        if (!cvs_schema_ready($conn)) {
            return ['ok' => false, 'message' => 'Schema CV chưa sẵn sàng. Liên hệ quản trị hoặc chạy migration CV-A.'];
        }

        if (!applications_cv_columns_ready($conn)) {
            return ['ok' => false, 'message' => 'Schema apply CV-C chưa sẵn sàng. Chạy migration CV-C.'];
        }

        if ($cvProfileId <= 0) {
            return ['ok' => false, 'message' => 'Vui lòng chọn CV để nộp.'];
        }

        $stmtJob = $conn->prepare('SELECT id, status, deadline, deleted_at FROM jobs WHERE id = ? LIMIT 1');
        $stmtJob->execute([$jobId]);
        $jobRow = $stmtJob->fetch(PDO::FETCH_ASSOC);
        if (!$jobRow || !job_is_open_for_apply($jobRow)) {
            return ['ok' => false, 'message' => 'Tin tuyển dụng không còn nhận hồ sơ (đã xóa, hết hạn hoặc chưa duyệt).'];
        }

        $candidateId = CvService::ensureCandidateId($conn, $userId);

        $check = $conn->prepare('SELECT id FROM applications WHERE job_id = ? AND candidate_id = ? LIMIT 1');
        $check->execute([$jobId, $candidateId]);
        if ($check->fetch()) {
            return ['ok' => false, 'message' => 'Bạn đã ứng tuyển công việc này rồi!'];
        }

        $snapshot = CvService::snapshotForApply($conn, $userId, $cvProfileId);
        if (!$snapshot['ok']) {
            return ['ok' => false, 'message' => $snapshot['message']];
        }

        try {
            $stmt = $conn->prepare(
                'INSERT INTO applications (job_id, candidate_id, cv_profile_id, cv_snapshot, cv_snapshot_json, cover_letter)
                 VALUES (?, ?, ?, NULL, ?, ?)'
            );
            $stmt->execute([
                $jobId,
                $candidateId,
                $cvProfileId,
                $snapshot['snapshot_json'],
                $coverLetter,
            ]);

            return ['ok' => true, 'message' => 'Ứng tuyển thành công!'];
        } catch (PDOException $e) {
            $isDuplicate = ($e->getCode() === '23000' && isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062);
            if ($isDuplicate) {
                return ['ok' => false, 'message' => 'Bạn đã ứng tuyển công việc này rồi!'];
            }

            return ['ok' => false, 'message' => 'Lỗi hệ thống, vui lòng thử lại.'];
        }
    }

    /**
     * Employer: lấy application thuộc company.
     *
     * @return array<string, mixed>|null
     */
    public static function getApplicationForCompany(PDO $conn, int $appId, int $companyId): ?array
    {
        $stmt = $conn->prepare(
            'SELECT app.id AS app_id, app.job_id, app.candidate_id, app.cv_profile_id,
                    app.cv_snapshot, app.cv_snapshot_json, app.cover_letter, app.status, app.created_at,
                    u.fullname, j.title AS job_title
             FROM applications app
             JOIN jobs j ON app.job_id = j.id
             JOIN candidates cand ON app.candidate_id = cand.id
             JOIN users u ON cand.user_id = u.id
             WHERE app.id = ? AND j.company_id = ?
             LIMIT 1'
        );
        $stmt->execute([$appId, $companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Candidate: xem đơn ứng tuyển của chính mình (snapshot lúc nộp).
     *
     * @return array<string, mixed>|null
     */
    public static function getApplicationForCandidate(PDO $conn, int $appId, int $userId): ?array
    {
        $stmt = $conn->prepare(
            'SELECT app.id AS app_id, app.job_id, app.cv_profile_id,
                    app.cv_snapshot, app.cv_snapshot_json, app.cover_letter, app.status, app.created_at,
                    j.title AS job_title, c.name AS company_name
             FROM applications app
             JOIN jobs j ON app.job_id = j.id
             JOIN companies c ON j.company_id = c.id
             JOIN candidates cand ON app.candidate_id = cand.id
             WHERE app.id = ? AND cand.user_id = ?
             LIMIT 1'
        );
        $stmt->execute([$appId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
