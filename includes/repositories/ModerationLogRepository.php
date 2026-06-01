<?php
declare(strict_types=1);

require_once __DIR__ . '/../moderation_log.php';

class ModerationLogRepository
{
    public static function insert(
        PDO $conn,
        int $adminId,
        string $entityType,
        int $entityId,
        string $action,
        ?string $note
    ): bool {
        if (!in_array($entityType, moderation_entity_types(), true)
            || !in_array($action, moderation_actions(), true)) {
            return false;
        }

        $stmt = $conn->prepare(
            'INSERT INTO moderation_logs (admin_id, entity_type, entity_id, action, note)
             VALUES (?, ?, ?, ?, ?)'
        );

        return $stmt->execute([$adminId, $entityType, $entityId, $action, $note]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listRecent(PDO $conn, int $limit = 100, ?string $entityType = null): array
    {
        $limit = max(1, min(500, $limit));
        $sql = 'SELECT ml.*, u.fullname AS admin_name
                FROM moderation_logs ml
                JOIN users u ON ml.admin_id = u.id';
        $params = [];

        if ($entityType !== null && $entityType !== '' && in_array($entityType, moderation_entity_types(), true)) {
            $sql .= ' WHERE ml.entity_type = ?';
            $params[] = $entityType;
        }

        $sql .= ' ORDER BY ml.created_at DESC LIMIT ' . $limit;

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listForEntity(PDO $conn, string $entityType, int $entityId, int $limit = 20): array
    {
        if (!in_array($entityType, moderation_entity_types(), true)) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $stmt = $conn->prepare(
            'SELECT ml.*, u.fullname AS admin_name
             FROM moderation_logs ml
             JOIN users u ON ml.admin_id = u.id
             WHERE ml.entity_type = ? AND ml.entity_id = ?
             ORDER BY ml.created_at DESC
             LIMIT ' . $limit
        );
        $stmt->execute([$entityType, $entityId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
