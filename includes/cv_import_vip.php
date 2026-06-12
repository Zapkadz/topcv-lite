<?php

require_once __DIR__ . '/cv_rules.php';
require_once __DIR__ . '/ai_config.php';
require_once __DIR__ . '/cv_import_rules.php';
require_once __DIR__ . '/services/CvParseService.php';

/**
 * VIP import hooks + orchestrate parse sau màn chọn engine.
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
        header('Location: ' . cv_template_picker_url(['from_import' => '1']));
        exit();
    }
}
