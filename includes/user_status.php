<?php
/**
 * Hằng số và helper hiển thị trạng thái user (Phase 2A).
 */

if (!function_exists('user_account_statuses')) {
    function user_account_statuses(): array
    {
        return ['active', 'suspended', 'pending_verification'];
    }
}

if (!function_exists('user_employer_approval_statuses')) {
    function user_employer_approval_statuses(): array
    {
        return ['pending', 'approved', 'rejected'];
    }
}

if (!function_exists('user_is_account_active')) {
    function user_is_account_active(array $user): bool
    {
        return ($user['account_status'] ?? 'active') === 'active';
    }
}

if (!function_exists('user_is_employer_approved')) {
    function user_is_employer_approved(array $user): bool
    {
        if (($user['role'] ?? '') !== 'employer') {
            return true;
        }

        return ($user['employer_approval_status'] ?? '') === 'approved';
    }
}

if (!function_exists('user_can_use_employer_panel')) {
    function user_can_use_employer_panel(array $user): bool
    {
        return ($user['role'] ?? '') === 'employer'
            && user_is_account_active($user)
            && user_is_employer_approved($user);
    }
}

if (!function_exists('user_account_status_badge_html')) {
    function user_account_status_badge_html(array $user): string
    {
        $status = $user['account_status'] ?? 'active';

        return match ($status) {
            'active' => '<span class="badge bg-success">Hoạt động</span>',
            'suspended' => '<span class="badge bg-dark">Đã khóa</span>',
            'pending_verification' => '<span class="badge bg-warning text-dark">Chờ xác minh</span>',
            default => '<span class="badge bg-secondary">Không rõ</span>',
        };
    }
}

if (!function_exists('user_employer_approval_badge_html')) {
    function user_employer_approval_badge_html(array $user): string
    {
        if (($user['role'] ?? '') !== 'employer') {
            return '<span class="text-muted">—</span>';
        }

        return match ($user['employer_approval_status'] ?? '') {
            'pending' => '<span class="badge bg-warning text-dark">Chờ duyệt NTD</span>',
            'approved' => '<span class="badge bg-primary">Đã duyệt NTD</span>',
            'rejected' => '<span class="badge bg-danger">Từ chối NTD</span>',
            default => '<span class="badge bg-secondary">Không rõ</span>',
        };
    }
}
