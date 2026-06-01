<?php

if (!function_exists('cv_normalize_phone')) {
    function cv_normalize_phone(string $phone): string
    {
        return preg_replace('/\D+/', '', trim($phone));
    }
}

if (!function_exists('cv_is_valid_phone_vn')) {
    function cv_is_valid_phone_vn(string $phone): bool
    {
        return (bool) preg_match('/^0[0-9]{9}$/', cv_normalize_phone($phone));
    }
}

if (!function_exists('cv_normalize_year_month')) {
    /**
     * Chuẩn hóa tháng/năm lưu DB: YYYY-MM (ISO). Hỗ trợ legacy chỉ có năm (YYYY → YYYY-01).
     */
    function cv_normalize_year_month(string $value): ?string
    {
        $v = trim($value);
        if ($v === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})$/', $v, $m)) {
            $month = (int) $m[2];
            if ($month >= 1 && $month <= 12) {
                return $m[1] . '-' . $m[2];
            }

            return null;
        }

        if (preg_match('/^(\d{4})$/', $v)) {
            return $v . '-01';
        }

        return null;
    }
}

if (!function_exists('cv_split_year_month')) {
    /**
     * @return array{month: string, year: string}
     */
    function cv_split_year_month(mixed $stored): array
    {
        if ($stored === null || $stored === '') {
            return ['month' => '', 'year' => ''];
        }
        $normalized = cv_normalize_year_month((string) $stored);
        if ($normalized === null) {
            return ['month' => '', 'year' => ''];
        }
        [$year, $month] = explode('-', $normalized);

        return ['month' => (string) (int) $month, 'year' => $year];
    }
}

if (!function_exists('cv_combine_month_year')) {
    function cv_combine_month_year(mixed $month, mixed $year, mixed $legacyDate = ''): ?string
    {
        $m = trim((string) $month);
        $y = trim((string) $year);
        if ($m === '' && $y === '') {
            $legacy = trim((string) $legacyDate);

            return $legacy !== '' ? cv_normalize_year_month($legacy) : null;
        }
        if ($m === '' || $y === '') {
            return null;
        }
        $mi = (int) $m;
        $yi = (int) $y;
        if ($mi < 1 || $mi > 12 || $yi < 1950 || $yi > 2100) {
            return null;
        }

        return cv_normalize_year_month(sprintf('%04d-%02d', $yi, $mi));
    }
}

if (!function_exists('cv_row_period_date')) {
    /**
     * @param array<string, mixed> $row
     * @param 'start'|'end' $role
     */
    function cv_row_period_date(array $row, string $role): ?string
    {
        return cv_combine_month_year(
            $row[$role . '_month'] ?? '',
            $row[$role . '_year'] ?? '',
            $row[$role . '_date'] ?? ''
        );
    }
}

if (!function_exists('cv_format_year_month_display')) {
    function cv_format_year_month_display(?string $value): string
    {
        $normalized = $value !== null && $value !== ''
            ? cv_normalize_year_month($value)
            : null;
        if ($normalized === null) {
            return $value !== null && $value !== '' ? (string) $value : '—';
        }
        [$year, $month] = explode('-', $normalized);

        return $month . '/' . $year;
    }
}

if (!function_exists('cv_compare_year_month')) {
    function cv_compare_year_month(string $a, string $b): int
    {
        return strcmp($a, $b);
    }
}

if (!function_exists('cv_date_of_birth_bounds')) {
    /**
     * @return array{min: string, max: string, max_age_years: int}
     */
    function cv_date_of_birth_bounds(): array
    {
        $today = new DateTime('today');

        return [
            'min' => $today->modify('-100 years')->format('Y-m-d'),
            'max' => (new DateTime('today'))->format('Y-m-d'),
            'max_age_years' => 100,
        ];
    }
}

if (!function_exists('cv_validate_date_of_birth')) {
    /**
     * @return array{ok: bool, message: string}
     */
    function cv_validate_date_of_birth(?string $value): array
    {
        $dob = trim((string) $value);
        if ($dob === '') {
            return ['ok' => true, 'message' => ''];
        }

        $parsed = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$parsed || $parsed->format('Y-m-d') !== $dob) {
            return ['ok' => false, 'message' => 'Ngày sinh không hợp lệ (định dạng YYYY-MM-DD).'];
        }

        $parsed->setTime(0, 0, 0);
        $today = new DateTime('today');
        if ($parsed > $today) {
            return ['ok' => false, 'message' => 'Ngày sinh không được ở tương lai.'];
        }

        $bounds = cv_date_of_birth_bounds();
        $minDate = DateTime::createFromFormat('Y-m-d', $bounds['min']);
        if ($minDate && $parsed < $minDate) {
            return [
                'ok' => false,
                'message' => 'Ngày sinh không hợp lệ (tuổi tối đa ' . $bounds['max_age_years'] . ').',
            ];
        }

        return ['ok' => true, 'message' => ''];
    }
}

