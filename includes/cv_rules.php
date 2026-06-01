<?php

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

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Email không hợp lệ.'];
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone !== '' && !preg_match('/^[0-9+\s().-]{8,20}$/', $phone)) {
            return ['ok' => false, 'message' => 'Số điện thoại không hợp lệ.'];
        }

        $dob = trim((string) ($data['date_of_birth'] ?? ''));
        if ($dob !== '') {
            $parsed = DateTime::createFromFormat('Y-m-d', $dob);
            if (!$parsed || $parsed->format('Y-m-d') !== $dob) {
                return ['ok' => false, 'message' => 'Ngày sinh không hợp lệ (YYYY-MM-DD).'];
            }
        }

        return ['ok' => true, 'message' => ''];
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
        if (trim((string) ($profile['phone'] ?? '')) !== '') {
            $score++;
        }
        if (trim((string) ($profile['email'] ?? '')) !== '') {
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
