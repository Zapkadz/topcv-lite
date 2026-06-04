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

if (!function_exists('cvs_extended_sections_ready')) {
    function cvs_extended_sections_ready(PDO $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        if (!cvs_schema_ready($conn)) {
            $cached = false;

            return $cached;
        }
        try {
            $tables = ['cv_activities', 'cv_certificates', 'cv_awards', 'cv_references'];
            foreach ($tables as $table) {
                $stmt = $conn->query("SHOW TABLES LIKE '{$table}'");
                if (!$stmt->fetch()) {
                    $cached = false;

                    return $cached;
                }
            }
            $cached = true;
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
            . '<a href="/topcv_lite/docs/migrations/migrate-phase-cv-a.php">Chạy migration CV-A</a> '
            . '(sau đó <a href="/topcv_lite/docs/migrations/migrate-phase-cv-b-formats.php">CV-B formats</a> nếu DB cũ).</div>';
    }
}

if (!function_exists('cvs_extended_migration_hint_html')) {
    function cvs_extended_migration_hint_html(): string
    {
        return '<div class="alert alert-warning">Chưa có bảng section mở rộng (CV-D). '
            . '<a href="/topcv_lite/docs/migrations/migrate-phase-cv-d.php">Chạy migration CV-D</a></div>';
    }
}
