<?php

require_once __DIR__ . '/ai_screening_config.php';

if (!function_exists('ai_diag_debug_enabled')) {
    function ai_diag_debug_enabled(): bool
    {
        if (isset($_GET['debug']) && (string) $_GET['debug'] === '1') {
            $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
            if ($host === '' || str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
                return true;
            }
        }

        if (!empty($_SESSION['ai_diag_debug'])) {
            return true;
        }

        $cfg = ai_screening_config();

        return !empty($cfg['debug_ui_diagnostics']) || !empty($cfg['debug_api_payload']);
    }
}

if (!function_exists('ai_diag_normalize_confidence_level')) {
    function ai_diag_normalize_confidence_level(?string $level): string
    {
        $key = strtolower(trim((string) $level));

        return match ($key) {
            'high' => 'high',
            'medium', 'med' => 'medium',
            'low' => 'low',
            default => '',
        };
    }
}

if (!function_exists('ai_diag_confidence_badge')) {
    /**
     * @return array{label: string, class: string, alert_class: string}
     */
    function ai_diag_confidence_badge(string $level): array
    {
        return match (ai_diag_normalize_confidence_level($level)) {
            'high' => [
                'label' => 'Cao',
                'class' => 'bg-success-subtle text-success border-success-subtle',
                'alert_class' => 'alert-success',
            ],
            'medium' => [
                'label' => 'Trung bình',
                'class' => 'bg-warning-subtle text-warning border-warning-subtle',
                'alert_class' => 'alert-warning',
            ],
            'low' => [
                'label' => 'Thấp',
                'class' => 'bg-danger-subtle text-danger border-danger-subtle',
                'alert_class' => 'alert-danger',
            ],
            default => [
                'label' => 'Chưa xác định',
                'class' => 'bg-light text-dark border',
                'alert_class' => 'alert-secondary',
            ],
        };
    }
}

if (!function_exists('ai_diag_review_status_label')) {
    function ai_diag_review_status_label(bool $reviewRequired, string $level): string
    {
        $normalized = ai_diag_normalize_confidence_level($level);
        if ($normalized === 'low') {
            return 'Kết quả độ tin cậy thấp';
        }
        if ($reviewRequired || $normalized === 'medium') {
            return 'Nên xem lại';
        }

        return 'Đáng tin cậy';
    }
}

if (!function_exists('ai_diag_humanize_reason_code')) {
    function ai_diag_humanize_reason_code(string $code): string
    {
        $key = strtolower(trim($code));
        $map = [
            'sparse_recovery_active' => 'JD thiếu yêu cầu kỹ thuật rõ ràng, AI đã suy luận thêm từ phần trách nhiệm.',
            'explicit_technical_core_sparse' => 'JD có quá ít yêu cầu kỹ thuật được nêu rõ.',
            'promoted_source_dominant' => 'Phần lớn đánh giá kỹ thuật dựa trên mô tả trách nhiệm thay vì yêu cầu rõ ràng.',
            'unknown_requirement_count_high' => 'Nhiều yêu cầu cần diễn giải ngữ nghĩa cẩn thận hơn.',
            'weak_hard_skill_confirmation' => 'Bằng chứng kỹ năng cứng còn yếu hoặc chưa đầy đủ.',
            'evidence_mostly_keyword_level' => 'Phần lớn bằng chứng mới ở mức từ khóa.',
            'direct_evidence_sparse' => 'Bằng chứng trực tiếp từ kinh nghiệm làm việc còn hạn chế.',
            'jd_quality_warning_present' => 'Chất lượng nội dung JD chưa đủ mạnh.',
        ];

        return $map[$key] ?? '';
    }
}

if (!function_exists('ai_diag_humanize_reason_codes')) {
    /**
     * @param list<mixed> $codes
     * @return list<string>
     */
    function ai_diag_humanize_reason_codes(array $codes): array
    {
        $out = [];
        $seen = [];
        foreach ($codes as $code) {
            if (!is_string($code) || trim($code) === '') {
                continue;
            }
            $text = ai_diag_humanize_reason_code(trim($code));
            if ($text === '' || isset($seen[$text])) {
                continue;
            }
            $seen[$text] = true;
            $out[] = $text;
        }

        return $out;
    }
}

