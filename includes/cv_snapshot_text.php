<?php

require_once __DIR__ . '/cv_preview_render.php';

if (!function_exists('cv_snapshot_text_from_json')) {
    /**
     * Chuyển cv_snapshot_json (lúc apply) sang plain text cho AI screening.
     *
     * @return string|null null nếu JSON không hợp lệ
     */
    function cv_snapshot_text_from_json(?string $json): ?string
    {
        if ($json === null || trim($json) === '') {
            return null;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }

        return cv_snapshot_text_from_array($data);
    }
}

if (!function_exists('cv_snapshot_text_from_array')) {
    /**
     * @param array<string, mixed> $data
     */
    function cv_snapshot_text_from_array(array $data): string
    {
        $norm = cv_preview_normalize_data($data);
        $profile = $norm['profile'];
        $lines = [];

        $name = trim((string) ($profile['full_name'] ?? ''));
        $headline = trim((string) ($profile['target_position'] ?? $profile['title'] ?? ''));

        if ($name !== '') {
            $lines[] = $name;
        }
        if ($headline !== '') {
            $lines[] = $headline;
        }

        $summary = trim((string) ($profile['career_objective'] ?? ''));
        if ($summary !== '') {
            $lines[] = '';
            $lines[] = 'Summary:';
            $lines[] = $summary;
        }

        $skillLines = [];
        foreach ($norm['skills'] as $skill) {
            if (!is_array($skill)) {
                continue;
            }
            $skillName = trim((string) ($skill['skill_name'] ?? ''));
            if ($skillName === '') {
                continue;
            }
            $desc = trim((string) ($skill['description'] ?? ''));
            $skillLines[] = $desc !== '' ? '- ' . $skillName . ': ' . $desc : '- ' . $skillName;
        }
        if ($skillLines !== []) {
            $lines[] = '';
            $lines[] = 'Skills:';
            array_push($lines, ...$skillLines);
        }

        $experienceBlocks = cv_snapshot_text_format_experiences($norm['experiences']);
        if ($experienceBlocks !== []) {
            $lines[] = '';
            $lines[] = 'Work Experience:';
            array_push($lines, ...$experienceBlocks);
        }

        $projectBlocks = cv_snapshot_text_format_projects($norm['projects']);
        if ($projectBlocks !== []) {
            $lines[] = '';
            $lines[] = 'Projects:';
            array_push($lines, ...$projectBlocks);
        }

        $educationBlocks = cv_snapshot_text_format_educations($norm['educations']);
        if ($educationBlocks !== []) {
            $lines[] = '';
            $lines[] = 'Education:';
            array_push($lines, ...$educationBlocks);
        }

        $certBlocks = cv_snapshot_text_format_certificates($norm['certificates']);
        if ($certBlocks !== []) {
            $lines[] = '';
            $lines[] = 'Certifications:';
            array_push($lines, ...$certBlocks);
        }

        $text = trim(implode("\n", $lines));

        return $text !== '' ? $text : ($name !== '' ? $name : '');
    }
}

if (!function_exists('cv_snapshot_text_append_description_lines')) {
    /**
     * @param list<string> $lines
     */
    function cv_snapshot_text_append_description_lines(array &$lines, string $description): void
    {
        $description = trim($description);
        if ($description === '') {
            return;
        }

        foreach (preg_split('/\r\n|\r|\n/', $description) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $lines[] = '- ' . $line;
            }
        }
    }
}

if (!function_exists('cv_snapshot_text_format_experiences')) {
    /**
     * @param list<mixed> $experiences
     * @return list<string>
     */
    function cv_snapshot_text_format_experiences(array $experiences): array
    {
        $blocks = [];

        foreach ($experiences as $exp) {
            if (!is_array($exp)) {
                continue;
            }

            $block = [];
            $position = trim((string) ($exp['position'] ?? ''));
            $company = trim((string) ($exp['company_name'] ?? ''));
            if ($position !== '' && $company !== '') {
                $block[] = $position . ' - ' . $company;
            } elseif ($position !== '' || $company !== '') {
                $block[] = $position !== '' ? $position : $company;
            }

            $period = cv_preview_period_range_label($exp['start_date'] ?? null, $exp['end_date'] ?? null);
            if ($period !== '— – —') {
                $block[] = $period;
            }

            cv_snapshot_text_append_description_lines($block, (string) ($exp['description'] ?? ''));
            if ($block !== []) {
                $blocks = array_merge($blocks, $block, ['']);
            }
        }

        if ($blocks !== [] && end($blocks) === '') {
            array_pop($blocks);
        }

        return $blocks;
    }
}

if (!function_exists('cv_snapshot_text_format_projects')) {
    /**
     * @param list<mixed> $projects
     * @return list<string>
     */
    function cv_snapshot_text_format_projects(array $projects): array
    {
        $blocks = [];

        foreach ($projects as $project) {
            if (!is_array($project)) {
                continue;
            }

            $block = [];
            $name = trim((string) ($project['project_name'] ?? ''));
            if ($name !== '') {
                $block[] = $name;
            }

            $role = trim((string) ($project['position'] ?? ''));
            if ($role !== '') {
                $block[] = $role;
            }

            $period = cv_preview_period_range_label($project['start_date'] ?? null, $project['end_date'] ?? null);
            if ($period !== '— – —') {
                $block[] = $period;
            }

            cv_snapshot_text_append_description_lines($block, (string) ($project['description'] ?? ''));
            if ($block !== []) {
                $blocks = array_merge($blocks, $block, ['']);
            }
        }

        if ($blocks !== [] && end($blocks) === '') {
            array_pop($blocks);
        }

        return $blocks;
    }
}

if (!function_exists('cv_snapshot_text_format_educations')) {
    /**
     * @param list<mixed> $educations
     * @return list<string>
     */
    function cv_snapshot_text_format_educations(array $educations): array
    {
        $blocks = [];

        foreach ($educations as $edu) {
            if (!is_array($edu)) {
                continue;
            }

            $block = [];
            $school = trim((string) ($edu['school_name'] ?? ''));
            $major = trim((string) ($edu['major'] ?? ''));
            if ($school !== '' && $major !== '') {
                $block[] = $school . ' — ' . $major;
            } elseif ($school !== '' || $major !== '') {
                $block[] = $school !== '' ? $school : $major;
            }

            $period = cv_preview_period_range_label($edu['start_date'] ?? null, $edu['end_date'] ?? null);
            if ($period !== '— – —') {
                $block[] = $period;
            }

            cv_snapshot_text_append_description_lines($block, (string) ($edu['description'] ?? ''));
            if ($block !== []) {
                $blocks = array_merge($blocks, $block, ['']);
            }
        }

        if ($blocks !== [] && end($blocks) === '') {
            array_pop($blocks);
        }

        return $blocks;
    }
}

if (!function_exists('cv_snapshot_text_format_certificates')) {
    /**
     * @param list<mixed> $certificates
     * @return list<string>
     */
    function cv_snapshot_text_format_certificates(array $certificates): array
    {
        $lines = [];

        foreach ($certificates as $cert) {
            if (!is_array($cert)) {
                continue;
            }

            $name = trim((string) ($cert['certificate_name'] ?? $cert['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $issued = trim((string) ($cert['issued_at'] ?? ''));
            $lines[] = $issued !== '' ? '- ' . $name . ' (' . $issued . ')' : '- ' . $name;
        }

        return $lines;
    }
}
