<?php

if (!function_exists('job_recommendation_fit_label_badge_html')) {
    function job_recommendation_fit_label_badge_html(?string $fitLabel): string
    {
        if ($fitLabel === null || trim($fitLabel) === '') {
            return '<span class="text-muted small">—</span>';
        }

        $label = trim($fitLabel);
        $lower = strtolower($label);
        $class = match (true) {
            str_contains($lower, 'strong') => 'bg-success',
            str_contains($lower, 'good') => 'bg-primary',
            str_contains($lower, 'potential') => 'bg-info text-dark',
            str_contains($lower, 'stretch') => 'bg-warning text-dark',
            str_contains($lower, 'low') => 'bg-secondary',
            default => 'bg-light text-dark border',
        };

        return '<span class="badge rounded-pill ' . $class . '">' . htmlspecialchars($label) . '</span>';
    }
}

if (!function_exists('job_recommendation_gap_summary_line')) {
    /**
     * @param array<string, mixed> $jobRow
     */
    function job_recommendation_gap_summary_line(array $jobRow): string
    {
        $summary = is_array($jobRow['skill_gap_summary'] ?? null) ? $jobRow['skill_gap_summary'] : [];
        $missing = (int) ($summary['missing_must_have_count'] ?? 0);
        $weak = (int) ($summary['weak_evidence_count'] ?? 0);
        $optional = (int) ($summary['optional_growth_count'] ?? 0);

        $parts = [];
        if ($missing > 0) {
            $parts[] = 'Thiếu ' . $missing . ' bắt buộc';
        }
        if ($weak > 0) {
            $parts[] = $weak . ' bằng chứng yếu';
        }
        if ($parts === [] && $optional > 0) {
            $parts[] = 'Chỉ cần phát triển thêm';
        } elseif ($optional > 0) {
            $parts[] = $optional . ' kỹ năng bổ sung';
        }

        return $parts !== [] ? implode(' · ', $parts) : 'Không có thiếu hụt lớn';
    }
}

if (!function_exists('job_recommendation_compute_summary_stats')) {
    /**
     * @param list<array<string, mixed>> $topJobs
     * @return array{top_count: int, high_fit_count: int, missing_must_have_jobs: int, optional_only_jobs: int}
     */
    function job_recommendation_compute_summary_stats(array $topJobs): array
    {
        $highFit = 0;
        $missingJobs = 0;
        $optionalOnly = 0;

        foreach ($topJobs as $job) {
            if (!is_array($job)) {
                continue;
            }
            $label = strtolower(trim((string) ($job['fit_label'] ?? '')));
            if (str_contains($label, 'strong') || str_contains($label, 'good')) {
                $highFit++;
            }

            $summary = is_array($job['skill_gap_summary'] ?? null) ? $job['skill_gap_summary'] : [];
            $missing = (int) ($summary['missing_must_have_count'] ?? 0);
            $optional = (int) ($summary['optional_growth_count'] ?? 0);
            if ($missing > 0) {
                $missingJobs++;
            } elseif ($optional > 0) {
                $optionalOnly++;
            }
        }

        return [
            'top_count' => count($topJobs),
            'high_fit_count' => $highFit,
            'missing_must_have_jobs' => $missingJobs,
            'optional_only_jobs' => $optionalOnly,
        ];
    }
}

if (!function_exists('job_recommendation_session_key')) {
    function job_recommendation_session_key(): string
    {
        return 'job_recommendation_last';
    }
}

if (!function_exists('job_recommendation_session_schema_version')) {
    /**
     * Bump when response shape changes.
     */
    function job_recommendation_session_schema_version(): int
    {
        return 5;
    }
}

if (!function_exists('job_recommendation_quality_label_vi')) {
    function job_recommendation_quality_label_vi(?string $label): string
    {
        $key = strtolower(trim((string) $label));

        return match ($key) {
            'insufficient_jd_data' => 'Thiếu dữ liệu JD',
            'eligible_with_warning' => 'JD cần xem lại',
            'eligible' => 'Đủ dữ liệu',
            default => $label !== null && trim($label) !== '' ? trim($label) : '—',
        };
    }
}

if (!function_exists('job_recommendation_translate_quality_reason')) {
    function job_recommendation_translate_quality_reason(string $reason): string
    {
        $map = [
            'Job title looks like a placeholder.' => 'Tiêu đề tin có vẻ là placeholder.',
            'JD content only contains placeholder-like text.' => 'Nội dung JD chỉ chứa text placeholder.',
            'JD content is too short after cleaning.' => 'Mô tả tuyển dụng quá ngắn sau khi làm sạch.',
            'No meaningful must-have requirements were detected.' => 'Không phát hiện yêu cầu bắt buộc có ý nghĩa.',
            'No meaningful responsibilities were detected.' => 'Không phát hiện mô tả công việc có ý nghĩa.',
            'JD has too few meaningful requirement or responsibility lines.' => 'JD có quá ít dòng yêu cầu hoặc trách nhiệm.',
            'JD does not contain enough meaningful technical vocabulary.' => 'JD thiếu từ vựng kỹ thuật có ý nghĩa.',
        ];

        return $map[$reason] ?? $reason;
    }
}

