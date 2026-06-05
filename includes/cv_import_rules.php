<?php

require_once __DIR__ . '/cv_rules.php';

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
    function cv_import_min_text_len(): int
    {
        return 80;
    }
}

if (!function_exists('cv_import_max_rows_per_section')) {
    function cv_import_max_rows_per_section(): int
    {
        return 5;
    }
}

if (!function_exists('cv_import_normalize_ai_date')) {
    function cv_import_normalize_ai_date(mixed $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        if (preg_match('/^(nay|hiện tại|hien tai|present|current)$/iu', $v)) {
            return date('Y-m');
        }

        if (preg_match('/^T?(\d{1,2})\/(\d{4})$/iu', $v, $m)) {
            return cv_normalize_year_month(sprintf('%04d-%02d', (int) $m[2], (int) $m[1]));
        }

        if (preg_match('/^(\d{1,2})\/(\d{4})$/', $v, $m)) {
            return cv_normalize_year_month(sprintf('%04d-%02d', (int) $m[2], (int) $m[1]));
        }

        if (preg_match('/^(\d{4})$/', $v, $m)) {
            return cv_normalize_year_month($m[1]);
        }

        return cv_normalize_year_month($v);
    }
}

if (!function_exists('cv_import_pick')) {
    function cv_import_pick(array $raw, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $raw)) {
                continue;
            }
            $val = trim((string) $raw[$key]);
            if ($val !== '') {
                return $val;
            }
        }

        return '';
    }
}

if (!function_exists('cv_normalize_import_draft')) {
    /**
     * Chuẩn hóa draft AI/fallback → shape khớp cv-builder.
     *
     * @param array<string, mixed> $raw
     * @return array{profile: array<string, mixed>, children: array<string, list>}
     */
    function cv_normalize_import_draft(array $raw): array
    {
        $title = cv_import_pick($raw, ['title', 'cv_title', 'cvTitle']);
        if ($title === '') {
            $title = 'CV import ' . date('Y-m-d');
        }

        $gender = cv_import_pick($raw, ['gender', 'gioi_tinh', 'sex']);
        if ($gender !== '' && !in_array($gender, cv_allowed_genders(), true)) {
            $genderLower = mb_strtolower($gender);
            $gender = match ($genderLower) {
                'male', 'nam' => 'Nam',
                'female', 'nữ', 'nu' => 'Nữ',
                'other', 'khác', 'khac' => 'Khác',
                default => '',
            };
        }

        $dob = cv_import_pick($raw, ['date_of_birth', 'dob', 'birthday', 'ngay_sinh']);
        if ($dob !== '') {
            $parsedDob = DateTime::createFromFormat('Y-m-d', $dob);
            if (!$parsedDob || $parsedDob->format('Y-m-d') !== $dob) {
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dob, $m)) {
                    $dob = sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
                } else {
                    $dob = '';
                }
            }
        }

        $profile = [
            'title' => $title,
            'full_name' => cv_import_pick($raw, ['full_name', 'fullName', 'name', 'ho_ten']),
            'target_position' => cv_import_pick($raw, ['target_position', 'targetPosition', 'position', 'vi_tri']),
            'date_of_birth' => $dob,
            'gender' => $gender,
            'phone' => cv_normalize_phone(cv_import_pick($raw, ['phone', 'mobile', 'sdt', 'phone_number'])),
            'email' => cv_import_pick($raw, ['email', 'mail']),
            'website' => cv_import_pick($raw, ['website', 'url', 'portfolio']),
            'address' => cv_import_pick($raw, ['address', 'dia_chi', 'location']),
            'career_objective' => cv_import_pick($raw, ['career_objective', 'careerObjective', 'objective', 'muc_tieu']),
            'interests' => cv_import_pick($raw, ['interests', 'interest', 'so_thich']),
            'template_key' => cv_normalize_template_key(cv_import_pick($raw, ['template_key', 'templateKey']) ?: 'classic'),
        ];

        $children = [
            'educations' => cv_import_normalize_education_rows($raw['educations'] ?? []),
            'experiences' => cv_import_normalize_experience_rows($raw['experiences'] ?? []),
            'skills' => cv_import_normalize_skill_rows($raw['skills'] ?? []),
            'projects' => cv_import_normalize_project_rows($raw['projects'] ?? []),
            'activities' => cv_import_normalize_activity_rows($raw['activities'] ?? []),
            'certificates' => cv_import_normalize_certificate_rows($raw['certificates'] ?? []),
            'awards' => cv_import_normalize_award_rows($raw['awards'] ?? []),
            'references' => cv_import_normalize_reference_rows($raw['references'] ?? []),
        ];

        $filtered = [
            'educations' => cv_filter_education_rows($children['educations']),
            'experiences' => cv_filter_experience_rows($children['experiences']),
            'skills' => cv_filter_skill_rows($children['skills']),
            'projects' => cv_filter_project_rows($children['projects']),
            'activities' => cv_filter_activity_rows($children['activities']),
            'certificates' => cv_filter_certificate_rows($children['certificates']),
            'awards' => cv_filter_award_rows($children['awards']),
            'references' => cv_filter_reference_rows($children['references']),
        ];
        $filtered = cv_import_prune_incomplete_children($filtered);

        return [
            'profile' => $profile,
            'children' => [
                'educations' => cv_import_limit_rows($filtered['educations']),
                'experiences' => cv_import_limit_rows($filtered['experiences']),
                'skills' => cv_import_limit_rows($filtered['skills']),
                'projects' => cv_import_limit_rows($filtered['projects']),
                'activities' => cv_import_limit_rows($filtered['activities']),
                'certificates' => cv_import_limit_rows($filtered['certificates']),
                'awards' => cv_import_limit_rows($filtered['awards']),
                'references' => cv_import_limit_rows($filtered['references']),
            ],
        ];
    }
}

