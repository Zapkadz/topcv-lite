<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/SavedJobRepository.php';
require_once __DIR__ . '/../repositories/JobRepository.php';
require_once __DIR__ . '/../schema_saved_jobs.php';

class SavedJobService
{
    public static function resolveCandidateId(PDO $conn, int $userId): ?int
    {
        $stmt = $conn->prepare('SELECT id FROM candidates WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public static function ensureCandidateId(PDO $conn, int $userId): int
    {
        $existing = self::resolveCandidateId($conn, $userId);
        if ($existing !== null) {
            return $existing;
        }

        $stmt = $conn->prepare("INSERT INTO candidates (user_id, title, bio) VALUES (?, ?, ?)");
        $stmt->execute([$userId, 'Ứng viên', '']);
        return (int) $conn->lastInsertId();
    }

    public static function isSaved(PDO $conn, int $candidateId, int $jobId): bool
    {
        if (!saved_jobs_schema_ready($conn)) {
            return false;
        }

        return SavedJobRepository::exists($conn, $candidateId, $jobId);
    }

    /**
     * @return array{ok: bool, saved: bool, message: string}
     */
    public static function toggle(PDO $conn, int $userId, int $jobId): array
    {
        if (!saved_jobs_schema_ready($conn)) {
            return ['ok' => false, 'saved' => false, 'message' => 'Chức năng lưu tin chưa sẵn sàng (migration 2D).'];
        }

        if ($jobId <= 0 || !JobRepository::findById($conn, $jobId)) {
            return ['ok' => false, 'saved' => false, 'message' => 'Tin tuyển dụng không tồn tại.'];
        }

        $candidateId = self::ensureCandidateId($conn, $userId);

        if (SavedJobRepository::exists($conn, $candidateId, $jobId)) {
            SavedJobRepository::delete($conn, $candidateId, $jobId);
            return ['ok' => true, 'saved' => false, 'message' => 'Đã bỏ lưu tin tuyển dụng.'];
        }

        SavedJobRepository::insert($conn, $candidateId, $jobId);

        return ['ok' => true, 'saved' => true, 'message' => 'Đã lưu tin tuyển dụng.'];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public static function listForUser(PDO $conn, int $userId, int $page, int $limit): array
    {
        if (!saved_jobs_schema_ready($conn)) {
            return ['rows' => [], 'total' => 0];
        }

        $candidateId = self::resolveCandidateId($conn, $userId);
        if ($candidateId === null) {
            return ['rows' => [], 'total' => 0];
        }

        $page = max(1, $page);
        $limit = max(1, min(50, $limit));
        $offset = ($page - 1) * $limit;
        $total = SavedJobRepository::countByCandidate($conn, $candidateId);
        $rows = SavedJobRepository::listByCandidate($conn, $candidateId, $limit, $offset);

        return ['rows' => $rows, 'total' => $total];
    }
}