if (!function_exists('job_recommendation_jd_quality_warning_badge_html')) {
    /**
     * Badge phụ khi job vẫn eligible nhưng JD có cảnh báo chất lượng.
     *
     * @param array<string, mixed>|null $jobQuality
     */
    function job_recommendation_jd_quality_warning_badge_html(?array $jobQuality): string
    {
        if ($jobQuality === null) {
            return '';
        }

        $label = strtolower(trim((string) ($jobQuality['quality_label'] ?? '')));
        if ($label !== 'eligible_with_warning') {
            return '';
        }

        $flags = is_array($jobQuality['flags'] ?? null) ? $jobQuality['flags'] : [];
        $flagSet = array_map('strval', $flags);
        $text = 'Dữ liệu JD hạn chế';
        if (in_array('description_too_short', $flagSet, true)) {
            $text = 'JD ngắn';
        } elseif (in_array('missing_responsibilities', $flagSet, true)) {
            $text = 'JD cần xem lại';
        } elseif (
            in_array('sparse_job_signal', $flagSet, true)
            || in_array('low_signal_vocabulary', $flagSet, true)
        ) {
            $text = 'Dữ liệu JD hạn chế';
        }

        return '<span class="badge bg-light text-dark border mt-1" title="JD có thể ảnh hưởng độ chính xác gợi ý">'
            . htmlspecialchars($text)
            . '</span>';
    }
}

if (!function_exists('job_recommendation_gap_counts_line')) {
    /**
     * Hiển thị số lượng thiếu hụt theo skill_gap_summary.
     *
     * @param array<string, mixed> $jobRow
     */
    function job_recommendation_gap_counts_line(array $jobRow): string
    {
        $summary = is_array($jobRow['skill_gap_summary'] ?? null) ? $jobRow['skill_gap_summary'] : [];
        $missing = (int) ($summary['missing_must_have_count'] ?? 0);
        $weak = (int) ($summary['weak_evidence_count'] ?? 0);
        $optional = (int) ($summary['optional_growth_count'] ?? 0);

        $parts = [];
        if ($missing > 0) {
            $parts[] = $missing . ' bắt buộc';
        }
        if ($optional > 0) {
            $parts[] = $optional . ' bổ sung';
        }
        if ($weak > 0) {
            $parts[] = $weak . ' bằng chứng yếu';
        }

        return $parts !== [] ? implode(' · ', $parts) : 'Không có thiếu hụt lớn';
    }
}

if (!function_exists('job_recommendation_excluded_reasons_line')) {
    /**
     * @param array<string, mixed> $jobRow
     */
    function job_recommendation_excluded_reasons_line(array $jobRow, int $max = 2): string
    {
        $quality = is_array($jobRow['job_quality'] ?? null) ? $jobRow['job_quality'] : [];
        $reasons = is_array($quality['reasons'] ?? null) ? $quality['reasons'] : [];
        $parts = [];
        foreach (array_slice($reasons, 0, max(1, $max)) as $reason) {
            if (!is_string($reason) || trim($reason) === '') {
                continue;
            }
            $parts[] = job_recommendation_translate_quality_reason(trim($reason));
        }

        return $parts !== [] ? implode(' · ', $parts) : 'Mô tả tuyển dụng chưa đủ để AI đánh giá.';
    }
}

if (!function_exists('job_recommendation_decision_confidence_badge_html')) {
    /**
     * @param array<string, mixed>|null $confidence
     */
    function job_recommendation_decision_confidence_badge_html(?array $confidence): string
    {
        if ($confidence === null || $confidence === []) {
            return '';
        }

        $level = strtolower(trim((string) ($confidence['level'] ?? '')));
        $reviewRequired = !empty($confidence['review_required']);
        $class = match ($level) {
            'high' => 'bg-success-subtle text-success border-success-subtle',
            'medium' => 'bg-warning-subtle text-warning border-warning-subtle',
            'low' => 'bg-danger-subtle text-danger border-danger-subtle',
            default => 'bg-light text-dark border',
        };
        $label = $level !== '' ? ucfirst($level) : 'Confidence';
        if ($reviewRequired) {
            $label .= ' · review';
        }

        return '<span class="badge ' . $class . ' border mt-1" title="Decision confidence">'
            . htmlspecialchars($label)
            . '</span>';
    }
}

if (!function_exists('job_recommendation_confidence_guardrails_line')) {
    /**
     * @param array<string, mixed>|null $guardrails
     */
    function job_recommendation_confidence_guardrails_line(?array $guardrails): string
    {
        if ($guardrails === null || $guardrails === []) {
            return '';
        }

        $parts = [];
        $level = trim((string) ($guardrails['level'] ?? ''));
        if ($level !== '') {
            $parts[] = 'Level: ' . $level;
        }
        if (!empty($guardrails['review_required'])) {
            $parts[] = 'Cần review';
        }
        $reasonCodes = is_array($guardrails['reason_codes'] ?? null) ? $guardrails['reason_codes'] : [];
        if ($reasonCodes !== []) {
            $parts[] = 'Reason codes: ' . implode(', ', array_map('strval', array_slice($reasonCodes, 0, 4)));
        }

        return $parts !== [] ? implode(' · ', $parts) : '';
    }
}