if (!function_exists('ai_diag_extract_confidence_block')) {
    /**
     * @param array<string, mixed> $jobApi
     * @param array<string, mixed> $context
     * @return array{level: string, review_required: bool, reason_codes: list<string>}
     */
    function ai_diag_extract_confidence_block(array $jobApi, array $context = []): array
    {
        $guardrails = is_array($jobApi['confidence_guardrails'] ?? null)
            ? $jobApi['confidence_guardrails']
            : [];
        $screeningConfidence = is_array($context['screening_confidence'] ?? null)
            ? $context['screening_confidence']
            : [];

        $level = ai_diag_normalize_confidence_level((string) (
            $guardrails['level']
            ?? $screeningConfidence['level']
            ?? ''
        ));
        $reviewRequired = !empty($guardrails['review_required'])
            || $level === 'medium'
            || $level === 'low';

        $reasonCodes = [];
        if (is_array($guardrails['reason_codes'] ?? null)) {
            foreach ($guardrails['reason_codes'] as $code) {
                if (is_string($code) && trim($code) !== '') {
                    $reasonCodes[] = trim($code);
                }
            }
        }

        return [
            'level' => $level,
            'review_required' => $reviewRequired,
            'reason_codes' => array_values(array_unique($reasonCodes)),
        ];
    }
}

if (!function_exists('ai_diag_recruiter_summary_text')) {
    function ai_diag_recruiter_summary_text(string $level, bool $reviewRequired): string
    {
        return match (ai_diag_normalize_confidence_level($level)) {
            'high' => 'Độ tin cậy AI cao. JD và bằng chứng ứng viên đủ mạnh để xếp hạng đáng tin cậy.',
            'medium' => 'Độ tin cậy AI trung bình. Hãy xem lại vì một phần mức phù hợp kỹ thuật được suy luận từ JD còn hạn chế.',
            'low' => 'Độ tin cậy AI thấp. Nên coi xếp hạng như gợi ý sàng lọc ban đầu và review thêm trước khi quyết định.',
            default => $reviewRequired
                ? 'AI khuyến nghị recruiter xem lại kết quả trước khi quyết định.'
                : 'Kết quả AI sẵn sàng để tham khảo.',
        };
    }
}

if (!function_exists('ai_diag_recruiter_technical_details')) {
    /**
     * @param array<string, mixed> $jobApi
     * @param array<string, mixed> $context
     * @param array<string, mixed> $confidence
     * @return list<array{label: string, value: string}>
     */
    function ai_diag_recruiter_technical_details(array $jobApi, array $context, array $confidence): array
    {
        $lines = [];
        $level = (string) ($confidence['level'] ?? '');
        if ($level !== '') {
            $lines[] = [
                'label' => 'Mức độ tin cậy',
                'value' => ai_diag_confidence_badge($level)['label'],
            ];
        }

        $lines[] = [
            'label' => 'Cần review thêm',
            'value' => !empty($confidence['review_required']) ? 'Có' : 'Không',
        ];

        $whyReview = ai_diag_humanize_reason_codes(is_array($confidence['reason_codes'] ?? null) ? $confidence['reason_codes'] : []);
        if ($whyReview !== []) {
            $lines[] = [
                'label' => 'Vì sao nên xem lại',
                'value' => implode(' ', $whyReview),
            ];
        }

        $openSet = is_array($jobApi['open_set_requirements'] ?? null) ? $jobApi['open_set_requirements'] : [];
        if ($openSet !== []) {
            $lines[] = ['label' => 'Số yêu cầu open-set', 'value' => (string) count($openSet)];
        }

        $promoted = is_array($jobApi['promoted_requirements'] ?? null) ? $jobApi['promoted_requirements'] : [];
        if ($promoted !== []) {
            $lines[] = ['label' => 'Số yêu cầu được promote', 'value' => (string) count($promoted)];
        }

        $sparseRecovery = in_array('sparse_recovery_active', $confidence['reason_codes'] ?? [], true)
            || is_array($jobApi['explicit_technical_recovery_summary'] ?? null);
        $lines[] = [
            'label' => 'Kích hoạt sparse technical recovery',
            'value' => $sparseRecovery ? 'Có' : 'Không',
        ];

        $candidateFlagged = (int) ($context['candidate_flagged_count'] ?? 0);
        if ($candidateFlagged > 0) {
            $lines[] = [
                'label' => 'CV cần xem kỹ hơn',
                'value' => (string) $candidateFlagged . ' hồ sơ',
            ];
        }

        return $lines;
    }
}

