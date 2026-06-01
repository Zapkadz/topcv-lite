<?php

if (!function_exists('moderation_entity_types')) {
    /** @return list<string> */
    function moderation_entity_types(): array
    {
        return ['job', 'employer'];
    }
}

if (!function_exists('moderation_actions')) {
    /** @return list<string> */
    function moderation_actions(): array
    {
        return ['approve', 'reject'];
    }
}

if (!function_exists('moderation_entity_type_label')) {
    function moderation_entity_type_label(string $type): string
    {
        return match ($type) {
            'job' => 'Tin tuyển dụng',
            'employer' => 'Nhà tuyển dụng',
            default => $type,
        };
    }
}

if (!function_exists('moderation_action_label')) {
    function moderation_action_label(string $action): string
    {
        return match ($action) {
            'approve' => 'Duyệt',
            'reject' => 'Từ chối',
            default => $action,
        };
    }
}

if (!function_exists('moderation_action_badge_html')) {
    function moderation_action_badge_html(string $action): string
    {
        if ($action === 'approve') {
            return '<span class="badge bg-success">Duyệt</span>';
        }
        if ($action === 'reject') {
            return '<span class="badge bg-danger">Từ chối</span>';
        }

        return '<span class="badge bg-secondary">' . htmlspecialchars($action) . '</span>';
    }
}
