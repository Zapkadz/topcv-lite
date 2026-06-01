<?php

require_once __DIR__ . '/upload_validate.php';

if (!function_exists('cv_avatar_rules')) {
    /**
     * @return array<string, int|float|string>
     */
    function cv_avatar_rules(): array
    {
        return [
            'min_width' => 200,
            'min_height' => 200,
            'max_width' => 2000,
            'max_height' => 2000,
            'max_aspect_ratio' => 1.35,
            'max_bytes' => 2 * 1024 * 1024,
            'type_label' => 'JPG, PNG hoặc WEBP',
        ];
    }
}

if (!function_exists('cv_avatar_validate_dimensions')) {
    function cv_avatar_validate_dimensions(string $tmpPath): array
    {
        $rules = cv_avatar_rules();
        $info = @getimagesize($tmpPath);
        if ($info === false) {
            return ['ok' => false, 'message' => 'Không đọc được kích thước ảnh.'];
        }

        $w = (int) $info[0];
        $h = (int) $info[1];
        if ($w < $rules['min_width'] || $h < $rules['min_height']) {
            return [
                'ok' => false,
                'message' => 'Ảnh đại diện tối thiểu ' . $rules['min_width'] . '×' . $rules['min_height'] . ' px.',
            ];
        }
        if ($w > $rules['max_width'] || $h > $rules['max_height']) {
            return [
                'ok' => false,
                'message' => 'Ảnh đại diện tối đa ' . $rules['max_width'] . '×' . $rules['max_height'] . ' px.',
            ];
        }

        $ratio = max($w, $h) / max(1, min($w, $h));
        if ($ratio > (float) $rules['max_aspect_ratio']) {
            return [
                'ok' => false,
                'message' => 'Nên dùng ảnh chân dung gần vuông (tỉ lệ cạnh không quá ~4:3).',
            ];
        }

        return ['ok' => true, 'message' => ''];
    }
}

if (!function_exists('cv_avatar_is_safe_stored_path')) {
    function cv_avatar_is_safe_stored_path(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        return (bool) preg_match(
            '#^uploads/cv/avatars/cv_avatar_u\d+_\d+\.(jpe?g|png|webp)$#i',
            $path
        );
    }
}

if (!function_exists('cv_avatar_public_url')) {
    function cv_avatar_public_url(?string $path): ?string
    {
        if (!cv_avatar_is_safe_stored_path($path)) {
            return null;
        }
        $base = defined('BASE_URL') ? BASE_URL : '/topcv_lite/';

        return $base . ltrim($path, '/');
    }
}

if (!function_exists('cv_avatar_delete_file')) {
    function cv_avatar_delete_file(?string $path): void
    {
        if (!cv_avatar_is_safe_stored_path($path)) {
            return;
        }
        $full = dirname(__DIR__) . '/' . $path;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}

if (!function_exists('cv_avatar_apply_post')) {
    /**
     * Xử lý upload / xóa ảnh từ form builder.
     *
     * @param array<string, mixed> $post
     * @return array{ok: bool, message: string, path: string}
     */
    function cv_avatar_apply_post(array $post, array $files, int $userId, ?string $currentPath): array
    {
        $path = cv_avatar_is_safe_stored_path($currentPath) ? $currentPath : '';

        if (!empty($post['remove_avatar']) && (string) $post['remove_avatar'] === '1') {
            cv_avatar_delete_file($path);

            return ['ok' => true, 'message' => '', 'path' => ''];
        }

        if (!isset($files['avatar_file']) || ($files['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'message' => '', 'path' => $path];
        }

        $check = upload_validate($files['avatar_file'], 'cv_avatar');
        if (!$check['ok']) {
            return ['ok' => false, 'message' => $check['message'], 'path' => $path];
        }

        $dim = cv_avatar_validate_dimensions((string) $files['avatar_file']['tmp_name']);
        if (!$dim['ok']) {
            return ['ok' => false, 'message' => $dim['message'], 'path' => $path];
        }

        $uploadDir = dirname(__DIR__) . '/uploads/cv/avatars/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return ['ok' => false, 'message' => 'Không tạo được thư mục lưu ảnh.', 'path' => $path];
        }

        $ext = $check['extension'] ?? 'jpg';
        $newName = 'cv_avatar_u' . $userId . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $newName;
        if (!move_uploaded_file($files['avatar_file']['tmp_name'], $dest)) {
            return ['ok' => false, 'message' => 'Không thể lưu ảnh lên server.', 'path' => $path];
        }

        cv_avatar_delete_file($path);

        return ['ok' => true, 'message' => '', 'path' => 'uploads/cv/avatars/' . $newName];
    }
}
