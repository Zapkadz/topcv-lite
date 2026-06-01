<?php
declare(strict_types=1);

require_once __DIR__ . '/ModerationLogService.php';

class JobModerationService
{
    public static function approve(PDO $conn, int $jobId, int $adminId): bool
    {
        $stmt = $conn->prepare(
            "UPDATE jobs SET status = 'approved', admin_note = NULL
             WHERE id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$jobId]);
        if ($stmt->rowCount() < 1) {
            return false;
        }

        ModerationLogService::record($conn, $adminId, 'job', $jobId, 'approve', null);

        return true;
    }

    public static function reject(PDO $conn, int $jobId, int $adminId, string $note): bool
    {
        $note = trim($note);
        $stmt = $conn->prepare(
            "UPDATE jobs SET status = 'rejected', admin_note = ?
             WHERE id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$note, $jobId]);
        if ($stmt->rowCount() < 1) {
            return false;
        }

        ModerationLogService::record($conn, $adminId, 'job', $jobId, 'reject', $note !== '' ? $note : null);

        return true;
    }
}
