<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/ModerationLogRepository.php';
require_once __DIR__ . '/../schema_moderation.php';

class ModerationLogService
{
    public static function record(
        PDO $conn,
        int $adminId,
        string $entityType,
        int $entityId,
        string $action,
        ?string $note = null
    ): bool {
        if ($adminId <= 0 || $entityId <= 0 || !moderation_schema_ready($conn)) {
            return false;
        }

        $note = $note !== null && trim($note) !== '' ? trim($note) : null;

        return ModerationLogRepository::insert($conn, $adminId, $entityType, $entityId, $action, $note);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listRecent(PDO $conn, int $limit = 100, ?string $entityType = null): array
    {
        if (!moderation_schema_ready($conn)) {
            return [];
        }

        return ModerationLogRepository::listRecent($conn, $limit, $entityType);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listForEntity(PDO $conn, string $entityType, int $entityId, int $limit = 20): array
    {
        if (!moderation_schema_ready($conn)) {
            return [];
        }

        return ModerationLogRepository::listForEntity($conn, $entityType, $entityId, $limit);
    }
}
