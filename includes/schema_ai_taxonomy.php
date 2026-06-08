<?php

if (!function_exists('ai_taxonomy_schema_ready')) {
    function ai_taxonomy_schema_ready(PDO $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $tables = ['ai_taxonomy_suggestions', 'ai_custom_taxonomy_skills', 'ai_taxonomy_audit_logs'];
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

if (!function_exists('ai_taxonomy_migration_hint_html')) {
    function ai_taxonomy_migration_hint_html(): string
    {
        $url = '/topcv_lite/docs/migrations/migrate-phase-admin-taxonomy.php';

        return '<div class="alert alert-warning border-0 shadow-sm mb-4">'
            . '<strong><i class="fas fa-database"></i> Chưa có schema taxonomy AI</strong>'
            . '<p class="small mb-2">Cần chạy migration trước khi dùng quản lý taxonomy suggestions.</p>'
            . '<p class="small mb-0"><a href="' . htmlspecialchars($url) . '" class="alert-link">'
            . 'migrate-phase-admin-taxonomy.php</a></p>'
            . '</div>';
    }
}

if (!function_exists('ai_taxonomy_status_label')) {
    function ai_taxonomy_status_label(string $status): string
    {
        return match ($status) {
            'pending_review' => 'Chờ duyệt',
            'approved_new_skill' => 'Skill mới',
            'approved_alias' => 'Đã thêm alias',
            'merged' => 'Đã gộp',
            'rejected' => 'Từ chối',
            default => $status,
        };
    }
}

if (!function_exists('ai_taxonomy_status_badge_class')) {
    function ai_taxonomy_status_badge_class(string $status): string
    {
        return match ($status) {
            'pending_review' => 'bg-warning text-dark',
            'approved_new_skill' => 'bg-success',
            'approved_alias' => 'bg-info text-dark',
            'merged' => 'bg-primary',
            'rejected' => 'bg-secondary',
            default => 'bg-light text-dark',
        };
    }
}
