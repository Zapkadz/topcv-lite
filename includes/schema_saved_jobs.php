<?php

if (!function_exists('saved_jobs_schema_ready')) {
    function saved_jobs_schema_ready(PDO $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $stmt = $conn->query("SHOW TABLES LIKE 'saved_jobs'");
            $cached = (bool) $stmt->fetch();
        } catch (Throwable $e) {
            $cached = false;
        }

        return $cached;
    }
}

if (!function_exists('saved_jobs_schema_migration_hint_html')) {
    function saved_jobs_schema_migration_hint_html(): string
    {
        return '<div class="alert alert-warning">Chưa có bảng <code>saved_jobs</code>. '
            . '<a href="/topcv_lite/docs/migrations/migrate-phase-2d.php">Chạy migration Phase 2D</a></div>';
    }
}