if (!function_exists('cv_allowed_genders')) {
    /**
     * @return list<string>
     */
    function cv_allowed_genders(): array
    {
        return ['Nam', 'Nữ', 'Khác'];
    }
}

if (!function_exists('cv_format_date_of_birth_display')) {
    function cv_format_date_of_birth_display(?string $value): string
    {
        $dob = trim((string) $value);
        if ($dob === '') {
            return '';
        }
        $parsed = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$parsed || $parsed->format('Y-m-d') !== $dob) {
            return $dob;
        }

        return $parsed->format('d/m/Y');
    }
}

if (!function_exists('cv_validate_profile')) {
    /**
     * @param array<string, mixed> $data
     * @return array{ok: bool, message: string}
     */
    function cv_validate_profile(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            return ['ok' => false, 'message' => 'Vui lòng đặt tên cho CV.'];
        }
        if (mb_strlen($title) > 255) {
            return ['ok' => false, 'message' => 'Tên CV quá dài (tối đa 255 ký tự).'];
        }

        $fullName = trim((string) ($data['full_name'] ?? ''));
        if ($fullName === '') {
            return ['ok' => false, 'message' => 'Vui lòng nhập họ tên.'];
        }

        $targetPosition = trim((string) ($data['target_position'] ?? ''));
        if ($targetPosition === '') {
            return ['ok' => false, 'message' => 'Vui lòng nhập vị trí ứng tuyển.'];
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email === '') {
            return ['ok' => false, 'message' => 'Vui lòng nhập email.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Email không hợp lệ.'];
        }

        $phone = cv_normalize_phone((string) ($data['phone'] ?? ''));
        if ($phone === '') {
            return ['ok' => false, 'message' => 'Vui lòng nhập số điện thoại.'];
        }
        if (!cv_is_valid_phone_vn($phone)) {
            return ['ok' => false, 'message' => 'Số điện thoại phải bắt đầu bằng 0 và đủ 10 chữ số (VD: 0912345678).'];
        }

        $website = trim((string) ($data['website'] ?? ''));
        if ($website !== '') {
            $url = $website;
            if (!preg_match('#^https?://#i', $url)) {
                $url = 'https://' . $url;
            }
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return ['ok' => false, 'message' => 'Website không hợp lệ.'];
            }
        }

        $gender = trim((string) ($data['gender'] ?? ''));
        if ($gender === '' || !in_array($gender, cv_allowed_genders(), true)) {
            return ['ok' => false, 'message' => 'Vui lòng chọn giới tính.'];
        }

        $dobCheck = cv_validate_date_of_birth($data['date_of_birth'] ?? '');
        if (!$dobCheck['ok']) {
            return $dobCheck;
        }

        return ['ok' => true, 'message' => ''];
    }
}

if (!function_exists('cv_validate_children')) {
    /**
     * @param array{educations: list, experiences: list, skills: list} $children
     * @return array{ok: bool, message: string}
     */
    function cv_validate_children(array $children): array
    {
        foreach ($children['educations'] ?? [] as $i => $row) {
            $line = (int) $i + 1;
            $school = trim((string) ($row['school_name'] ?? ''));
            if ($school === '') {
                return ['ok' => false, 'message' => "Học vấn dòng {$line}: vui lòng nhập tên trường."];
            }

            $start = $row['start_date'] ?? null;
            if ($start === null || $start === '') {
                return ['ok' => false, 'message' => "Học vấn dòng {$line}: nhập tháng (1–12) và năm (4 chữ số) bắt đầu."];
            }

            $end = $row['end_date'] ?? null;
            if ($end !== null && $end !== '' && cv_normalize_year_month((string) $end) === null) {
                return ['ok' => false, 'message' => "Học vấn dòng {$line}: tháng/năm kết thúc không hợp lệ."];
            }
            if ($end !== null && $end !== '' && cv_compare_year_month((string) $end, (string) $start) < 0) {
                return ['ok' => false, 'message' => "Học vấn dòng {$line}: tháng kết thúc không được trước tháng bắt đầu."];
            }
        }

        foreach ($children['experiences'] ?? [] as $i => $row) {
            $line = (int) $i + 1;
            $company = trim((string) ($row['company_name'] ?? ''));
            if ($company === '') {
                return ['ok' => false, 'message' => "Kinh nghiệm dòng {$line}: vui lòng nhập tên công ty."];
            }

            $start = $row['start_date'] ?? null;
            if ($start === null || $start === '') {
                return ['ok' => false, 'message' => "Kinh nghiệm dòng {$line}: nhập tháng (1–12) và năm (4 chữ số) bắt đầu."];
            }

            $end = $row['end_date'] ?? null;
            if ($end !== null && $end !== '' && cv_normalize_year_month((string) $end) === null) {
                return ['ok' => false, 'message' => "Kinh nghiệm dòng {$line}: tháng/năm kết thúc không hợp lệ."];
            }
            if ($end !== null && $end !== '' && cv_compare_year_month((string) $end, (string) $start) < 0) {
                return ['ok' => false, 'message' => "Kinh nghiệm dòng {$line}: tháng kết thúc không được trước tháng bắt đầu."];
            }
        }

        foreach ($children['skills'] ?? [] as $i => $row) {
            $line = (int) $i + 1;
            if (trim((string) ($row['skill_name'] ?? '')) === '') {
                return ['ok' => false, 'message' => "Kỹ năng dòng {$line}: vui lòng nhập tên kỹ năng."];
            }
        }

        return ['ok' => true, 'message' => ''];
    }
}