if (!function_exists('ai_diag_recruiter_banner')) {
    /**
     * @param array<string, mixed> $jobApi
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    function ai_diag_recruiter_banner(array $jobApi, array $context = []): array
    {
        $confidence = ai_diag_extract_confidence_block($jobApi, $context);
        $level = $confidence['level'];
        $candidateFlagged = (int) ($context['candidate_flagged_count'] ?? 0);
        $summary = ai_diag_recruiter_summary_text($level, $confidence['review_required']);

        if ($candidateFlagged > 0) {
            $summary .= ' Một số CV đầu vào còn ngắn hoặc thiếu chi tiết — nên review kỹ hơn.';
        }

        $hasRun = !empty($context['has_screening_run']);
        $show = $hasRun && (
            $level !== ''
            || $confidence['review_required']
            || $candidateFlagged > 0
            || is_array($jobApi['confidence_guardrails'] ?? null)
        );

        return [
            'show' => $show,
            'badge' => ai_diag_confidence_badge($level !== '' ? $level : 'unknown'),
            'review_status' => ai_diag_review_status_label($confidence['review_required'], $level),
            'summary' => $summary,
            'technical_details' => ai_diag_recruiter_technical_details($jobApi, $context, $confidence),
            'confidence' => $confidence,
        ];
    }
}

if (!function_exists('ai_diag_candidate_notice')) {
    /**
     * @param array<string, mixed> $sessionResult
     * @return array{show: bool, main: string, note: string, cv_note: string}
     */
    function ai_diag_candidate_notice(array $sessionResult): array
    {
        $qualityStats = is_array($sessionResult['job_quality_stats'] ?? null) ? $sessionResult['job_quality_stats'] : [];
        $excludedJobs = is_array($sessionResult['excluded_jobs'] ?? null) ? $sessionResult['excluded_jobs'] : [];
        $excludedCount = count($excludedJobs);
        if ($excludedCount === 0) {
            $excludedCount = (int) ($qualityStats['excluded_jobs'] ?? 0);
        }

        $diagnostics = is_array($sessionResult['diagnostics'] ?? null) ? $sessionResult['diagnostics'] : [];
        $diagPayload = is_array($diagnostics['payload'] ?? null) ? $diagnostics['payload'] : [];
        $diagCandidate = is_array($diagPayload['candidate'] ?? null) ? $diagPayload['candidate'] : [];
        $candidateFlags = is_array($diagCandidate['flags'] ?? null) ? $diagCandidate['flags'] : [];
        $cvWeak = array_intersect(
            array_map('strval', $candidateFlags),
            ['cv_text_too_short', 'candidate_profile_sparse', 'html_cleaning_changed_text_heavily']
        ) !== [];

        $main = '';
        $note = '';
        $cvNote = '';

        if ($excludedCount > 0) {
            $main = 'Một số tin đã bị loại vì mô tả công việc chưa đủ chi tiết để AI so khớp đáng tin cậy.';
            $note = 'Kết quả hiển thị dựa trên các tin có nội dung đủ cấu trúc để đánh giá.';
        }

        if ($cvWeak) {
            $cvNote = 'CV hiện tại có thể chưa đủ thông tin để AI đánh giá tối ưu. Hãy bổ sung kỹ năng, kinh nghiệm và mục tiêu nghề nghiệp.';
        }

        return [
            'show' => $main !== '' || $cvNote !== '',
            'main' => $main,
            'note' => $note,
            'cv_note' => $cvNote,
        ];
    }
}

