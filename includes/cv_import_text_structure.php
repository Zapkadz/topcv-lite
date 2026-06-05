<?php

/**
 * Cấu trúc hóa text đã clean thành các block có ranh giới rõ — giảm AI gán nhầm mô tả.
 */

if (!function_exists('cv_import_repeatable_section_labels')) {
    /**
     * Section có nhiều mục (học vấn, kinh nghiệm...) — tách theo mốc thời gian.
     *
     * @return array<string, true>
     */
    function cv_import_repeatable_section_labels(): array
    {
        return [
            'HỌC VẤN' => true,
            'EDUCATION' => true,
            'KINH NGHIỆM' => true,
            'EXPERIENCE' => true,
            'DỰ ÁN' => true,
            'PROJECTS' => true,
            'HOẠT ĐỘNG' => true,
            'ACTIVITIES' => true,
            'CHỨNG CHỈ' => true,
            'CERTIFICATES' => true,
            'GIẢI THƯỞNG' => true,
            'AWARDS' => true,
        ];
    }
}

if (!function_exists('cv_import_extract_date_range_prefix')) {
    /**
     * @return array{range: string, body: string}
     */
    function cv_import_extract_date_range_prefix(string $chunk): array
    {
        $chunk = trim($chunk);
        $patterns = [
            '/^((?:T?\d{1,2}\/\d{4}|\d{4})\s*[-–—]\s*(?:T?\d{1,2}\/\d{4}|\d{4}|nay|hiện tại|hiện nay|present|current))/iu',
            '/^(\d{4}\s*[-–—]\s*\d{4})/u',
            '/^(\d{4})/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $chunk, $m)) {
                $range = trim((string) $m[1]);
                $body = trim((string) mb_substr($chunk, mb_strlen($range)));

                return ['range' => $range, 'body' => $body];
            }
        }

        return ['range' => '', 'body' => $chunk];
    }
}

if (!function_exists('cv_import_split_section_items')) {
    /**
     * Tách nội dung section thành các mục — ưu tiên mốc khoảng thời gian.
     *
     * @return list<string>
     */
    function cv_import_split_section_items(string $body): array
    {
        $body = trim($body);
        if ($body === '') {
            return [];
        }

        $splitPattern = '/(?=(?:T?\d{1,2}\/\d{4}|\d{4})\s*[-–—]\s*(?:T?\d{1,2}\/\d{4}|\d{4}|nay|hiện tại|hiện nay|present|current))/iu';
        $parts = preg_split($splitPattern, $body, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts)) {
            return [$body];
        }

        $items = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $items[] = $part;
            }
        }

        if (count($items) >= 2) {
            return $items;
        }

        // Fallback: tách theo dấu | hoặc xuống dòng kép (layout một số template)
        $alt = preg_split('/\s*\|\s*|\n{2,}/u', $body, -1, PREG_SPLIT_NO_EMPTY);
        if (is_array($alt) && count($alt) >= 2) {
            $altItems = [];
            foreach ($alt as $piece) {
                $piece = trim((string) $piece);
                if ($piece !== '' && mb_strlen($piece) >= 8) {
                    $altItems[] = $piece;
                }
            }
            if (count($altItems) >= 2) {
                return $altItems;
            }
        }

        return [$body];
    }
}

if (!function_exists('cv_import_format_repeatable_section')) {
    function cv_import_format_repeatable_section(string $label, string $body): string
    {
        $items = cv_import_split_section_items($body);
        $out = '=== ' . mb_strtoupper($label) . " ===\n";

        foreach ($items as $i => $item) {
            $parsed = cv_import_extract_date_range_prefix($item);
            $index = (int) $i + 1;
            $out .= "--- Mục {$index} ---\n";
            if ($parsed['range'] !== '') {
                $out .= 'Thời gian: ' . $parsed['range'] . "\n";
            }
            $out .= "Nội dung (chỉ dùng cho mục này, không gộp sang mục khác):\n";
            $out .= $parsed['body'] !== '' ? $parsed['body'] : $item;
            $out .= "\n\n";
        }

        return rtrim($out) . "\n";
    }
}

if (!function_exists('cv_import_format_simple_section')) {
    function cv_import_format_simple_section(string $label, string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }

        return '=== ' . mb_strtoupper($label) . " ===\n" . $body . "\n";
    }
}

if (!function_exists('cv_import_parse_clean_sections')) {
    /**
     * @return array{preamble: string, sections: array<string, string>}
     */
    function cv_import_parse_clean_sections(string $text): array
    {
        $headers = cv_import_section_header_patterns();
        usort($headers, static fn(string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $quoted = array_map(static fn(string $h): string => preg_quote($h, '/'), $headers);
        $pattern = '/\n\n(' . implode('|', $quoted) . ')\n\n/iu';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts) || $parts === []) {
            return ['preamble' => trim($text), 'sections' => []];
        }

        $preamble = trim((string) ($parts[0] ?? ''));
        $sections = [];
        $i = 1;
        while ($i < count($parts)) {
            $label = mb_strtoupper(trim((string) ($parts[$i] ?? '')));
            $content = trim((string) ($parts[$i + 1] ?? ''));
            if ($label !== '' && $content !== '') {
                $sections[$label] = $content;
            }
            $i += 2;
        }

        return ['preamble' => $preamble, 'sections' => $sections];
    }
}

if (!function_exists('cv_import_structure_text_for_ai')) {
    /**
     * Biến text clean thành dạng có section + mục con — giúp AI không lẫn description.
     */
    function cv_import_structure_text_for_ai(string $cleanText): string
    {
        $parsed = cv_import_parse_clean_sections($cleanText);
        $repeatable = cv_import_repeatable_section_labels();
        $blocks = [];

        if ($parsed['preamble'] !== '') {
            $blocks[] = "=== THÔNG TIN CHUNG ===\n" . $parsed['preamble'];
        }

        $sectionOrder = cv_import_section_header_patterns();
        $seen = [];
        foreach ($sectionOrder as $label) {
            $key = mb_strtoupper($label);
            if (isset($seen[$key]) || !isset($parsed['sections'][$key])) {
                continue;
            }
            $seen[$key] = true;
            $body = $parsed['sections'][$key];
            if (isset($repeatable[$key])) {
                $blocks[] = cv_import_format_repeatable_section($key, $body);
            } else {
                $formatted = cv_import_format_simple_section($key, $body);
                if ($formatted !== '') {
                    $blocks[] = $formatted;
                }
            }
        }

        foreach ($parsed['sections'] as $key => $body) {
            if (isset($seen[$key])) {
                continue;
            }
            if (isset($repeatable[$key])) {
                $blocks[] = cv_import_format_repeatable_section($key, $body);
            } else {
                $formatted = cv_import_format_simple_section($key, $body);
                if ($formatted !== '') {
                    $blocks[] = $formatted;
                }
            }
        }

        if ($blocks === []) {
            return $cleanText;
        }

        return trim(implode("\n\n", $blocks));
    }
}
