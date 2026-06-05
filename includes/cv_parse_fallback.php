<?php

require_once __DIR__ . '/cv_rules.php';

/**
 * Rule-based fallback khi AI parse thất bại hoặc thiếu field cốt lõi.
 *
 * @return array<string, mixed> Draft thô (chưa normalize đầy đủ).
 */
if (!function_exists('cv_parse_fallback_from_text')) {
    function cv_parse_fallback_from_text(string $text): array
    {
        $flat = preg_replace('/\s+/u', ' ', trim($text));
        if (!is_string($flat)) {
            $flat = '';
        }

        $draft = [
            'title' => '',
            'full_name' => cv_parse_fallback_guess_name($flat),
            'target_position' => '',
            'date_of_birth' => '',
            'gender' => '',
            'phone' => '',
            'email' => '',
            'website' => '',
            'address' => '',
            'career_objective' => '',
            'interests' => '',
            'educations' => [],
            'experiences' => [],
            'skills' => [],
            'projects' => [],
            'activities' => [],
            'certificates' => [],
            'awards' => [],
            'references' => [],
        ];

        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $flat, $m)) {
            $draft['email'] = $m[0];
        }

        if (preg_match('/\b(0[0-9]{9})\b/', $flat, $m)) {
            $draft['phone'] = $m[1];
        }

        if (preg_match('/\b((?:https?:\/\/)?(?:www\.)?[a-z0-9][-a-z0-9.]+\.[a-z]{2,})\b/i', $flat, $m)) {
            $draft['website'] = $m[1];
        }

        $sections = cv_parse_fallback_split_sections($flat);
        if (!empty($sections['educations'])) {
            $draft['educations'] = cv_parse_fallback_lines_to_educations($sections['educations']);
        }
        if (!empty($sections['experiences'])) {
            $draft['experiences'] = cv_parse_fallback_lines_to_experiences($sections['experiences']);
        }
        if (!empty($sections['skills'])) {
            $draft['skills'] = cv_parse_fallback_lines_to_skills($sections['skills']);
        }

        return $draft;
    }
}

if (!function_exists('cv_parse_fallback_guess_name')) {
    function cv_parse_fallback_guess_name(string $text): string
    {
        if (preg_match('/(?:họ\s*và\s*tên|ho\s*ten|full\s*name)\s*[:\-]\s*([^|]+?)(?:\s{2,}|$)/iu', $text, $m)) {
            return trim($m[1]);
        }

        $parts = preg_split('/\s{2,}/', $text);
        if (is_array($parts) && !empty($parts[0])) {
            $first = trim($parts[0]);
            if ($first !== '' && !preg_match('/@|https?:\/\//i', $first) && mb_strlen($first) <= 80) {
                return $first;
            }
        }

        return '';
    }
}

if (!function_exists('cv_parse_fallback_split_sections')) {
    /**
     * @return array{educations: string, experiences: string, skills: string}
     */
    function cv_parse_fallback_split_sections(string $text): array
    {
        $pattern = '/\b(HỌC VẤN|EDUCATION|KINH NGHIỆM|EXPERIENCE|KỸ NĂNG|SKILLS)\b/iu';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts)) {
            return ['educations' => '', 'experiences' => '', 'skills' => ''];
        }

        $out = ['educations' => '', 'experiences' => '', 'skills' => ''];
        $current = '';
        foreach ($parts as $part) {
            $label = mb_strtoupper(trim($part));
            if ($label === 'HỌC VẤN' || $label === 'EDUCATION') {
                $current = 'educations';
                continue;
            }
            if ($label === 'KINH NGHIỆM' || $label === 'EXPERIENCE') {
                $current = 'experiences';
                continue;
            }
            if ($label === 'KỸ NĂNG' || $label === 'SKILLS') {
                $current = 'skills';
                continue;
            }
            if ($current !== '' && trim($part) !== '') {
                $out[$current] .= ' ' . trim($part);
            }
        }

        return $out;
    }
}

if (!function_exists('cv_parse_fallback_lines_to_educations')) {
    /**
     * @return list<array<string, mixed>>
     */
    function cv_parse_fallback_lines_to_educations(string $block): array
    {
        $chunks = cv_parse_fallback_chunk_block($block, 2);
        $rows = [];
        foreach ($chunks as $chunk) {
            $rows[] = [
                'start_date' => '',
                'end_date' => '',
                'school_name' => mb_substr(trim($chunk), 0, 255),
                'major' => '',
                'description' => '',
            ];
        }

        return $rows;
    }
}

if (!function_exists('cv_parse_fallback_lines_to_experiences')) {
    /**
     * @return list<array<string, mixed>>
     */
    function cv_parse_fallback_lines_to_experiences(string $block): array
    {
        $chunks = cv_parse_fallback_chunk_block($block, 2);
        $rows = [];
        foreach ($chunks as $chunk) {
            $rows[] = [
                'start_date' => '',
                'end_date' => '',
                'company_name' => mb_substr(trim($chunk), 0, 255),
                'position' => '',
                'description' => '',
            ];
        }

        return $rows;
    }
}

if (!function_exists('cv_parse_fallback_lines_to_skills')) {
    /**
     * @return list<array<string, mixed>>
     */
    function cv_parse_fallback_lines_to_skills(string $block): array
    {
        $items = preg_split('/[,;|•·]/u', $block) ?: [];
        $rows = [];
        foreach ($items as $item) {
            $name = trim($item);
            if ($name === '' || mb_strlen($name) > 120) {
                continue;
            }
            $rows[] = ['skill_name' => $name, 'description' => ''];
            if (count($rows) >= 5) {
                break;
            }
        }

        return $rows;
    }
}

if (!function_exists('cv_parse_fallback_chunk_block')) {
    /**
     * @return list<string>
     */
    function cv_parse_fallback_chunk_block(string $block, int $maxChunks): array
    {
        $block = trim($block);
        if ($block === '') {
            return [];
        }

        $parts = preg_split('/\s{2,}|\.\s+/u', $block) ?: [];
        $chunks = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || mb_strlen($part) < 4) {
                continue;
            }
            $chunks[] = $part;
            if (count($chunks) >= $maxChunks) {
                break;
            }
        }

        if ($chunks === [] && $block !== '') {
            $chunks[] = mb_substr($block, 0, 255);
        }

        return $chunks;
    }
}
