<?php

require_once __DIR__ . '/ai_config.php';
require_once __DIR__ . '/cv_import_rules.php';
require_once __DIR__ . '/services/CvParseService.php';

/**
 * VIP import + quota GPT Chuẩn (MVP). F5 mở rộng persist DB nếu cần.
 */

if (!function_exists('cv_user_import_is_vip')) {
    /**
     * MVP: luôn false. Sau gắn plan/subscription hoặc cột users.
     */
    function cv_user_import_is_vip(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        return false;
    }
}

if (!function_exists('cv_import_gpt_quota_max_lifetime')) {
    function cv_import_gpt_quota_max_lifetime(): int
    {
        return 5;
    }
}

if (!function_exists('cv_import_gpt_quota_file_path')) {
    function cv_import_gpt_quota_file_path(int $userId): string
    {
        $dir = dirname(__DIR__) . '/uploads/cv/import/quota';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir . DIRECTORY_SEPARATOR . $userId . '.txt';
    }
}

if (!function_exists('cv_import_gpt_quota_used')) {
    function cv_import_gpt_quota_used(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $path = cv_import_gpt_quota_file_path($userId);
        if (!is_file($path)) {
            return 0;
        }

        $raw = trim((string) @file_get_contents($path));

        return max(0, (int) $raw);
    }
}

if (!function_exists('cv_import_gpt_quota_remaining')) {
    function cv_import_gpt_quota_remaining(int $userId): int
    {
        if (cv_user_import_is_vip($userId)) {
            return 999;
        }

        return max(0, cv_import_gpt_quota_max_lifetime() - cv_import_gpt_quota_used($userId));
    }
}

if (!function_exists('cv_import_gpt_quota_check')) {
    /**
     * @return array{ok: bool, message: string, remaining: int, used: int, max: int}
     */
    function cv_import_gpt_quota_check(int $userId): array
    {
        $max = cv_import_gpt_quota_max_lifetime();
        $used = cv_import_gpt_quota_used($userId);
        $remaining = cv_import_gpt_quota_remaining($userId);

        if (cv_user_import_is_vip($userId)) {
            return [
                'ok' => true,
                'message' => '',
                'remaining' => $remaining,
                'used' => $used,
                'max' => $max,
            ];
        }

        if ($used >= $max) {
            return [
                'ok' => false,
                'message' => 'Bạn đã dùng hết ' . $max . ' lần Chuẩn GPT trên tài khoản. '
                    . 'Nâng cấp VIP để không giới hạn hoặc dùng Text-base.',
                'remaining' => 0,
                'used' => $used,
                'max' => $max,
            ];
        }

        return [
            'ok' => true,
            'message' => '',
            'remaining' => $remaining,
            'used' => $used,
            'max' => $max,
        ];
    }
}

if (!function_exists('cv_import_gpt_quota_record')) {
    function cv_import_gpt_quota_record(int $userId): void
    {
        if ($userId <= 0 || cv_user_import_is_vip($userId)) {
            return;
        }

        $used = cv_import_gpt_quota_used($userId) + 1;
        $path = cv_import_gpt_quota_file_path($userId);
        @file_put_contents($path, (string) $used, LOCK_EX);
    }
}

if (!function_exists('cv_import_commit_draft_from_parse')) {
    /**
     * @param array<string, mixed> $parseResult
     */
    function cv_import_commit_draft_from_parse(int $userId, string $attachmentPath, array $parseResult): void
    {
        $_SESSION['cv_import_draft'] = [
            'user_id' => $userId,
            'profile' => $parseResult['profile'] ?? [],
            'children' => $parseResult['children'] ?? [],
            'attachment_path' => $attachmentPath,
            'meta' => is_array($parseResult['meta'] ?? null) ? $parseResult['meta'] : [],
        ];
        unset($_SESSION['cv_import_pending']);
    }
}

if (!function_exists('cv_import_run_parse_and_redirect')) {
    /**
     * @return never
     */
    function cv_import_run_parse_and_redirect(int $userId, string $absolutePath, string $relativePath, string $parseMode): void
    {
        $parseMode = cv_import_normalize_parse_mode_request($parseMode);
        if ($parseMode === 'vision') {
            $quota = cv_import_gpt_quota_check($userId);
            if (!$quota['ok']) {
                $_SESSION['swal_icon'] = 'warning';
                $_SESSION['swal_title'] = $quota['message'];
                header('Location: cv-import-choose.php');
                exit();
            }
        }

        $parseResult = CvParseService::importFromPdfPath($absolutePath, ['parse_mode' => $parseMode]);
        if (!$parseResult['ok']) {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = $parseResult['message'] ?: 'Không phân tích được CV từ PDF.';
            header('Location: cv-import-choose.php');
            exit();
        }

        if ($parseMode === 'vision') {
            cv_import_gpt_quota_record($userId);
        }

        cv_import_commit_draft_from_parse($userId, $relativePath, $parseResult);
        header('Location: cv-builder.php?from_import=1');
        exit();
    }
}
