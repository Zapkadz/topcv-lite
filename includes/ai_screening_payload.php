<?php

require_once __DIR__ . '/ai_screening_job_text.php';
require_once __DIR__ . '/ai_taxonomy_config.php';

if (!function_exists('ai_screening_build_job_payload')) {
    /**
     * @param array<string, mixed> $job
     * @return array<string, mixed>
     */
    function ai_screening_build_job_payload(array $job): array
    {
        $requirements = ai_screening_split_text_lines($job['requirements'] ?? null);

        $experience = trim((string) ($job['experience'] ?? ''));
        if ($experience !== '' && $experience !== 'Không yêu cầu') {
            $requirements[] = $experience;
        }

        $jobLevel = trim((string) ($job['job_level'] ?? ''));
        if ($jobLevel !== '' && $jobLevel !== 'Nhân viên') {
            $requirements[] = 'Cấp bậc: ' . $jobLevel;
        }

        $jobType = trim((string) ($job['job_type'] ?? ''));
        if ($jobType !== '' && $jobType !== 'Toàn thời gian') {
            $requirements[] = 'Hình thức: ' . $jobType;
        }

        return [
            'job_id' => (int) ($job['id'] ?? 0),
            'job_title' => trim((string) ($job['title'] ?? '')),
            'requirements' => array_values(array_unique($requirements)),
            'nice_to_have' => ai_screening_split_text_lines($job['benefits'] ?? null),
            'responsibilities' => ai_screening_split_text_lines($job['description'] ?? null),
            'minimum_experience_years' => null,
            'description' => trim((string) ($job['description'] ?? '')),
        ];
    }
}

if (!function_exists('ai_screening_build_candidate_payload')) {
    /**
     * @param array<string, mixed> $app Row từ listApplicationsForAiScreening
     * @return array<string, mixed>|null null nếu thiếu cv_text
     */
    function ai_screening_build_candidate_payload(array $app): ?array
    {
        $appId = (int) ($app['app_id'] ?? 0);
        $candidateId = (int) ($app['candidate_id'] ?? 0);
        $cvText = trim((string) ($app['cv_snapshot_text'] ?? ''));

        if ($appId <= 0 || $candidateId <= 0 || $cvText === '') {
            return null;
        }

        return [
            'application_id' => $appId,
            'candidate_id' => $candidateId,
            'candidate_name' => trim((string) ($app['fullname'] ?? '')),
            'email' => trim((string) ($app['email'] ?? '')),
            'phone' => trim((string) ($app['phone'] ?? '')),
            'headline' => '',
            'summary' => '',
            'skills' => [],
            'work_experience' => [],
            'projects' => [],
            'education' => [],
            'cv_text' => $cvText,
            'applied_at' => (string) ($app['time_apply'] ?? $app['created_at'] ?? ''),
        ];
    }
}

if (!function_exists('ai_screening_build_screening_payload')) {
    /**
     * @param array<string, mixed> $job
     * @param list<array<string, mixed>> $applications
     * @return array{payload: array<string, mixed>, skipped: int, app_map: array<int, array<string, mixed>>}
     */
    function ai_screening_build_screening_payload(array $job, array $applications): array
    {
        $candidates = [];
        $appMap = [];
        $skipped = 0;

        foreach ($applications as $app) {
            $candidate = ai_screening_build_candidate_payload($app);
            if ($candidate === null) {
                $skipped++;
                ai_screening_log('Skip API candidate app_id=' . ($app['app_id'] ?? '?') . ' — missing cv_snapshot_text');
                continue;
            }

            $appId = (int) $candidate['application_id'];
            $candidates[] = $candidate;
            $appMap[$appId] = $app;
        }

        $payload = [
            'job' => ai_screening_build_job_payload($job),
            'candidates' => $candidates,
        ];

        $taxonomyPath = ai_taxonomy_effective_screening_path();
        if ($taxonomyPath !== '' && is_file($taxonomyPath)) {
            $payload['taxonomy_path'] = $taxonomyPath;
        }

        return [
            'payload' => $payload,
            'skipped' => $skipped,
            'app_map' => $appMap,
        ];
    }
}
