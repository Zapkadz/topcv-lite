<?php

/**
 * Helpers phục vụ import PDF → parse → pre-fill form.
 */

if (!function_exists('cv_import_truncate_text')) {
    function cv_import_truncate_text(string $text, int $maxChars = 14000): string
    {
        $t = preg_replace('/\s+/u', ' ', trim($text));
        if (!is_string($t)) {
            $t = '';
        }

        $len = function_exists('mb_strlen') ? mb_strlen($t) : strlen($t);
        if ($len <= $maxChars) {
            return $t;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($t, 0, $maxChars);
        }

        return substr($t, 0, $maxChars);
    }
}

if (!function_exists('cv_import_min_text_len')) {
    /**
     * Ngưỡng tối thiểu để coi là PDF text-based có nội dung thật.
     */
    function cv_import_min_text_len(): int
    {
        return 80;
    }
}

