<?php

if (!function_exists('applications_cv_columns_ready')) {
    function applications_cv_columns_ready(PDO $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $stmt = $conn->query("SHOW COLUMNS FROM applications LIKE 'cv_profile_id'");
            if (!$stmt->fetch()) {
                $cached = false;

                return $cached;
            }
            $stmt = $conn->query("SHOW COLUMNS FROM applications LIKE 'cv_snapshot_json'");
            $cached = (bool) $stmt->fetch();
        } catch (Throwable $e) {
            $cached = false;
        }

        return $cached;
    }
}

if (!function_exists('applications_cv_snapshot_text_ready')) {
    function applications_cv_snapshot_text_ready(PDO $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $stmt = $conn->query("SHOW COLUMNS FROM applications LIKE 'cv_snapshot_text'");
            $cached = (bool) $stmt->fetch();
        } catch (Throwable $e) {
            $cached = false;
        }

        return $cached;
    }
}

if (!function_exists('applications_cv_migration_hint_html')) {
    function applications_cv_migration_hint_html(): string
    {
        return '<div class="alert alert-warning">Chưa có cột snapshot CV trên bảng applications. '
            . '<a href="/topcv_lite/docs/migrations/migrate-phase-cv-c.php">Chạy migration CV-C</a></div>';
    }
}
