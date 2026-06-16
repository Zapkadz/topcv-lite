<?php
declare(strict_types=1);

require_once __DIR__ . '/../job_rules.php';

class JobRepository
{
    public static function findById(PDO $conn, int $id): ?array
    {
        $stmt = $conn->prepare('SELECT * FROM jobs WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function findByIdForCompany(PDO $conn, int $jobId, int $companyId): ?array
    {
        $stmt = $conn->prepare('SELECT * FROM jobs WHERE id = ? AND company_id = ? LIMIT 1');
        $stmt->execute([$jobId, $companyId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function findPublicById(PDO $conn, int $id): ?array
    {
        $stmt = $conn->prepare(
            "SELECT j.*, c.name AS company_name, c.logo, c.address AS company_address, l.name AS city
             FROM jobs j
             JOIN companies c ON j.company_id = c.id
             JOIN locations l ON j.location_id = l.id
             WHERE j.id = ? AND j.status = 'approved' AND " . job_sql_not_deleted('j') . '
             LIMIT 1'
        );
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function softDelete(PDO $conn, int $jobId, int $companyId): bool
    {
        $stmt = $conn->prepare(
            'UPDATE jobs SET deleted_at = NOW()
             WHERE id = ? AND company_id = ? AND deleted_at IS NULL'
        );

        $stmt->execute([$jobId, $companyId]);

        return $stmt->rowCount() > 0;
    }

    public static function restore(PDO $conn, int $jobId, int $companyId): bool
    {
        $stmt = $conn->prepare(
            'UPDATE jobs SET deleted_at = NULL
             WHERE id = ? AND company_id = ? AND deleted_at IS NOT NULL'
        );

        $stmt->execute([$jobId, $companyId]);

        return $stmt->rowCount() > 0;
    }

    public static function countByCompany(PDO $conn, int $companyId, bool $deletedOnly): int
    {
        $clause = $deletedOnly ? 'deleted_at IS NOT NULL' : 'deleted_at IS NULL';
        $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE company_id = ? AND {$clause}");
        $stmt->execute([$companyId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listByCompany(
        PDO $conn,
        int $companyId,
        bool $deletedOnly,
        int $limit,
        int $offset
    ): array {
        $clause = $deletedOnly ? 'deleted_at IS NOT NULL' : 'deleted_at IS NULL';
        $sql = "SELECT * FROM jobs
                WHERE company_id = ? AND {$clause}
                ORDER BY created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$companyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tin public còn hạn — dùng cho AI gợi ý việc làm (Phase 23).
     *
     * @return list<array<string, mixed>>
     */
    public static function listOpenForRecommendation(PDO $conn): array
    {
        $today = job_today_date();
        $sql = "SELECT j.*, c.name AS company_name, c.logo, l.name AS city
                FROM jobs j
                JOIN companies c ON j.company_id = c.id
                JOIN locations l ON j.location_id = l.id
                WHERE j.status = 'approved'
                  AND (j.deadline IS NULL OR j.deadline >= ?)
                  AND " . job_sql_not_deleted('j') . '
                ORDER BY j.created_at DESC';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$today]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public static function countOpenForRecommendation(PDO $conn): int
    {
        $today = job_today_date();
        $sql = 'SELECT COUNT(*)
                FROM jobs j
                WHERE j.status = \'approved\'
                  AND (j.deadline IS NULL OR j.deadline >= ?)
                  AND ' . job_sql_not_deleted('j');
        $stmt = $conn->prepare($sql);
        $stmt->execute([$today]);

        return (int) $stmt->fetchColumn();
    }
}
