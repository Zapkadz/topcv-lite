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

if (!function_exists('cv_allowed_template_keys')) {
    /**
     * @return list<string>
     */
    function cv_allowed_template_keys(): array
    {
        return ['classic', 'modern'];
    }
}

if (!function_exists('cv_normalize_template_key')) {
    function cv_normalize_template_key(string $key): string
    {
        $key = strtolower(trim($key));

        return in_array($key, cv_allowed_template_keys(), true) ? $key : 'classic';
    }
}

if (!function_exists('cv_row_single_month_year')) {
    /**
     * @param array<string, mixed> $row
     */
    function cv_row_single_month_year(array $row, string $prefix): ?string
    {
        return cv_combine_month_year(
            $row[$prefix . '_month'] ?? '',
            $row[$prefix . '_year'] ?? '',
            $row[$prefix . '_at'] ?? ($row[$prefix . '_date'] ?? '')
        );
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

        foreach ($children['projects'] ?? [] as $i => $row) {
            $line = (int) $i + 1;
            $name = trim((string) ($row['project_name'] ?? ''));
            if ($name === '') {
                return ['ok' => false, 'message' => "Dự án dòng {$line}: vui lòng nhập tên dự án."];
            }

            $start = $row['start_date'] ?? null;
            if ($start === null || $start === '') {
                return ['ok' => false, 'message' => "Dự án dòng {$line}: nhập tháng (1–12) và năm (4 chữ số) bắt đầu."];
            }

            $end = $row['end_date'] ?? null;
            if ($end !== null && $end !== '' && cv_normalize_year_month((string) $end) === null) {
                return ['ok' => false, 'message' => "Dự án dòng {$line}: tháng/năm kết thúc không hợp lệ."];
            }
            if ($end !== null && $end !== '' && cv_compare_year_month((string) $end, (string) $start) < 0) {
                return ['ok' => false, 'message' => "Dự án dòng {$line}: tháng kết thúc không được trước tháng bắt đầu."];
            }
        }

        foreach ($children['skills'] ?? [] as $i => $row) {
            $line = (int) $i + 1;
            if (trim((string) ($row['skill_name'] ?? '')) === '') {
                return ['ok' => false, 'message' => "Kỹ năng dòng {$line}: vui lòng nhập tên kỹ năng."];
            }
        }

        foreach ($children['activities'] ?? [] as $i => $row) {
            $line = (int) $i + 1;
            $org = trim((string) ($row['organization'] ?? ''));
            if ($org === '') {
                return ['ok' => false, 'message' => "Hoạt động dòng {$line}: vui lòng nhập tổ chức / hoạt động."];
            }
            $start = $row['start_date'] ?? null;
            if ($start === null || $start === '') {
                return ['ok' => false, 'message' => "Hoạt động dòng {$line}: nhập tháng và năm bắt đầu."];
            }
            $end = $row['end_date'] ?? null;
            if ($end !== null && $end !== '' && cv_compare_year_month((string) $end, (string) $start) < 0) {
                return ['ok' => false, 'message' => "Hoạt động dòng {$line}: tháng kết thúc không được trước tháng bắt đầu."];
            }
        }

        foreach ($children['certificates'] ?? [] as $i => $row) {
            $line = (int) $i + 1;
            if (trim((string) ($row['certificate_name'] ?? '')) === '') {
                return ['ok' => false, 'message' => "Chứng chỉ dòng {$line}: vui lòng nhập tên chứng chỉ."];
            }
            $issued = $row['issued_at'] ?? null;
            if ($issued !== null && $issued !== '' && cv_normalize_year_month((string) $issued) === null) {
                return ['ok' => false, 'message' => "Chứng chỉ dòng {$line}: tháng/năm cấp không hợp lệ."];
            }
        }

        foreach ($children['awards'] ?? [] as $i => $row) {
            $line = (int) $i + 1;
            if (trim((string) ($row['title'] ?? '')) === '') {
                return ['ok' => false, 'message' => "Giải thưởng dòng {$line}: vui lòng nhập tên giải thưởng."];
            }
            $awarded = $row['awarded_at'] ?? null;
            if ($awarded !== null && $awarded !== '' && cv_normalize_year_month((string) $awarded) === null) {
                return ['ok' => false, 'message' => "Giải thưởng dòng {$line}: tháng/năm không hợp lệ."];
            }
        }

        foreach ($children['references'] ?? [] as $i => $row) {
            $line = (int) $i + 1;
            if (trim((string) ($row['full_name'] ?? '')) === '') {
                return ['ok' => false, 'message' => "Người giới thiệu dòng {$line}: vui lòng nhập họ tên."];
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
        $max = 15;

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
        if (trim((string) ($profile['interests'] ?? '')) !== '') {
            $score++;
        }
        if (count($children['educations'] ?? []) > 0) {
            $score++;
        }
        if (count($children['experiences'] ?? []) > 0) {
            $score++;
        }
        if (count($children['projects'] ?? []) > 0) {
            $score++;
        }
        if (count($children['skills'] ?? []) > 0) {
            $score++;
        }
        if (count($children['activities'] ?? []) > 0) {
            $score++;
        }
        if (count($children['certificates'] ?? []) > 0) {
            $score++;
        }
        if (count($children['awards'] ?? []) > 0) {
            $score++;
        }
        if (count($children['references'] ?? []) > 0) {
            $score++;
        }
        if (trim((string) ($profile['avatar_path'] ?? '')) !== '') {
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
            'interests' => $post['interests'] ?? '',
            'template_key' => $post['template_key'] ?? 'classic',
            'attachment_path' => trim((string) ($post['attachment_path'] ?? '')),
        ];

        return [
            'profile' => $profile,
            'children' => [
                'educations' => cv_filter_education_rows($post['educations'] ?? []),
                'experiences' => cv_filter_experience_rows($post['experiences'] ?? []),
                'skills' => cv_filter_skill_rows($post['skills'] ?? []),
                'activities' => cv_filter_activity_rows($post['activities'] ?? []),
                'certificates' => cv_filter_certificate_rows($post['certificates'] ?? []),
                'awards' => cv_filter_award_rows($post['awards'] ?? []),
                'references' => cv_filter_reference_rows($post['references'] ?? []),
                'projects' => cv_filter_project_rows($post['projects'] ?? []),
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

if (!function_exists('cv_filter_project_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_filter_project_rows($rows): array
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
                'project_name' => trim((string) ($row['project_name'] ?? '')),
                'position' => trim((string) ($row['position'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'sort_order' => (int) $i,
            ];
            if ($normalized['project_name'] === ''
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

if (!function_exists('cv_filter_activity_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_filter_activity_rows($rows): array
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
                'organization' => trim((string) ($row['organization'] ?? '')),
                'role' => trim((string) ($row['role'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'sort_order' => (int) $i,
            ];
            if ($normalized['organization'] === ''
                && $normalized['role'] === ''
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

if (!function_exists('cv_filter_certificate_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_filter_certificate_rows($rows): array
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
                'issued_at' => cv_row_single_month_year($row, 'issued'),
                'certificate_name' => trim((string) ($row['certificate_name'] ?? '')),
                'sort_order' => (int) $i,
            ];
            if ($normalized['certificate_name'] === '' && $normalized['issued_at'] === null) {
                continue;
            }
            $out[] = $normalized;
        }

        return $out;
    }
}

if (!function_exists('cv_filter_award_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_filter_award_rows($rows): array
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
                'awarded_at' => cv_row_single_month_year($row, 'awarded'),
                'title' => trim((string) ($row['title'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'sort_order' => (int) $i,
            ];
            if ($normalized['title'] === ''
                && $normalized['awarded_at'] === null
                && $normalized['description'] === '') {
                continue;
            }
            $out[] = $normalized;
        }

        return $out;
    }
}

if (!function_exists('cv_filter_reference_rows')) {
    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    function cv_filter_reference_rows($rows): array
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
                'full_name' => trim((string) ($row['full_name'] ?? '')),
                'position' => trim((string) ($row['position'] ?? '')),
                'contact_info' => trim((string) ($row['contact_info'] ?? '')),
                'sort_order' => (int) $i,
            ];
            if ($normalized['full_name'] === ''
                && $normalized['position'] === ''
                && $normalized['contact_info'] === '') {
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
