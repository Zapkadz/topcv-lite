<?php
/**
 * Validate upload file — extension + MIME (finfo) + size.
 * $kind: 'cv' | 'cv_pdf_import' | 'image' | 'cv_avatar'
 *
 * @return array{ok: bool, message: string, extension?: string}
 */
if (!function_exists('upload_validate')) {
    function upload_validate(array $file, string $kind): array
    {
        $rules = [
            'cv' => [
                'extensions' => ['pdf', 'doc', 'docx'],
                'mimes' => [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ],
                'max_bytes' => 5 * 1024 * 1024,
                'type_label' => 'PDF, DOC hoặc DOCX',
            ],
            'cv_pdf_import' => [
                'extensions' => ['pdf'],
                'mimes' => [
                    'application/pdf',
                ],
                'max_bytes' => 5 * 1024 * 1024,
                'type_label' => 'PDF',
            ],
            'image' => [
                'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                'mimes' => [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ],
                'max_bytes' => 2 * 1024 * 1024,
                'type_label' => 'JPG, PNG hoặc WEBP',
            ],
            'cv_avatar' => [
                'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                'mimes' => [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ],
                'max_bytes' => 2 * 1024 * 1024,
                'type_label' => 'ảnh JPG, PNG hoặc WEBP',
            ],
        ];

        if (!isset($rules[$kind])) {
            return ['ok' => false, 'message' => 'Cấu hình upload không hợp lệ.'];
        }

        $rule = $rules[$kind];

        if (!isset($file['error'])) {
            return ['ok' => false, 'message' => 'Không nhận được file tải lên.'];
        }

        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'message' => 'Vui lòng chọn file để tải lên.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                $maxMb = (int) round($rule['max_bytes'] / (1024 * 1024));
                return ['ok' => false, 'message' => "File vượt quá dung lượng cho phép (tối đa {$maxMb}MB)."];
            }
            return ['ok' => false, 'message' => 'Lỗi khi tải file lên, vui lòng thử lại.'];
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'message' => 'File không đúng định dạng hoặc bị hỏng.'];
        }

        $size = isset($file['size']) ? (int) $file['size'] : 0;
        if ($size <= 0 || $size > $rule['max_bytes']) {
            $maxMb = (int) round($rule['max_bytes'] / (1024 * 1024));
            return [
                'ok' => false,
                'message' => "Chỉ chấp nhận {$rule['type_label']} (tối đa {$maxMb}MB).",
            ];
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, $rule['extensions'], true)) {
            $maxMb = (int) round($rule['max_bytes'] / (1024 * 1024));
            return [
                'ok' => false,
                'message' => "Chỉ chấp nhận {$rule['type_label']} (tối đa {$maxMb}MB).",
            ];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if ($mime === false || !in_array($mime, $rule['mimes'], true)) {
            return ['ok' => false, 'message' => 'File không đúng định dạng hoặc bị hỏng.'];
        }

        return ['ok' => true, 'message' => '', 'extension' => $ext];
    }
}
