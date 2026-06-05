<?php

/**
 * Làm sạch text thô từ pdfparser trước khi gửi AI / fallback.
 * PDF thiết kế (Canva, template) thường có chữ lặp/chồng layer.
 */

if (!function_exists('cv_import_section_header_patterns')) {
    /**
     * @return list<string>
     */
    function cv_import_section_header_patterns(): array
    {
        return [
            'VỀ TÔI', 'LIÊN HỆ', 'HỌC VẤN', 'KINH NGHIỆM', 'KỸ NĂNG', 'KỸ NĂNG MỀM', 'KỸ NĂNG CỨNG',
            'DỰ ÁN', 'HOẠT ĐỘNG', 'CHỨNG CHỈ', 'GIẢI THƯỞNG', 'NGƯỜI GIỚI THIỆU', 'MỤC TIÊU',
            'MỤC TIÊU NGHỀ NGHIỆP', 'SỞ THÍCH', 'THÔNG TIN CÁ NHÂN', 'TRÌNH ĐỘ',
            'ABOUT', 'CONTACT', 'EDUCATION', 'EXPERIENCE', 'SKILLS', 'PROJECTS', 'ACTIVITIES',
            'CERTIFICATES', 'AWARDS', 'REFERENCES', 'OBJECTIVE', 'SUMMARY', 'PROFILE',
        ];
    }
}

if (!function_exists('cv_import_dedup_contiguous_repeats')) {
    function cv_import_dedup_contiguous_repeats(string $text, int $minLen = 3, int $maxLen = 80): string
    {
        $prev = '';
        $guard = 0;
        while ($prev !== $text && $guard < 12) {
            $prev = $text;
            $pattern = '/(.{' . $minLen . ',' . $maxLen . '}?)\1+/u';
            $text = preg_replace($pattern, '$1', $text);
            if (!is_string($text)) {
                return $prev;
            }
            $guard++;
        }

        return $text;
    }
}

if (!function_exists('cv_import_dedup_adjacent_patterns')) {
    function cv_import_dedup_adjacent_patterns(string $text): string
    {
        $patterns = [
            '/([a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,})(?:\s*\1)+/iu',
            '/(https?:\/\/[^\s]+)(?:\s*\1)+/iu',
            '/(\b0[0-9]{9}\b)(?:\s*\1)+/u',
            '/(\b(?:19|20)\d{2}\s*[-–—]\s*(?:19|20)\d{2}\b)(?:\s*\1)+/u',
            '/(\b(?:19|20)\d{2}\b)(?:\s*\1){2,}/u',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '$1', $text);
            if (!is_string($text)) {
                return '';
            }
        }

        return $text;
    }
}

if (!function_exists('cv_import_fix_glued_date_ranges')) {
    function cv_import_fix_glued_date_ranges(string $text): string
    {
        // "2024 - 20282024 - 2028" → "2024 - 2028"
        $text = preg_replace(
            '/(\d{4})\s*[-–—]\s*(\d{4})(\d{4})\s*[-–—]\s*(\d{4})/u',
            '$1 - $4',
            $text
        );

        // "2024 - 20282028" → "2024 - 2028"
        $text = preg_replace(
            '/(\d{4})\s*[-–—]\s*(\d{4})(\d{4})/u',
            '$1 - $3',
            $text
        );

        return is_string($text) ? $text : '';
    }
}

if (!function_exists('cv_import_insert_section_breaks')) {
    function cv_import_insert_section_breaks(string $text): string
    {
        foreach (cv_import_section_header_patterns() as $header) {
            $quoted = preg_quote($header, '/');
            $text = preg_replace('/(?<!\n)\s*(' . $quoted . ')(?=\s|$)/iu', "\n\n$1", $text);
            if (!is_string($text)) {
                return '';
            }
        }

        return $text;
    }
}

if (!function_exists('cv_import_split_glued_words')) {
    function cv_import_split_glued_words(string $text): string
    {
        // "HoàngTú" → "Hoàng Tú" (chữ thường/ số rồi tới chữ hoa)
        $text = preg_replace('/([\p{Ll}\d])(\p{Lu})/u', '$1 $2', $text);

        return is_string($text) ? $text : '';
    }
}

if (!function_exists('cv_import_normalize_extracted_whitespace')) {
    function cv_import_normalize_extracted_whitespace(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
        $text = preg_replace('/ *\| */u', ' | ', $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);

        return trim((string) $text);
    }
}

if (!function_exists('cv_import_text_noise_score')) {
    /**
     * 0 = sạch, 1 = rất nhiễu (nhiều text bị gỡ khi clean).
     */
    function cv_import_text_noise_score(int $rawLen, int $cleanLen): float
    {
        if ($rawLen <= 0) {
            return 0.0;
        }

        $reduction = max(0, $rawLen - $cleanLen) / $rawLen;

        return round(min(1.0, $reduction * 1.4), 3);
    }
}

if (!function_exists('cv_import_clean_extracted_text')) {
    /**
     * @return array{
     *   text: string,
     *   raw_len: int,
     *   clean_len: int,
     *   noise_score: float,
     *   steps: list<string>
     * }
     */
    function cv_import_clean_extracted_text(string $text): array
    {
        $rawLen = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
        $steps = [];

        $text = cv_import_normalize_extracted_whitespace($text);
        $steps[] = 'whitespace';

        $before = $text;
        $text = cv_import_dedup_adjacent_patterns($text);
        if ($text !== $before) {
            $steps[] = 'dedup_patterns';
        }

        $before = $text;
        $text = cv_import_fix_glued_date_ranges($text);
        if ($text !== $before) {
            $steps[] = 'date_ranges';
        }

        $before = $text;
        $text = cv_import_dedup_contiguous_repeats($text);
        if ($text !== $before) {
            $steps[] = 'dedup_repeats';
        }

        $before = $text;
        $text = cv_import_split_glued_words($text);
        if ($text !== $before) {
            $steps[] = 'split_glued_words';
        }

        $before = $text;
        $text = cv_import_insert_section_breaks($text);
        if ($text !== $before) {
            $steps[] = 'section_breaks';
        }

        $text = cv_import_normalize_extracted_whitespace($text);
        $cleanLen = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);

        return [
            'text' => $text,
            'raw_len' => $rawLen,
            'clean_len' => $cleanLen,
            'noise_score' => cv_import_text_noise_score($rawLen, $cleanLen),
            'steps' => $steps,
        ];
    }
}
