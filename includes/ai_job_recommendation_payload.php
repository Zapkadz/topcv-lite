<?php

require_once __DIR__ . '/ai_screening_config.php';
require_once __DIR__ . '/ai_screening_payload.php';
require_once __DIR__ . '/cv_snapshot_text.php';

if (!function_exists('ai_recommendation_map_experience_rows')) {
    /**
     * @param list<mixed> $rows
     * @return list<array<string, mixed>>
     */
    function ai_recommendation_map_experience_rows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $company = trim((string) ($row['company_name'] ?? ''));
            $position = trim((string) ($row['position'] ?? ''));
            if ($company === '' && $position === '') {
                continue;
            }
            $out[] = [
                'company_name' => $company,
                'position' => $position,
                'start_date' => trim((string) ($row['start_date'] ?? '')),
                'end_date' => trim((string) ($row['end_date'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
            ];
        }

        return $out;
    }
}

if (!function_exists('ai_recommendation_format_education_line')) {
    /**
     * @param array<string, mixed> $row
     */
    function ai_recommendation_format_education_line(array $row): string
    {
        $school = trim((string) ($row['school_name'] ?? ''));
        $major = trim((string) ($row['major'] ?? ''));
        $start = trim((string) ($row['start_date'] ?? ''));
        $end = trim((string) ($row['end_date'] ?? ''));
        $desc = trim((string) ($row['description'] ?? ''));

        $line = $school;
        if ($major !== '') {
            $line .= ($line !== '' ? ' — ' : '') . $major;
        }
        if ($start !== '' || $end !== '') {
            $period = $start . ($end !== '' ? ' – ' . $end : '');
            $line .= ($line !== '' ? ' (' . $period . ')' : $period);
        }
        if ($desc !== '') {
            $line .= ($line !== '' ? ' — ' : '') . preg_replace('/\s+/u', ' ', $desc);
        }

        return trim($line);
    }
}

if (!function_exists('ai_recommendation_format_certificate_line')) {
    /**
     * @param array<string, mixed> $row
     */
    function ai_recommendation_format_certificate_line(array $row): string
    {
        $name = trim((string) ($row['certificate_name'] ?? ''));
        $issued = trim((string) ($row['issued_at'] ?? ''));
        if ($name === '') {
            return '';
        }
        if ($issued !== '') {
            return $name . ' (' . $issued . ')';
        }

        return $name;
    }
}

if (!function_exists('ai_recommendation_map_education_rows')) {
    /**
     * API /recommend-jobs expects list<string>.
     *
     * @param list<mixed> $rows
     * @return list<string>
     */
    function ai_recommendation_map_education_rows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (is_string($row) && trim($row) !== '') {
                $out[] = trim($row);
                continue;
            }
            if (!is_array($row)) {
                continue;
            }
            $line = ai_recommendation_format_education_line($row);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }
}

if (!function_exists('ai_recommendation_map_project_rows')) {
    /**
     * @param list<mixed> $rows
     * @return list<array<string, mixed>>
     */
    function ai_recommendation_map_project_rows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['project_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = [
                'project_name' => $name,
                'position' => trim((string) ($row['position'] ?? '')),
                'start_date' => trim((string) ($row['start_date'] ?? '')),
                'end_date' => trim((string) ($row['end_date'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
            ];
        }

        return $out;
    }
}

if (!function_exists('ai_recommendation_map_certificate_rows')) {
    /**
     * API /recommend-jobs expects list<string>.
     *
     * @param list<mixed> $rows
     * @return list<string>
     */
    function ai_recommendation_map_certificate_rows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (is_string($row) && trim($row) !== '') {
                $out[] = trim($row);
                continue;
            }
            if (!is_array($row)) {
                continue;
            }
            $line = ai_recommendation_format_certificate_line($row);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }
}

if (!function_exists('ai_recommendation_build_candidate_payload')) {
    /**
     * @param array<string, mixed> $cvData packFullProfile structure
     * @param array<string, mixed> $userRow users row
     */
    function ai_recommendation_build_candidate_payload(
        int $candidateId,
        array $cvData,
        array $userRow
    ): array {
        $profile = is_array($cvData['profile'] ?? null) ? $cvData['profile'] : [];
        $skills = [];
        foreach ($cvData['skills'] ?? [] as $skill) {
            if (!is_array($skill)) {
                continue;
            }
            $name = trim((string) ($skill['skill_name'] ?? ''));
            if ($name !== '') {
                $skills[] = $name;
            }
        }

        $fullName = trim((string) ($profile['full_name'] ?? ''));
        if ($fullName === '') {
            $fullName = trim((string) ($userRow['fullname'] ?? ''));
        }

        $email = trim((string) ($profile['email'] ?? ''));
        if ($email === '') {
            $email = trim((string) ($userRow['email'] ?? ''));
        }

        $phone = trim((string) ($profile['phone'] ?? ''));
        if ($phone === '') {
            $phone = trim((string) ($userRow['phone'] ?? ''));
        }

        $cvText = cv_snapshot_text_from_array($cvData);

        return [
            'candidate_id' => $candidateId,
            'candidate_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'headline' => trim((string) ($profile['target_position'] ?? $profile['title'] ?? '')),
            'summary' => trim((string) ($profile['career_objective'] ?? '')),
            'skills' => $skills,
            'work_experience' => ai_recommendation_map_experience_rows(
                is_array($cvData['experiences'] ?? null) ? $cvData['experiences'] : []
            ),
            'projects' => ai_recommendation_map_project_rows(
                is_array($cvData['projects'] ?? null) ? $cvData['projects'] : []
            ),
            'education' => ai_recommendation_map_education_rows(
                is_array($cvData['educations'] ?? null) ? $cvData['educations'] : []
            ),
            'certifications' => ai_recommendation_map_certificate_rows(
                is_array($cvData['certificates'] ?? null) ? $cvData['certificates'] : []
            ),
            'cv_text' => $cvText,
            'cv_file_path' => trim((string) ($profile['attachment_path'] ?? '')),
        ];
    }
}

if (!function_exists('ai_recommendation_build_jobs_payload')) {
    /**
     * @param list<array<string, mixed>> $jobs
     * @return list<array<string, mixed>>
     */
    function ai_recommendation_build_jobs_payload(array $jobs): array
    {
        $out = [];
        foreach ($jobs as $job) {
            if (!is_array($job)) {
                continue;
            }
            $payload = ai_screening_build_job_payload($job);
            if ((int) ($payload['job_id'] ?? 0) <= 0) {
                continue;
            }
            if (trim((string) ($payload['job_title'] ?? '')) === '') {
                continue;
            }
            $out[] = $payload;
        }

        return $out;
    }
}

if (!function_exists('ai_recommendation_build_request_payload')) {
    /**
     * @param array<string, mixed> $candidate
     * @param list<array<string, mixed>> $jobs
     * @return array<string, mixed>
     */
    function ai_recommendation_build_request_payload(array $candidate, array $jobs): array
    {
        $cfg = ai_screening_config();

        return [
            'candidate' => $candidate,
            'jobs' => $jobs,
            'options' => [
                'top_k' => (int) ($cfg['recommend_top_k'] ?? 10),
                'retrieval_top_n' => (int) ($cfg['recommend_retrieval_top_n'] ?? 50),
            ],
        ];
    }
}
