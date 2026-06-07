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

    /**
     * Số đơn pending trên các tin eligible hub sàng lọc (EMP-A).
     */
    public static function countPendingForScreeningHub(PDO $conn, int $companyId): int
    {
        if ($companyId <= 0) {
            return 0;
        }

        require_once __DIR__ . '/../employer_screening_rules.php';

        $eligible = employer_screening_sql_pending_eligible('j');
        $stmt = $conn->prepare(
            "SELECT COUNT(*)
             FROM applications app
             INNER JOIN jobs j ON app.job_id = j.id
             WHERE j.company_id = ?
               AND app.status = 'pending'
               AND ({$eligible})"
        );
        $stmt->execute([$companyId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Danh sách tin + số UV cho hub sàng lọc.
     *
     * @return list<array<string, mixed>>
     */
    public static function listScreeningJobs(PDO $conn, int $companyId, string $section): array
    {
        if ($companyId <= 0) {
            return [];
        }

        require_once __DIR__ . '/../employer_screening_rules.php';

        $section = $section === 'expired' ? 'expired' : 'active';
        $whereSection = $section === 'expired'
            ? employer_screening_sql_expired_with_apps('j')
            : employer_screening_sql_active('j');

        $order = employer_screening_order_sql();
        $sql = "SELECT j.id, j.title, j.deadline, j.status, j.created_at,
                       COUNT(app.id) AS total_apps,
                       COALESCE(SUM(CASE WHEN app.status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_apps
                FROM jobs j
                LEFT JOIN applications app ON app.job_id = j.id
                WHERE j.company_id = ?
                  AND {$whereSection}
                GROUP BY j.id, j.title, j.deadline, j.status, j.created_at
                ORDER BY {$order}";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Employer: tin thuộc company, approved, chưa xóa mềm (EMP-A).
     *
     * @return array<string, mixed>|null
     */
    public static function getJobOwnedByCompany(PDO $conn, int $jobId, int $companyId): ?array
    {
        if ($jobId <= 0 || $companyId <= 0) {
            return null;
        }

        $stmt = $conn->prepare(
            'SELECT j.id, j.title, j.deadline, j.status, j.deleted_at, j.company_id, j.created_at
             FROM jobs j
             WHERE j.id = ?
               AND j.company_id = ?
               AND j.status = \'approved\'
               AND ' . job_sql_not_deleted('j') . '
             LIMIT 1'
        );
        $stmt->execute([$jobId, $companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listApplicationsForJob(PDO $conn, int $jobId, int $companyId): array
    {
        if (self::getJobOwnedByCompany($conn, $jobId, $companyId) === null) {
            return [];
        }

        $stmt = $conn->prepare(
            'SELECT app.id AS app_id, app.created_at AS time_apply, app.status,
                    app.cv_snapshot, app.cv_snapshot_json, app.cover_letter,
                    u.fullname, u.email, u.phone
             FROM applications app
             INNER JOIN jobs j ON app.job_id = j.id
             INNER JOIN candidates cand ON app.candidate_id = cand.id
             INNER JOIN users u ON cand.user_id = u.id
             WHERE app.job_id = ?
               AND j.company_id = ?
             ORDER BY
               CASE app.status WHEN \'pending\' THEN 0 ELSE 1 END,
               app.created_at DESC'
        );
        $stmt->execute([$jobId, $companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function updateApplicationStatusForCompany(
        PDO $conn,
        int $appId,
        int $companyId,
        string $status
    ): array {
        $allowed = ['pending', 'viewed', 'interview', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            return ['ok' => false, 'message' => 'Trạng thái không hợp lệ.'];
        }

        if (self::getApplicationForCompany($conn, $appId, $companyId) === null) {
            return ['ok' => false, 'message' => 'Hồ sơ không tồn tại hoặc bạn không có quyền.'];
        }

        $stmt = $conn->prepare('UPDATE applications SET status = ? WHERE id = ?');
        $stmt->execute([$status, $appId]);

        return ['ok' => true, 'message' => 'Đã cập nhật trạng thái hồ sơ!'];
    }
}