if (!function_exists('cv_import_limit_rows')) {
    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    function cv_import_limit_rows(array $rows): array
    {
        return array_slice($rows, 0, cv_import_max_rows_per_section());
    }
}

if (!function_exists('cv_import_is_trivial_text')) {
    function cv_import_is_trivial_text(string $value): bool
    {
        $v = trim($value);
        if ($v === '' || $v === '-' || $v === '—' || $v === '–') {
            return true;
        }

        return mb_strlen($v) <= 1;
    }
}

if (!function_exists('cv_import_prune_incomplete_children')) {
    /**
     * Bỏ mục AI tạo ra chỉ có ngày mà không có trường/công ty — thường gặp với PDF nhiễu.
     *
     * @param array<string, list<array<string, mixed>>> $children
     * @return array<string, list<array<string, mixed>>>
     */
    function cv_import_prune_incomplete_children(array $children): array
    {
        $children['educations'] = array_values(array_filter(
            $children['educations'] ?? [],
            static fn(array $row): bool => !cv_import_is_trivial_text((string) ($row['school_name'] ?? ''))
        ));

        $children['experiences'] = array_values(array_filter(
            $children['experiences'] ?? [],
            static fn(array $row): bool => !cv_import_is_trivial_text((string) ($row['company_name'] ?? ''))
        ));

        $children['projects'] = array_values(array_filter(
            $children['projects'] ?? [],
            static fn(array $row): bool => !cv_import_is_trivial_text((string) ($row['project_name'] ?? ''))
        ));

        $children['activities'] = array_values(array_filter(
            $children['activities'] ?? [],
            static fn(array $row): bool => !cv_import_is_trivial_text((string) ($row['organization'] ?? ''))
        ));

        $children['skills'] = array_values(array_filter(
            $children['skills'] ?? [],
            static fn(array $row): bool => !cv_import_is_trivial_text((string) ($row['skill_name'] ?? ''))
        ));

        $children['certificates'] = array_values(array_filter(
            $children['certificates'] ?? [],
            static fn(array $row): bool => !cv_import_is_trivial_text((string) ($row['certificate_name'] ?? ''))
        ));

        $children['awards'] = array_values(array_filter(
            $children['awards'] ?? [],
            static fn(array $row): bool => !cv_import_is_trivial_text((string) ($row['title'] ?? ''))
        ));

        $children['references'] = array_values(array_filter(
            $children['references'] ?? [],
            static fn(array $row): bool => !cv_import_is_trivial_text((string) ($row['full_name'] ?? ''))
        ));

        foreach (['educations', 'experiences', 'projects', 'activities', 'awards'] as $section) {
            foreach ($children[$section] as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }
                if (cv_import_is_trivial_text((string) ($row['description'] ?? ''))) {
                    $children[$section][$i]['description'] = '';
                }
            }
        }

        return $children;
    }
}

