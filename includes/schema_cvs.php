<?php

if (!function_exists('cvs_schema_ready')) {
    function cvs_schema_ready(PDO $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $stmt = $conn->query("SHOW TABLES LIKE 'cv_profiles'");
            $cached = (bool) $stmt->fetch();
        } catch (Throwable $e) {
            $cached = false;
        }

        return $cached;
    }
}

if (!function_exists('cvs_schema_migration_hint_html')) {
    function cvs_schema_migration_hint_html(): string
    {
        return '<div class="alert alert-warning">Chưa có bảng CV structured. '
            . '<a href="/topcv_lite/docs/migrations/migrate-phase-cv-a.php">Chạy migration CV-A</a></div>';
    }
}
