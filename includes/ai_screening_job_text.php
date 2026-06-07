<?php

if (!function_exists('ai_screening_split_text_lines')) {
    /**
     * Tách block text thành các dòng bullet (bỏ rỗng, trim).
     *
     * @return list<string>
     */
    function ai_screening_split_text_lines(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $parts = preg_split('/\n+/', $normalized) ?: [];
        $lines = [];

        foreach ($parts as $part) {
            $line = trim((string) $part);
            $line = preg_replace('/^[-*•]\s*/u', '', $line) ?? $line;
            $line = trim($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }
}

if (!function_exists('ai_screening_build_job_text')) {
    /**
     * Build JD plain text từ row bảng jobs (contract AI screening).
     *
     * @param array<string, mixed> $job
     */
    function ai_screening_build_job_text(array $job): string
    {
        $title = trim((string) ($job['title'] ?? ''));
        $lines = [];

        if ($title !== '') {
            $lines[] = $title;
        }

        $requirementLines = [];
        foreach (ai_screening_split_text_lines($job['requirements'] ?? null) as $line) {
            $requirementLines[] = $line;
        }

        $experience = trim((string) ($job['experience'] ?? ''));
        if ($experience !== '' && $experience !== 'Không yêu cầu') {
            $requirementLines[] = $experience;
        }

        $jobLevel = trim((string) ($job['job_level'] ?? ''));
        if ($jobLevel !== '' && $jobLevel !== 'Nhân viên') {
            $requirementLines[] = 'Cấp bậc: ' . $jobLevel;
        }

        $jobType = trim((string) ($job['job_type'] ?? ''));
        if ($jobType !== '' && $jobType !== 'Toàn thời gian') {
            $requirementLines[] = 'Hình thức: ' . $jobType;
        }

        $requirementLines = array_values(array_unique($requirementLines));

        if ($requirementLines !== []) {
            $lines[] = '';
            $lines[] = 'Requirements:';
            foreach ($requirementLines as $req) {
                $lines[] = '- ' . $req;
            }
        }

        $niceToHave = ai_screening_split_text_lines($job['benefits'] ?? null);
        if ($niceToHave !== []) {
            $lines[] = '';
            $lines[] = 'Nice to have:';
            foreach ($niceToHave as $item) {
                $lines[] = '- ' . $item;
            }
        }

        $responsibilities = ai_screening_split_text_lines($job['description'] ?? null);
        if ($responsibilities !== []) {
            $lines[] = '';
            $lines[] = 'Responsibilities:';
            foreach ($responsibilities as $item) {
                $lines[] = '- ' . $item;
            }
        }

        return trim(implode("\n", $lines));
    }
}

if (!function_exists('ai_screening_job_text_is_valid')) {
    /**
     * JD tối thiểu: có title + ít nhất 1 dòng Requirements.
     */
    function ai_screening_job_text_is_valid(string $jobText): bool
    {
        $jobText = trim($jobText);
        if ($jobText === '') {
            return false;
        }

        $firstLine = trim(strtok($jobText, "\n") ?: '');
        if ($firstLine === '') {
            return false;
        }

        return (bool) preg_match('/Requirements:\s*\n-/s', $jobText);
    }
}

if (!function_exists('ai_screening_job_text_validation_error')) {
    function ai_screening_job_text_validation_error(array $job): string
    {
        $title = trim((string) ($job['title'] ?? ''));
        if ($title === '') {
            return 'Tin tuyển dụng thiếu tiêu đề.';
        }

        $hasReq = ai_screening_split_text_lines($job['requirements'] ?? null) !== [];
        $hasDesc = ai_screening_split_text_lines($job['description'] ?? null) !== [];
        $experience = trim((string) ($job['experience'] ?? ''));
        $hasExp = $experience !== '' && $experience !== 'Không yêu cầu';

        if (!$hasReq && !$hasDesc && !$hasExp) {
            return 'Tin tuyển dụng thiếu yêu cầu hoặc mô tả — cần bổ sung trước khi chạy AI.';
        }

        $text = ai_screening_build_job_text($job);
        if (!ai_screening_job_text_is_valid($text)) {
            return 'Không thể tạo JD text hợp lệ từ tin tuyển dụng.';
        }

        return '';
    }
}
