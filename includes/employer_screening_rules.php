<?php

require_once __DIR__ . '/job_rules.php';

/**
 * Quy tắc hub Sàng lọc ứng viên (EMP-A).
 */

if (!function_exists('employer_screening_sql_base')) {
    /** Điều kiện chung: tin approved, chưa xóa mềm. */
    function employer_screening_sql_base(string $jobAlias = 'j'): string
    {
        $j = $jobAlias !== '' ? $jobAlias . '.' : '';

        return "{$j}status = 'approved' AND " . job_sql_not_deleted($jobAlias);
    }
}

if (!function_exists('employer_screening_sql_active')) {
    /** Section 「Đang tuyển」 — còn nhận hồ sơ. */
    function employer_screening_sql_active(string $jobAlias = 'j'): string
    {
        $j = $jobAlias !== '' ? $jobAlias . '.' : '';
        $today = job_today_date();

        return employer_screening_sql_base($jobAlias)
            . " AND ({$j}deadline IS NULL OR {$j}deadline >= " . quote_sql_date($today) . ')';
    }
}

if (!function_exists('employer_screening_sql_expired_with_apps')) {
    /** Section 「Hết hạn — còn CV」 — hết hạn nộp nhưng còn đơn. */
    function employer_screening_sql_expired_with_apps(string $jobAlias = 'j'): string
    {
        $j = $jobAlias !== '' ? $jobAlias . '.' : '';
        $idCol = $jobAlias !== '' ? $jobAlias . '.id' : 'id';
        $today = job_today_date();

        return employer_screening_sql_base($jobAlias)
            . " AND {$j}deadline IS NOT NULL AND {$j}deadline < " . quote_sql_date($today)
            . " AND EXISTS (SELECT 1 FROM applications app_e WHERE app_e.job_id = {$idCol})";
    }
}

if (!function_exists('employer_screening_sql_pending_eligible')) {
    /** Job có thể xuất hiện trên hub (active hoặc expired có đơn). */
    function employer_screening_sql_pending_eligible(string $jobAlias = 'j'): string
    {
        return '(' . employer_screening_sql_active($jobAlias) . ') OR (' . employer_screening_sql_expired_with_apps($jobAlias) . ')';
    }
}

if (!function_exists('quote_sql_date')) {
    function quote_sql_date(string $dateYmd): string
    {
        return "'" . str_replace("'", "''", $dateYmd) . "'";
    }
}

if (!function_exists('employer_screening_order_sql')) {
    function employer_screening_order_sql(): string
    {
        return 'pending_apps DESC, j.deadline IS NULL, j.deadline ASC, j.title ASC';
    }
}

if (!function_exists('employer_screening_format_deadline')) {
    function employer_screening_format_deadline(?string $deadline): string
    {
        if ($deadline === null || trim($deadline) === '') {
            return 'Không giới hạn';
        }

        $ts = strtotime($deadline);
        if ($ts === false) {
            return (string) $deadline;
        }

        return date('d/m/Y', $ts);
    }
}

if (!function_exists('employer_screening_job_badge_html')) {
    function employer_screening_job_badge_html(array $job, string $section): string
    {
        if ($section === 'expired') {
            return '<span class="badge bg-secondary">Hết hạn nộp</span>';
        }

        return '<span class="badge bg-success">Đang nhận hồ sơ</span>';
    }
}

if (!function_exists('employer_application_status_badge_html')) {
    function employer_application_status_badge_html(string $status): string
    {
        return match ($status) {
            'pending' => '<span class="badge rounded-pill bg-warning text-dark">Chờ duyệt</span>',
            'viewed' => '<span class="badge rounded-pill bg-info">Đã xem</span>',
            'interview' => '<span class="badge rounded-pill bg-success">Hẹn PV</span>',
            'rejected' => '<span class="badge rounded-pill bg-secondary">Từ chối</span>',
            default => '<span class="badge rounded-pill bg-light text-dark border">' . htmlspecialchars($status) . '</span>',
        };
    }
}
