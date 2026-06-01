<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_jobs.php';

function jobs_column_exists(PDO $conn, string $column): bool
{
    $stmt = $conn->prepare('SHOW COLUMNS FROM jobs LIKE ?');
    $stmt->execute([$column]);

    return (bool) $stmt->fetch();
}

function jobs_index_exists(PDO $conn, string $index): bool
{
    $stmt = $conn->prepare('SHOW INDEX FROM jobs WHERE Key_name = ?');
    $stmt->execute([$index]);

    return (bool) $stmt->fetch();
}

if (jobs_schema_has_soft_delete($conn) && jobs_index_exists($conn, 'idx_jobs_deleted_at')) {
    echo "SKIP — Phase 2B đã có (deleted_at + index).\n";
    exit(0);
}

try {
    if (!jobs_column_exists($conn, 'deleted_at')) {
        $conn->exec('ALTER TABLE `jobs` ADD COLUMN `deleted_at` datetime NULL DEFAULT NULL');
        echo "OK — added jobs.deleted_at\n";
    }

    if (!jobs_index_exists($conn, 'idx_jobs_deleted_at')) {
        $conn->exec('CREATE INDEX `idx_jobs_deleted_at` ON `jobs` (`deleted_at`)');
        echo "OK — created idx_jobs_deleted_at\n";
    }

    echo "OK — Phase 2B job soft delete migration done.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
}
