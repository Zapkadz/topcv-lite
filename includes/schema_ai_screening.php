<?php

if (!function_exists('ai_screening_results_ready')) {
    function ai_screening_results_ready(PDO $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $stmt = $conn->query("SHOW TABLES LIKE 'ai_screening_results'");
            $cached = (bool) $stmt->fetch();
        } catch (Throwable $e) {
            $cached = false;
        }

        return $cached;
    }
}

if (!function_exists('ai_screening_migration_hint_html')) {
    function ai_screening_migration_hint_html(): string
    {
        $url = '/topcv_lite/docs/migrations/migrate-phase-emp-b-ai-screening.php';

        return '<div class="alert alert-warning border-0 shadow-sm mb-4">'
            . '<strong><i class="fas fa-database"></i> Chưa có bảng kết quả AI screening</strong>'
            . '<p class="small mb-2">Bảng <code>ai_screening_results</code> chưa tồn tại — không thể lưu xếp hạng AI.</p>'
            . '<p class="small mb-0"><a href="' . htmlspecialchars($url) . '" class="alert-link">'
            . 'migrate-phase-emp-b-ai-screening.php</a></p>'
            . '</div>';
    }
}