if (!function_exists('cv_import_normalize_education_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_import_normalize_education_rows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'start_date' => cv_import_normalize_ai_date($row['start_date'] ?? ''),
                'end_date' => cv_import_normalize_ai_date($row['end_date'] ?? ''),
                'school_name' => cv_import_pick($row, ['school_name', 'school', 'schoolName', 'university']),
                'major' => cv_import_pick($row, ['major', 'field', 'major_name']),
                'description' => cv_import_pick($row, ['description', 'detail', 'summary']),
            ];
        }

        return $out;
    }
}

if (!function_exists('cv_import_normalize_experience_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_import_normalize_experience_rows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'start_date' => cv_import_normalize_ai_date($row['start_date'] ?? ''),
                'end_date' => cv_import_normalize_ai_date($row['end_date'] ?? ''),
                'company_name' => cv_import_pick($row, ['company_name', 'company', 'companyName', 'employer']),
                'position' => cv_import_pick($row, ['position', 'role', 'job_title', 'title']),
                'description' => cv_import_pick($row, ['description', 'detail', 'summary']),
            ];
        }

        return $out;
    }
}

if (!function_exists('cv_import_normalize_skill_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_import_normalize_skill_rows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (is_string($row)) {
                $row = ['skill_name' => $row, 'description' => ''];
            }
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'skill_name' => cv_import_pick($row, ['skill_name', 'skill', 'name', 'title']),
                'description' => cv_import_pick($row, ['description', 'level', 'detail']),
            ];
        }

        return $out;
    }
}

if (!function_exists('cv_import_normalize_project_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_import_normalize_project_rows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'start_date' => cv_import_normalize_ai_date($row['start_date'] ?? ''),
                'end_date' => cv_import_normalize_ai_date($row['end_date'] ?? ''),
                'project_name' => cv_import_pick($row, ['project_name', 'project', 'name', 'title']),
                'position' => cv_import_pick($row, ['position', 'role']),
                'description' => cv_import_pick($row, ['description', 'detail', 'summary']),
            ];
        }

        return $out;
    }
}

if (!function_exists('cv_import_normalize_activity_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_import_normalize_activity_rows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'start_date' => cv_import_normalize_ai_date($row['start_date'] ?? ''),
                'end_date' => cv_import_normalize_ai_date($row['end_date'] ?? ''),
                'organization' => cv_import_pick($row, ['organization', 'org', 'company']),
                'role' => cv_import_pick($row, ['role', 'position', 'title']),
                'description' => cv_import_pick($row, ['description', 'detail', 'summary']),
            ];
        }

        return $out;
    }
}

if (!function_exists('cv_import_normalize_certificate_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_import_normalize_certificate_rows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'issued_at' => cv_import_normalize_ai_date($row['issued_at'] ?? $row['issue_date'] ?? ''),
                'certificate_name' => cv_import_pick($row, ['certificate_name', 'name', 'title', 'certificate']),
            ];
        }

        return $out;
    }
}

if (!function_exists('cv_import_normalize_award_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_import_normalize_award_rows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'awarded_at' => cv_import_normalize_ai_date($row['awarded_at'] ?? $row['award_date'] ?? ''),
                'title' => cv_import_pick($row, ['title', 'name', 'award_name']),
                'description' => cv_import_pick($row, ['description', 'detail', 'summary']),
            ];
        }

        return $out;
    }
}

if (!function_exists('cv_import_normalize_reference_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_import_normalize_reference_rows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'full_name' => cv_import_pick($row, ['full_name', 'fullName', 'name']),
                'position' => cv_import_pick($row, ['position', 'role', 'title']),
                'contact_info' => cv_import_pick($row, ['contact_info', 'contact', 'phone', 'email']),
            ];
        }

        return $out;
    }
}