if (!function_exists('cv_validate_submission')) {
    /**
     * @param array<string, mixed> $profile
     * @param array{educations: list, experiences: list, skills: list} $children
     * @return array{ok: bool, message: string}
     */
    function cv_validate_submission(array $profile, array $children): array
    {
        $profileCheck = cv_validate_profile($profile);
        if (!$profileCheck['ok']) {
            return $profileCheck;
        }

        return cv_validate_children($children);
    }
}

if (!function_exists('cv_estimate_completion_percent')) {
    /**
     * @param array<string, mixed> $profile
     * @param array{educations: list, experiences: list, skills: list} $children
     */
    function cv_estimate_completion_percent(array $profile, array $children): int
    {
        $score = 0;
        $max = 10;

        if (trim((string) ($profile['full_name'] ?? '')) !== '') {
            $score++;
        }
        if (trim((string) ($profile['target_position'] ?? '')) !== '') {
            $score++;
        }
        if (cv_is_valid_phone_vn((string) ($profile['phone'] ?? ''))) {
            $score++;
        }
        if (trim((string) ($profile['email'] ?? '')) !== '' && filter_var($profile['email'], FILTER_VALIDATE_EMAIL)) {
            $score++;
        }
        if (trim((string) ($profile['career_objective'] ?? '')) !== '') {
            $score++;
        }
        if (count($children['educations'] ?? []) > 0) {
            $score += 2;
        }
        if (count($children['experiences'] ?? []) > 0) {
            $score += 2;
        }
        if (count($children['skills'] ?? []) > 0) {
            $score++;
        }

        return (int) min(100, round(($score / $max) * 100));
    }
}

if (!function_exists('cv_parse_builder_post')) {
    /**
     * @return array{profile: array<string, mixed>, children: array{educations: list, experiences: list, skills: list}}
     */
    function cv_parse_builder_post(array $post): array
    {
        $profile = [
            'title' => $post['title'] ?? '',
            'full_name' => $post['full_name'] ?? '',
            'target_position' => $post['target_position'] ?? '',
            'date_of_birth' => $post['date_of_birth'] ?? '',
            'gender' => $post['gender'] ?? '',
            'phone' => cv_normalize_phone((string) ($post['phone'] ?? '')),
            'email' => $post['email'] ?? '',
            'website' => $post['website'] ?? '',
            'address' => $post['address'] ?? '',
            'career_objective' => $post['career_objective'] ?? '',
        ];

        return [
            'profile' => $profile,
            'children' => [
                'educations' => cv_filter_education_rows($post['educations'] ?? []),
                'experiences' => cv_filter_experience_rows($post['experiences'] ?? []),
                'skills' => cv_filter_skill_rows($post['skills'] ?? []),
            ],
        ];
    }
}

if (!function_exists('cv_filter_education_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_filter_education_rows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized = [
                'start_date' => cv_row_period_date($row, 'start'),
                'end_date' => cv_row_period_date($row, 'end'),
                'school_name' => trim((string) ($row['school_name'] ?? '')),
                'major' => trim((string) ($row['major'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'sort_order' => (int) $i,
            ];
            if ($normalized['school_name'] === ''
                && $normalized['major'] === ''
                && $normalized['start_date'] === null
                && $normalized['end_date'] === null
                && $normalized['description'] === '') {
                continue;
            }
            $out[] = $normalized;
        }

        return $out;
    }
}

if (!function_exists('cv_filter_experience_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_filter_experience_rows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized = [
                'start_date' => cv_row_period_date($row, 'start'),
                'end_date' => cv_row_period_date($row, 'end'),
                'company_name' => trim((string) ($row['company_name'] ?? '')),
                'position' => trim((string) ($row['position'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'sort_order' => (int) $i,
            ];
            if ($normalized['company_name'] === ''
                && $normalized['position'] === ''
                && $normalized['start_date'] === null
                && $normalized['end_date'] === null
                && $normalized['description'] === '') {
                continue;
            }
            $out[] = $normalized;
        }

        return $out;
    }
}

if (!function_exists('cv_filter_skill_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_filter_skill_rows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized = [
                'skill_name' => trim((string) ($row['skill_name'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'sort_order' => (int) $i,
            ];
            if ($normalized['skill_name'] === '' && $normalized['description'] === '') {
                continue;
            }
            $out[] = $normalized;
        }

        return $out;
    }
}

if (!function_exists('cv_format_updated_at')) {
    function cv_format_updated_at(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '—';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return $datetime;
        }

        return date('d/m/Y H:i', $ts);
    }
}
