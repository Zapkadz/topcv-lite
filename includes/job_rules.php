<?php
/**
 * Quy tắc nghiệp vụ tin tuyển dụng: deadline, hết hạn.
 */

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
        return ($job['status'] ?? '') === 'approved' && !job_is_expired($job['deadline'] ?? null);
    }
}

if (!function_exists('job_admin_status_badge_html')) {
  function job_admin_status_badge_html(array $job): string
    {
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