if (!function_exists('ai_diag_excluded_job_reason')) {
    /**
     * @param array<string, mixed> $excludedJob
     */
    function ai_diag_excluded_job_reason(array $excludedJob): string
    {
        $quality = is_array($excludedJob['job_quality'] ?? null) ? $excludedJob['job_quality'] : [];
        $label = strtolower(trim((string) ($quality['quality_label'] ?? '')));
        $flags = is_array($quality['flags'] ?? null) ? array_map('strval', $quality['flags']) : [];

        if ($label === 'insufficient_jd_data') {
            return 'Mô tả tuyển dụng quá ngắn hoặc thiếu yêu cầu rõ ràng.';
        }

        if (array_intersect($flags, ['placeholder_title', 'placeholder_jd', 'placeholder_content', 'placeholder_like_jd']) !== []) {
            return 'Nội dung tin có vẻ là bài đăng thử nghiệm hoặc placeholder.';
        }

        if (in_array('description_too_short', $flags, true)) {
            return 'Mô tả tuyển dụng quá ngắn để AI đánh giá đáng tin cậy.';
        }

        if (in_array('missing_responsibilities', $flags, true) || in_array('missing_requirements', $flags, true)) {
            return 'Tin chưa cung cấp đủ yêu cầu hoặc trách nhiệm công việc rõ ràng.';
        }

        return 'Tin chưa đủ dữ liệu để đưa vào gợi ý AI.';
    }
}

if (!function_exists('ai_diag_render_debug_block')) {
    /**
     * @param array<string, mixed> $data
     */
    function ai_diag_render_debug_block(array $data): string
    {
        if (!ai_diag_debug_enabled()) {
            return '';
        }

        ob_start();
        ?>
        <details class="mb-4 border rounded-3 p-3 bg-light">
            <summary class="fw-bold text-muted small">Debug diagnostics</summary>
            <div class="small text-muted mt-3">
                <?php if (!empty($data['trace_id'])): ?>
                    <div><strong>Trace ID:</strong> <code><?= htmlspecialchars((string) $data['trace_id']) ?></code></div>
                <?php endif; ?>
                <?php if (!empty($data['run_id'])): ?>
                    <div><strong>Run ID:</strong> <code><?= htmlspecialchars((string) $data['run_id']) ?></code></div>
                <?php endif; ?>
                <?php if (!empty($data['reason_codes']) && is_array($data['reason_codes'])): ?>
                    <div class="mt-2"><strong>Raw reason codes:</strong> <?= htmlspecialchars(implode(', ', array_map('strval', $data['reason_codes']))) ?></div>
                <?php endif; ?>
                <?php if (!empty($data['excluded_job_ids']) && is_array($data['excluded_job_ids'])): ?>
                    <div class="mt-2"><strong>Excluded job IDs:</strong> <?= htmlspecialchars(implode(', ', array_map('strval', $data['excluded_job_ids']))) ?></div>
                <?php endif; ?>
                <?php if (!empty($data['top_job_ids']) && is_array($data['top_job_ids'])): ?>
                    <div class="mt-2"><strong>Top job IDs:</strong> <?= htmlspecialchars(implode(', ', array_map('strval', $data['top_job_ids']))) ?></div>
                <?php endif; ?>
                <?php if (!empty($data['runtime_json'])): ?>
                    <div class="mt-2"><strong>Runtime JSON:</strong></div>
                    <pre class="small mb-0 mt-1 p-2 bg-white border rounded"><?= htmlspecialchars((string) $data['runtime_json']) ?></pre>
                <?php endif; ?>
            </div>
        </details>
        <?php

        return (string) ob_get_clean();
    }
}
