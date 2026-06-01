<?php
declare(strict_types=1);

class SavedJobRepository
{
    public static function exists(PDO $conn, int $candidateId, int $jobId): bool
    {
        $stmt = $conn->prepare(
            'SELECT 1 FROM saved_jobs WHERE candidate_id = ? AND job_id = ? LIMIT 1'
        );
        $stmt->execute([$candidateId, $jobId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function insert(PDO $conn, int $candidateId, int $jobId): bool
    {
        $stmt = $conn->prepare(
            'INSERT INTO saved_jobs (candidate_id, job_id) VALUES (?, ?)'
        );

        try {
            return $stmt->execute([$candidateId, $jobId]);
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return true;
            }
            throw $e;
        }
    }

    public static function delete(PDO $conn, int $candidateId, int $jobId): bool
    {
        $stmt = $conn->prepare(
            'DELETE FROM saved_jobs WHERE candidate_id = ? AND job_id = ?'
        );
        $stmt->execute([$candidateId, $jobId]);

        return $stmt->rowCount() > 0;
    }

    public static function countByCandidate(PDO $conn, int $candidateId): int
    {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM saved_jobs WHERE candidate_id = ?');
        $stmt->execute([$candidateId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listByCandidate(PDO $conn, int $candidateId, int $limit, int $offset): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        $stmt = $conn->prepare(
            "SELECT sj.created_at AS saved_at, j.*, c.name AS company_name, l.name AS city
             FROM saved_jobs sj
             JOIN jobs j ON sj.job_id = j.id
             JOIN companies c ON j.company_id = c.id
             LEFT JOIN locations l ON j.location_id = l.id
             WHERE sj.candidate_id = ?
             ORDER BY sj.created_at DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute([$candidateId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
