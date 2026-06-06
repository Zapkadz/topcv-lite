<?php
/**
 * Schema CV-F — quota GPT import (cột users.cv_gpt_import_uses).
 */

if (!function_exists('users_cv_gpt_quota_ready')) {
    function users_cv_gpt_quota_ready(PDO $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'cv_gpt_import_uses'");
            $cached = (bool) $stmt->fetch();
        } catch (Throwable $e) {
            $cached = false;
        }

        return $cached;
    }
}

if (!function_exists('cv_import_gpt_quota_migration_hint_html')) {
    function cv_import_gpt_quota_migration_hint_html(): string
    {
        $url = '/topcv_lite/docs/migrations/migrate-phase-cv-f-gpt-quota.php';

        return '<div class="alert alert-warning border-0 shadow-sm mb-4">'
            . '<strong><i class="fas fa-database"></i> Quota Chuẩn GPT chưa lưu DB</strong>'
            . '<p class="small mb-2">Cột <code>users.cv_gpt_import_uses</code> chưa có — hệ thống dùng file tạm. '
            . 'Chạy migration để đếm <strong>5 lần/tổng đời</strong> chính xác theo tài khoản.</p>'
            . '<p class="small mb-0"><a href="' . htmlspecialchars($url) . '" class="alert-link">migrate-phase-cv-f-gpt-quota.php</a></p>'
            . '</div>';
    }
}
