<?php
/**
 * Quy tắc nghiệp vụ tin tuyển dụng: deadline, hết hạn, soft delete.
 */

if (!function_exists('job_sql_not_deleted')) {
    /** Điều kiện SQL: tin chưa bị xóa mềm (cần cột deleted_at). */
    function job_sql_not_deleted(string $alias = 'j'): string
    {
        $prefix = $alias !== '' ? $alias . '.' : '';

        return "({$prefix}deleted_at IS NULL)";
    }
}

if (!function_exists('job_is_soft_deleted')) {
    function job_is_soft_deleted(array $job): bool
    {
        return !empty($job['deleted_at']);
    }
}

if (!function_exists('job_today_date')) {
    function job_today_date(): string
    {
        return date('Y-m-d');
    }
}

if (!function_exists('job_deadline_past_message')) {
    function job_deadline_past_message(): string
    {
        return 'Hạn nộp hồ sơ phải từ hôm nay trở đi. Vui lòng chọn ngày hôm nay hoặc một ngày sau đó.';
    }
}

if (!function_exists('job_validate_deadline')) {
    /**
     * @return array{ok: bool, message: string}
     */
    function job_validate_deadline(string $deadline): array
    {
        $deadline = trim($deadline);
        $parsed = DateTime::createFromFormat('Y-m-d', $deadline);

        if (!$parsed || $parsed->format('Y-m-d') !== $deadline) {
            return ['ok' => false, 'message' => 'Ngày hạn nộp không hợp lệ.'];
        }

        if ($deadline < job_today_date()) {
            return ['ok' => false, 'message' => job_deadline_past_message()];
        }

        return ['ok' => true, 'message' => ''];
    }
}

if (!function_exists('job_is_expired')) {
    function job_is_expired(?string $deadline): bool
    {
        if ($deadline === null || $deadline === '') {
            return false;
        }

        return $deadline < job_today_date();
    }
}

if (!function_exists('job_is_open_for_apply')) {
    /** Tin được phép nhận hồ sơ ứng tuyển */
    function job_is_open_for_apply(array $job): bool
    {
        if (job_is_soft_deleted($job)) {
            return false;
        }

        return ($job['status'] ?? '') === 'approved' && !job_is_expired($job['deadline'] ?? null);
    }
}

if (!function_exists('job_admin_status_badge_html')) {
  function job_admin_status_badge_html(array $job): string
    {
        if (job_is_soft_deleted($job)) {
            return '<span class="badge bg-dark">Đã xóa (NTD)</span>';
        }

        $status = $job['status'] ?? '';
        $expired = job_is_expired($job['deadline'] ?? null);

        if ($status === 'rejected') {
            return '<span class="badge bg-danger">Đã từ chối</span>';
        }
        if ($expired) {
            return '<span class="badge bg-secondary">Hết hạn</span>';
        }
        if ($status === 'approved') {
            return '<span class="badge bg-success">Đang hiện</span>';
        }
        if ($status === 'pending') {
            return '<span class="badge bg-warning text-dark">Chờ duyệt</span>';
        }
        if ($status === 'hidden') {
            return '<span class="badge bg-dark">Đã ẩn</span>';
        }

        return '<span class="badge bg-light text-dark border">Không rõ</span>';
    }
}

if (!function_exists('job_saved_listing_badge_html')) {
    /** Badge cho tab "Đã lưu" — tin có thể hết hạn / đã xóa nhưng vẫn trong danh sách. */
    function job_saved_listing_badge_html(array $job): string
    {
        if (job_is_soft_deleted($job)) {
            return '<span class="badge bg-dark">Không còn hiệu lực (đã xóa)</span>';
        }

        $status = $job['status'] ?? '';
        if ($status !== 'approved') {
            return '<span class="badge bg-secondary">Không còn hiệu lực</span>';
        }

        if (job_is_expired($job['deadline'] ?? null)) {
            return '<span class="badge bg-secondary">Hết hạn</span>';
        }

        return '<span class="badge bg-success">Còn tuyển</span>';
    }
}
