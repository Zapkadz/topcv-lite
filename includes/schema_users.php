<?php
/**
 * Kiểm tra schema users sau migration Phase 2A.
 */

if (!function_exists('users_schema_has_phase2a')) {
    function users_schema_has_phase2a(PDO $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'employer_approval_status'");
            $cached = (bool) $stmt->fetch();
        } catch (Throwable $e) {
            $cached = false;
        }

        return $cached;
    }
}

if (!function_exists('users_schema_migration_hint_html')) {
    function users_schema_migration_hint_html(): string
    {
        $url = '/topcv_lite/docs/migrations/migrate-phase-2a.php';

        return '<div class="alert alert-danger m-4">'
            . '<h5 class="alert-heading">Cần cập nhật cơ sở dữ liệu (Phase 2A)</h5>'
            . '<p class="mb-2">Bảng <code>users</code> chưa có cột <code>employer_approval_status</code>. '
            . 'Code Phase 2A đã được cài nhưng migration chưa chạy trên DB này.</p>'
            . '<p class="mb-0"><strong>Cách 1 (khuyến nghị):</strong> Mở '
            . '<a href="' . htmlspecialchars($url) . '" class="alert-link">migrate-phase-2a.php</a> '
            . 'trên trình duyệt (localhost), sau đó F5 trang này.<br>'
            . '<strong>Cách 2:</strong> Import file <code>docs/migrations/phase-2a-user-status.sql</code> trong phpMyAdmin.</p>'
            . '</div>';
    }
}
