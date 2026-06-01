<?php

if (!function_exists('moderation_schema_ready')) {
    function moderation_schema_ready(PDO $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $stmt = $conn->query("SHOW TABLES LIKE 'moderation_logs'");
            $cached = (bool) $stmt->fetch();
        } catch (Throwable $e) {
            $cached = false;
        }

        return $cached;
    }
}

if (!function_exists('moderation_schema_migration_hint_html')) {
    function moderation_schema_migration_hint_html(): string
    {
        return '<div class="alert alert-warning">Chưa có bảng <code>moderation_logs</code>. '
            . '<a href="/topcv_lite/docs/migrations/migrate-phase-2c.php">Chạy migration Phase 2C</a></div>';
    }
}
