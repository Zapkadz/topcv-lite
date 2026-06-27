<?php

if (!function_exists('ai_ui_supported_langs')) {
    /**
     * @return list<string>
     */
    function ai_ui_supported_langs(): array
    {
        return ['en', 'vi'];
    }
}

if (!function_exists('ai_ui_lang')) {
    function ai_ui_lang(): string
    {
        $langs = ai_ui_supported_langs();
        $stored = '';
        if (isset($_COOKIE['topcv_ai_ui_lang'])) {
            $stored = strtolower(trim((string) $_COOKIE['topcv_ai_ui_lang']));
        }
        if ($stored !== '' && in_array($stored, $langs, true)) {
            return $stored;
        }

        return 'en';
    }
}

if (!function_exists('ai_ui_is_english')) {
    function ai_ui_is_english(string $lang): bool
    {
        return strtolower(trim($lang)) !== 'vi';
    }
}

if (!function_exists('ai_i18n_labels')) {
    /**
     * @return array<string, array<string, string>>
     */
    function ai_i18n_labels(): array
    {
        return [
            'summary' => ['en' => 'Summary', 'vi' => 'Tóm tắt'],
            'strengths' => ['en' => 'Strengths', 'vi' => 'Điểm mạnh'],
            'concerns' => ['en' => 'Concerns', 'vi' => 'Lưu ý / thiếu sót'],
            'requirement_notes' => ['en' => 'Requirement Notes', 'vi' => 'Ghi chú yêu cầu'],
            'evidence_highlights' => ['en' => 'Evidence Highlights', 'vi' => 'Bằng chứng nổi bật'],
            'suggested_interview_questions' => ['en' => 'Suggested Interview Questions', 'vi' => 'Gợi ý câu hỏi phỏng vấn'],
            'score_flow' => ['en' => 'Score Flow', 'vi' => 'Luồng điểm'],
            'core_requirement_fit' => ['en' => 'Core Requirement Fit', 'vi' => 'Độ phủ yêu cầu cốt lõi'],
            'decision_confidence' => ['en' => 'Decision Confidence', 'vi' => 'Độ tin cậy quyết định'],
            'technical_details' => ['en' => 'Technical Details', 'vi' => 'Chi tiết kỹ thuật'],
            'why_fit' => ['en' => 'Why this job fits', 'vi' => 'Vì sao phù hợp'],
            'gaps' => ['en' => 'Missing or weak areas', 'vi' => 'Điểm còn thiếu / yếu'],
            'improve_cv' => ['en' => 'How to improve your CV', 'vi' => 'Cách cải thiện CV'],
            'skill_evidence' => ['en' => 'Skill Evidence', 'vi' => 'Bằng chứng kỹ năng'],
            'excluded_jobs' => ['en' => 'Jobs not included in AI matching', 'vi' => 'Việc làm không đưa vào gợi ý AI'],
            'jd_quality' => ['en' => 'JD Quality', 'vi' => 'Chất lượng JD'],
            'open_set_requirements' => ['en' => 'Open-set Requirements', 'vi' => 'Yêu cầu open-set'],
            'job_confidence_guardrails' => ['en' => 'Job Confidence Guardrails', 'vi' => 'Rào chắn độ tin cậy JD'],
            'confidence_technical_footer' => ['en' => 'Decision confidence & technical details', 'vi' => 'Độ tin cậy & chi tiết kỹ thuật'],
            'ai_diagnostics' => ['en' => 'AI Diagnostics', 'vi' => 'AI diagnostics'],
            'technical_recovery_summary' => ['en' => 'Technical Recovery Summary', 'vi' => 'Tóm tắt khôi phục kỹ thuật'],
            'promoted_requirements' => ['en' => 'Promoted Requirements', 'vi' => 'Yêu cầu được promote'],
            'technical_responsibility_candidates' => ['en' => 'Technical Responsibility Candidates', 'vi' => 'Ứng viên trách nhiệm kỹ thuật'],
            'next_actions' => ['en' => 'Next best actions', 'vi' => 'Việc nên làm tiếp'],
            'cv_suggestions' => ['en' => 'Detailed suggestions', 'vi' => 'Gợi ý chi tiết'],
            'missing_must_have' => ['en' => 'Missing must-have', 'vi' => 'Thiếu bắt buộc'],
            'weak_evidence' => ['en' => 'Weak evidence', 'vi' => 'Bằng chứng yếu'],
            'optional_growth' => ['en' => 'Optional growth', 'vi' => 'Phát triển thêm'],
            'matched_skills' => ['en' => 'Matched must-have skills', 'vi' => 'Kỹ năng bắt buộc khớp'],
            'role_adjustment' => ['en' => 'AI adjusted score by role-family / core evidence', 'vi' => 'AI có điều chỉnh điểm theo role-family/core evidence'],
            'ai_review_title' => ['en' => 'AI review', 'vi' => 'Đánh giá AI'],
            'rec_detail_title' => ['en' => 'Recommendation detail', 'vi' => 'Chi tiết gợi ý'],
            'close' => ['en' => 'Close', 'vi' => 'Đóng'],
            'rank' => ['en' => 'Rank', 'vi' => 'Hạng'],
            'score' => ['en' => 'Score', 'vi' => 'Điểm'],
            'points' => ['en' => 'points', 'vi' => 'điểm'],
            'weighted_score' => ['en' => 'Weighted score', 'vi' => 'Điểm có trọng số'],
            'role_calibrated_score' => ['en' => 'Role-calibrated score', 'vi' => 'Điểm hiệu chỉnh theo role'],
            'final_score' => ['en' => 'Final score', 'vi' => 'Điểm cuối'],
            'role_score_adjustment' => ['en' => 'Role score adjustment', 'vi' => 'Điều chỉnh điểm theo role'],
            'role_adjustment_reason' => ['en' => 'Role-aware adjustment reason', 'vi' => 'Lý do điều chỉnh theo role'],
            'core_requirements_counted' => ['en' => 'Core requirements counted', 'vi' => 'Số yêu cầu cốt lõi'],
            'positive_coverage' => ['en' => 'Positive coverage', 'vi' => 'Độ phủ tích cực'],
            'confirmed_coverage' => ['en' => 'Confirmed coverage', 'vi' => 'Độ phủ đã xác nhận'],
            'semantic_only_ratio' => ['en' => 'Semantic-only ratio', 'vi' => 'Tỷ lệ chỉ ngữ nghĩa'],
            'level' => ['en' => 'Level', 'vi' => 'Mức'],
            'review_required' => ['en' => 'Review required', 'vi' => 'Cần review thêm'],
            'reason_codes' => ['en' => 'Reason codes', 'vi' => 'Lý do'],
            'yes' => ['en' => 'yes', 'vi' => 'có'],
            'no' => ['en' => 'no', 'vi' => 'không'],
            'trace_id' => ['en' => 'Trace ID', 'vi' => 'Trace ID'],
            'run_id' => ['en' => 'Run ID', 'vi' => 'Run ID'],
            'job_payload_flags' => ['en' => 'Job payload flags', 'vi' => 'Job payload flags'],
            'candidate_flagged_count' => ['en' => 'Candidate flagged count', 'vi' => 'Số CV bị gắn cờ'],
            'job_quality_label' => ['en' => 'Job quality label', 'vi' => 'Nhãn chất lượng JD'],
            'job_quality_reasons' => ['en' => 'Job quality reasons', 'vi' => 'Lý do chất lượng JD'],
            'screening_confidence_level' => ['en' => 'Screening confidence level', 'vi' => 'Mức tin cậy screening'],
            'known_requirements' => ['en' => 'Known requirements', 'vi' => 'Yêu cầu đã biết'],
            'embedding_enabled' => ['en' => 'Embedding enabled', 'vi' => 'Bật embedding'],
            'warnings' => ['en' => 'Warnings', 'vi' => 'Cảnh báo'],
            'quality_score' => ['en' => 'Quality score', 'vi' => 'Điểm chất lượng'],
            'label' => ['en' => 'Label', 'vi' => 'Nhãn'],
            'reasons' => ['en' => 'Reasons', 'vi' => 'Lý do'],
            'no_summary' => ['en' => 'No summary available.', 'vi' => 'Chưa có tóm tắt.'],
            'no_strengths' => ['en' => 'No strengths recorded.', 'vi' => 'Chưa ghi nhận điểm mạnh.'],
            'no_concerns' => ['en' => 'No concerns recorded.', 'vi' => 'Chưa ghi nhận lưu ý.'],
            'no_evidence' => ['en' => 'No evidence highlights yet.', 'vi' => 'Chưa có bằng chứng nổi bật.'],
            'no_questions' => ['en' => 'No interview questions suggested.', 'vi' => 'Chưa có gợi ý câu hỏi.'],
            'no_reasons' => ['en' => 'No specific reasons listed.', 'vi' => 'Chưa có lý do cụ thể.'],
            'no_suggestions' => ['en' => 'No suggestions yet.', 'vi' => 'Chưa có gợi ý.'],
            'no_gap_items' => ['en' => 'No items in this category.', 'vi' => 'Không có mục nào.'],
            'no_major_gaps' => ['en' => 'No major gaps.', 'vi' => 'Không có thiếu hụt lớn.'],
            'no_matched_skills' => ['en' => 'No clearly matched must-have skills.', 'vi' => 'Chưa có kỹ năng khớp rõ.'],
            'missing_must_have_count' => ['en' => 'Missing must-have', 'vi' => 'Thiếu bắt buộc'],
            'weak_evidence_count' => ['en' => 'Weak evidence', 'vi' => 'Bằng chứng yếu'],
            'optional_growth_count' => ['en' => 'Optional growth', 'vi' => 'Phát triển thêm'],
        ];
    }
}

if (!function_exists('ai_i18n_enum_maps')) {
    /**
     * @return array<string, array<string, string>>
     */
    function ai_i18n_enum_maps(): array
    {
        return [
            'fit_label' => [
                'Strong Fit' => 'Rất phù hợp',
                'Good Fit' => 'Phù hợp tốt',
                'Potential Fit' => 'Có tiềm năng phù hợp',
                'Stretch' => 'Phù hợp một phần',
                'Low Fit' => 'Phù hợp thấp',
            ],
            'recommendation' => [
                'Strong Review' => 'Đề xuất mạnh',
                'Review' => 'Nên xem xét',
                'Maybe Review' => 'Có thể xem xét',
                'Not Enough Evidence' => 'Chưa đủ bằng chứng',
                'Low Priority' => 'Ưu tiên thấp',
                'High Priority' => 'Ưu tiên cao',
                'Consider' => 'Cân nhắc',
                'Reject' => 'Loại',
                'Not Recommended' => 'Không đề xuất',
            ],
            'confidence_level' => [
                'high' => 'Cao',
                'medium' => 'Trung bình',
                'low' => 'Thấp',
            ],
            'evidence_source' => [
                'work_experience' => 'kinh nghiệm làm việc',
                'project' => 'dự án',
                'projects' => 'dự án',
                'education' => 'học vấn',
                'certification' => 'chứng chỉ',
                'summary' => 'tóm tắt',
                'skills' => 'kỹ năng',
            ],
            'reason_code' => [
                'sparse_recovery_active' => 'JD thiếu yêu cầu kỹ thuật rõ ràng, AI đã suy luận thêm từ phần trách nhiệm.',
                'explicit_technical_core_sparse' => 'JD có quá ít yêu cầu kỹ thuật được nêu rõ.',
                'explicit_technical_contamination_detected' => 'Phát hiện nhiễu trong các dòng yêu cầu kỹ thuật được nêu rõ.',
                'promoted_source_dominant' => 'Phần lớn đánh giá kỹ thuật dựa trên mô tả trách nhiệm thay vì yêu cầu rõ ràng.',
                'unknown_requirement_count_high' => 'Nhiều yêu cầu cần diễn giải ngữ nghĩa cẩn thận hơn.',
                'weak_hard_skill_confirmation' => 'Bằng chứng kỹ năng cứng còn yếu hoặc chưa đầy đủ.',
                'evidence_mostly_keyword_level' => 'Phần lớn bằng chứng mới ở mức từ khóa.',
                'direct_evidence_sparse' => 'Bằng chứng trực tiếp từ kinh nghiệm làm việc còn hạn chế.',
                'jd_quality_warning_present' => 'Chất lượng nội dung JD chưa đủ mạnh.',
                'source_alignment_adjustment_applied' => 'Đã điều chỉnh điểm theo mức phù hợp nguồn bằng chứng.',
            ],
        ];
    }
}

if (!function_exists('ai_i18n_phrase_map')) {
    /**
     * @return array<string, string>
     */
    function ai_i18n_phrase_map(): array
    {
        return [
            'Strong must-have skill coverage.' => 'Độ phủ kỹ năng bắt buộc mạnh.',
            'Strong must-have skill overlap.' => 'Mức trùng khớp kỹ năng bắt buộc mạnh.',
            'Solid must-have skill coverage.' => 'Độ phủ kỹ năng bắt buộc khá tốt.',
            'Strong evidence in work or project descriptions.' => 'Có bằng chứng mạnh trong kinh nghiệm làm việc hoặc dự án.',
            'Some practical evidence is present.' => 'Đã có một phần bằng chứng thực tế.',
            'Good domain alignment with the role.' => 'Mức độ phù hợp domain với vị trí này khá tốt.',
            'Seniority appears aligned with the role.' => 'Mức độ seniority có vẻ phù hợp với vị trí.',
            'Experience level appears close to the requirement.' => 'Số năm kinh nghiệm có vẻ gần với yêu cầu.',
            'Role-family alignment appears strong for this position.' => 'Mức độ phù hợp nhóm vai trò với vị trí này khá mạnh.',
            'Core technical requirements are still missing or weakly evidenced.' => 'Các yêu cầu kỹ thuật cốt lõi vẫn còn thiếu hoặc có bằng chứng còn yếu.',
            'Evidence is weak or mostly keyword-level.' => 'Bằng chứng còn yếu hoặc chủ yếu mới ở mức từ khóa.',
            'Limited direct evidence for several core requirements.' => 'Bằng chứng trực tiếp cho một số yêu cầu cốt lõi còn hạn chế.',
            'Several requirements rely on semantic inference rather than explicit evidence.' => 'Một số yêu cầu chủ yếu dựa trên suy luận ngữ nghĩa thay vì bằng chứng rõ ràng.',
            'CV presentation could be strengthened with clearer project outcomes.' => 'CV có thể mạnh hơn nếu mô tả kết quả dự án rõ ràng hơn.',
            'Add more concrete examples for top must-have skills.' => 'Bổ sung ví dụ cụ thể cho các kỹ năng bắt buộc quan trọng.',
            'Highlight measurable outcomes in recent work experience.' => 'Làm nổi bật kết quả đo lường được trong kinh nghiệm gần đây.',
            'Clarify ownership and impact for key technical tasks.' => 'Làm rõ phần việc bạn phụ trách và tác động của các nhiệm vụ kỹ thuật chính.',
            'Expand project descriptions with tools, scale, and results.' => 'Mở rộng mô tả dự án với công cụ, quy mô và kết quả.',
            'Consider adding certifications or training for missing core skills.' => 'Cân nhắc bổ sung chứng chỉ hoặc đào tạo cho các kỹ năng cốt lõi còn thiếu.',
            'JD quality is limited; treat ranking as directional only.' => 'Chất lượng JD còn hạn chế; nên xem xếp hạng như gợi ý định hướng.',
            'insufficient_jd_data' => 'Dữ liệu JD chưa đủ',
            'placeholder_like_jd' => 'Nội dung JD giống placeholder',
            'description_too_short' => 'Mô tả quá ngắn',
            'Most core technical requirements have confirmed evidence.' => 'Hầu hết yêu cầu kỹ thuật cốt lõi đã có bằng chứng xác nhận.',
            'If you have real exposure to Preferred Qualifications, add it as an optional growth strength for similar roles.' => 'Nếu bạn có kinh nghiệm thực tế với các yêu cầu ưu tiên, hãy bổ sung như điểm phát triển thêm cho các vị trí tương tự.',
            "The candidate's strongest technical profile aligns with the JD role family." => 'Hồ sơ kỹ thuật mạnh nhất của ứng viên phù hợp với nhóm vai trò trong JD.',
            'your profile only partially matches the must-have requirements.' => 'hồ sơ của bạn chỉ khớp một phần với các yêu cầu bắt buộc.',
            'explicit must-have requirement coverage is still too limited for a stronger score.' => 'độ phủ yêu cầu bắt buộc rõ ràng vẫn còn hạn chế nên chưa đủ điểm cao hơn.',
            'The candidate appears to meet the experience requirement, but hard-skill evidence is incomplete; review missing and weakly evidenced must-have skills before shortlisting.' => 'Ứng viên có vẻ đáp ứng yêu cầu kinh nghiệm, nhưng bằng chứng kỹ năng cứng chưa đầy đủ; cần rà soát các kỹ năng bắt buộc còn thiếu hoặc bằng chứng yếu trước khi shortlist.',
            'Domain alignment may need recruiter review before relying on the semantic fit score.' => 'Mức phù hợp domain có thể cần recruiter xem lại trước khi tin hoàn toàn vào điểm phù hợp ngữ nghĩa.',
            'This sparse JD relies on promoted technical signals, and the promoted core requirements are strongly confirmed in the CV.' => 'JD thiếu chi tiết nên AI dựa vào tín hiệu kỹ thuật được promote; các yêu cầu cốt lõi được promote đã có bằng chứng mạnh trong CV.',
        ];
    }
}

if (!function_exists('ai_i18n_fragment_rules')) {
    /**
     * @return list<array{0: string, 1: string}>
     */
    function ai_i18n_fragment_rules(): array
    {
        return [
            ['/(?i)If you have real experience with (.+?), add it explicitly in your Skills section and mention one concrete usage example in work or projects\.?/', 'Nếu bạn có kinh nghiệm thực tế với $1, hãy ghi rõ trong mục Kỹ năng và nêu một ví dụ cụ thể trong kinh nghiệm hoặc dự án.'],
            ['/(?i)If you have real exposure to (.+?), add it explicitly in your Skills section and mention one concrete usage example in work or projects\.?/', 'Nếu bạn có kinh nghiệm thực tế với $1, hãy ghi rõ trong mục Kỹ năng và nêu một ví dụ cụ thể trong kinh nghiệm hoặc dự án.'],
            ['/(?i)To improve your fit,\s*/', 'Để cải thiện mức phù hợp, '],
            ['/(?i)your profile only partially matches the must-have requirements\.?/', 'hồ sơ của bạn chỉ khớp một phần với các yêu cầu bắt buộc.'],
            ['/(?i)Source-aware calibration adjusted the score from (\d+)\/100 to (\d+)\/100 because /', 'Hiệu chỉnh theo nguồn bằng chứng đã điều chỉnh điểm từ $1/100 lên $2/100 vì '],
            ['/(?i)Clear evidence for (.+?) via (.+?), and (.+?)\./', 'Có bằng chứng rõ cho $1 thông qua $2 và $3.'],
            ['/(?i)Clear evidence for (.+?) via (.+?)\./', 'Có bằng chứng rõ cho $1 thông qua $2.'],
            ['/(?i)Have experience in /', 'Có kinh nghiệm với '],
            ['/(?i)add it explicitly in your Skills section/', 'ghi rõ trong mục Kỹ năng'],
            ['/(?i)mention one concrete usage example in work or projects/', 'nêu một ví dụ cụ thể trong kinh nghiệm hoặc dự án'],
            ['/(?i)before shortlisting/', 'trước khi shortlist'],
            ['/(?i)before relying on the semantic fit score/', 'trước khi tin hoàn toàn vào điểm phù hợp ngữ nghĩa'],
            ['/(?i)hard-skill evidence is incomplete/', 'bằng chứng kỹ năng cứng chưa đầy đủ'],
            ['/(?i)weakly evidenced must-have skills/', 'các kỹ năng bắt buộc có bằng chứng yếu'],
            ['/(?i)review missing and/', 'rà soát các mục còn thiếu và'],
            ['/(?i)The candidate appears to meet the experience requirement, but/', 'Ứng viên có vẻ đáp ứng yêu cầu kinh nghiệm, nhưng'],
            ['/(?i)Domain alignment may need recruiter review/', 'Mức phù hợp domain có thể cần recruiter xem lại'],
        ];
    }
}

if (!function_exists('ai_i18n_apply_fragment_rules')) {
    function ai_i18n_apply_fragment_rules(string $text, string $lang, int $depth = 0): string
    {
        if (ai_ui_is_english($lang) || $text === '' || $depth > 2) {
            return $text;
        }

        $out = $text;
        $changed = false;
        foreach (ai_i18n_fragment_rules() as [$pattern, $replacement]) {
            $next = preg_replace($pattern, $replacement, $out);
            if (is_string($next) && $next !== $out) {
                $out = $next;
                $changed = true;
            }
        }

        return $changed ? ai_i18n_apply_fragment_rules($out, $lang, $depth + 1) : $out;
    }
}

if (!function_exists('ai_ui_translate_label')) {
    function ai_ui_translate_label(string $key, string $lang): string
    {
        $labels = ai_i18n_labels();
        $lang = ai_ui_is_english($lang) ? 'en' : 'vi';

        return $labels[$key][$lang] ?? $key;
    }
}

if (!function_exists('ai_ui_translate_enum')) {
    function ai_ui_translate_enum(string $group, string $value, string $lang): string
    {
        if (ai_ui_is_english($lang) || trim($value) === '') {
            return $value;
        }

        $maps = ai_i18n_enum_maps();
        $groupMap = $maps[$group] ?? [];
        $lower = strtolower($value);

        if (isset($groupMap[$value])) {
            return $groupMap[$value];
        }
        foreach ($groupMap as $en => $vi) {
            if (strtolower($en) === $lower) {
                return $vi;
            }
        }

        return $value;
    }
}

if (!function_exists('ai_ui_translate_text')) {
    /**
     * @param array<string, mixed> $context
     */
    function ai_ui_translate_text(string $text, string $lang, array $context = []): string
    {
        $text = trim($text);
        if ($text === '' || ai_ui_is_english($lang)) {
            return $text;
        }

        $phrases = ai_i18n_phrase_map();
        if (isset($phrases[$text])) {
            return $phrases[$text];
        }

        $lower = strtolower($text);
        foreach ($phrases as $en => $vi) {
            if (strtolower($en) === $lower) {
                return $vi;
            }
        }

        if (preg_match('/^Missing must-have skills:\s*(.+)$/i', $text, $m)) {
            return 'Thiếu kỹ năng bắt buộc: ' . $m[1];
        }
        if (preg_match('/^Optional nice-to-have gaps:\s*(.+)$/i', $text, $m)) {
            return 'Các kỹ năng cộng thêm còn thiếu: ' . $m[1];
        }
        if (preg_match('/^This role is a Strong Fit because (.+)$/i', $text, $m)) {
            return 'Vị trí này rất phù hợp vì ' . ai_ui_translate_text($m[1], $lang, $context);
        }
        if (preg_match('/^This role is currently a Stretch because (.+)$/i', $text, $m)) {
            return 'Vị trí này hiện chỉ phù hợp một phần vì ' . ai_ui_translate_text($m[1], $lang, $context);
        }
        if (preg_match('/^This role is currently a Low Fit because (.+)$/i', $text, $m)) {
            return 'Vị trí này hiện chưa phù hợp cao vì ' . ai_ui_translate_text($m[1], $lang, $context);
        }
        if (preg_match('/^This role is currently a Potential Fit because (.+)$/i', $text, $m)) {
            return 'Vị trí này hiện có tiềm năng phù hợp vì ' . ai_ui_translate_text($m[1], $lang, $context);
        }
        if (preg_match('/^This role is a Good Fit because (.+)$/i', $text, $m)) {
            return 'Vị trí này phù hợp tốt vì ' . ai_ui_translate_text($m[1], $lang, $context);
        }
        if (preg_match('/^Can you explain how you used (.+?) in this work example:\s*(.+)$/i', $text, $m)) {
            return 'Bạn có thể giải thích cách bạn sử dụng ' . $m[1] . ' trong ví dụ công việc này: ' . $m[2];
        }
        if (preg_match('/^(.+?)\s*\(level\s*(\d+),\s*([^)]+)\)\s*:\s*(.+)$/i', $text, $m)) {
            $source = ai_ui_translate_enum('evidence_source', trim($m[3]), $lang);

            return trim($m[1]) . ' (mức ' . $m[2] . ', ' . $source . '): ' . $m[4];
        }
        if (preg_match('/^Do you have production experience with (.+)\?$/i', $text, $m)) {
            return 'Bạn có kinh nghiệm production với ' . $m[1] . ' không?';
        }
        if (preg_match('/^Clear evidence for (.+)\.$/i', $text, $m)) {
            return 'Có bằng chứng rõ cho ' . $m[1] . '.';
        }
        if (preg_match('/^Education requirements should be reviewed separately:\s*(.+)$/i', $text, $m)) {
            return 'Yêu cầu học vấn cần xem xét riêng: ' . $m[1];
        }
        if (preg_match('/^Soft skills should be verified during interview:\s*(.+)$/i', $text, $m)) {
            return 'Kỹ năng mềm cần xác minh khi phỏng vấn: ' . $m[1];
        }
        if (preg_match('/^Language requirements should be reviewed separately:\s*(.+)$/i', $text, $m)) {
            return 'Yêu cầu ngoại ngữ cần xem xét riêng: ' . $m[1];
        }
        if (preg_match('/^Source-aware calibration adjusted the score:\s*(.+)$/i', $text, $m)) {
            return 'Hiệu chỉnh theo nguồn bằng chứng đã điều chỉnh điểm: ' . ai_ui_translate_text($m[1], $lang, $context);
        }
        if (preg_match('/^Source-aware calibration adjusted the score from (\d+)\/100 to (\d+)\/100 because (.+)$/i', $text, $m)) {
            return 'Hiệu chỉnh theo nguồn bằng chứng đã điều chỉnh điểm từ ' . $m[1] . '/100 lên ' . $m[2] . '/100 vì ' . ai_ui_translate_text($m[3], $lang, $context);
        }
        if (preg_match('/^(.+?) is a (.+?) candidate for (.+?) with a final score of (\d+)\/100\.\s*(.*)$/is', $text, $m)) {
            $tail = trim($m[5]);
            $out = $m[1] . ' là ứng viên ' . ai_ui_translate_enum('recommendation', $m[2], $lang)
                . ' cho vị trí ' . $m[3] . ' với điểm cuối ' . $m[4] . '/100.';
            if ($tail !== '') {
                $out .= ' ' . ai_ui_translate_text($tail, $lang, $context);
            }

            return $out;
        }
        if (preg_match('/^(.+?) is a (.+?) candidate for (.+?) with a final score of (\d+)\/100$/i', $text, $m)) {
            return $m[1] . ' là ứng viên ' . ai_ui_translate_enum('recommendation', $m[2], $lang)
                . ' cho vị trí ' . $m[3] . ' với điểm cuối ' . $m[4] . '/100.';
        }

        return ai_i18n_apply_fragment_rules($text, $lang);
    }
}

if (!function_exists('ai_ui_translate_list')) {
    /**
     * @param list<mixed> $items
     * @param array<string, mixed> $context
     * @return list<string>
     */
    function ai_ui_translate_list(array $items, string $lang, array $context = []): array
    {
        $out = [];
        foreach ($items as $item) {
            if (is_string($item) || is_numeric($item)) {
                $out[] = ai_ui_translate_text((string) $item, $lang, $context);
            } elseif (is_array($item)) {
                $label = (string) ($item['skill'] ?? $item['requirement'] ?? $item['name'] ?? $item['label'] ?? '');
                $detail = (string) ($item['reason'] ?? $item['detail'] ?? $item['note'] ?? '');
                if ($detail !== '') {
                    $out[] = $label . ' — ' . ai_ui_translate_text($detail, $lang, $context);
                } elseif ($label !== '') {
                    $out[] = $label;
                }
            }
        }

        return $out;
    }
}

if (!function_exists('ai_ui_translate_review_card')) {
    /**
     * @param array<string, mixed> $card
     * @return array<string, mixed>
     */
    function ai_ui_translate_review_card(array $card, string $lang): array
    {
        if (ai_ui_is_english($lang)) {
            return $card;
        }

        $out = $card;
        $out['summary'] = ai_ui_translate_text((string) ($card['summary'] ?? ''), $lang);

        foreach (['strengths', 'concerns', 'requirement_notes', 'suggested_interview_questions'] as $key) {
            $items = is_array($card[$key] ?? null) ? $card[$key] : [];
            $out[$key] = ai_ui_translate_list($items, $lang, ['field' => $key]);
        }

        $evidence = is_array($card['evidence_highlights'] ?? null) ? $card['evidence_highlights'] : [];
        $out['evidence_highlights'] = ai_ui_translate_list($evidence, $lang, ['field' => 'evidence']);

        $impact = is_array($card['role_alignment_impact'] ?? null) ? $card['role_alignment_impact'] : null;
        if (is_array($impact) && isset($impact['reason']) && is_string($impact['reason'])) {
            $impact['reason'] = ai_ui_translate_text($impact['reason'], $lang);
            $out['role_alignment_impact'] = $impact;
        }

        return $out;
    }
}

if (!function_exists('ai_ui_translate_candidate_job_detail')) {
    /**
     * @param array<string, mixed> $job
     * @return array<string, mixed>
     */
    function ai_ui_translate_candidate_job_detail(array $job, string $lang): array
    {
        if (ai_ui_is_english($lang)) {
            return $job;
        }

        $out = $job;
        $out['fit_label'] = ai_ui_translate_enum('fit_label', (string) ($job['fit_label'] ?? ''), $lang);
        $out['fit_summary'] = ai_ui_translate_text((string) ($job['fit_summary'] ?? ''), $lang);

        foreach (['why_fit', 'next_best_actions', 'cv_improvement_suggestions', 'matched_must_have_skills'] as $key) {
            $items = is_array($job[$key] ?? null) ? $job[$key] : [];
            $out[$key] = ai_ui_translate_list($items, $lang);
        }

        $gaps = is_array($job['skill_gaps'] ?? null) ? $job['skill_gaps'] : [];
        $translatedGaps = [];
        foreach ($gaps as $gapKey => $gapItems) {
            $translatedGaps[$gapKey] = is_array($gapItems)
                ? ai_ui_translate_list($gapItems, $lang, ['gap' => $gapKey])
                : $gapItems;
        }
        $out['skill_gaps'] = $translatedGaps;

        $reviewCard = is_array($job['review_card'] ?? null) ? $job['review_card'] : [];
        if ($reviewCard !== []) {
            $out['review_card'] = ai_ui_translate_review_card($reviewCard, $lang);
        }

        $jq = is_array($job['job_quality'] ?? null) ? $job['job_quality'] : [];
        if ($jq !== []) {
            if (isset($jq['quality_label'])) {
                $jq['quality_label'] = ai_ui_translate_text((string) $jq['quality_label'], $lang);
            }
            if (is_array($jq['reasons'] ?? null)) {
                $jq['reasons'] = ai_ui_translate_list($jq['reasons'], $lang);
            }
            $out['job_quality'] = $jq;
        }

        $impact = is_array($job['role_alignment_impact'] ?? null) ? $job['role_alignment_impact'] : null;
        if (is_array($impact) && isset($impact['reason'])) {
            $impact['reason'] = ai_ui_translate_text((string) $impact['reason'], $lang);
            $out['role_alignment_impact'] = $impact;
        }

        return $out;
    }
}

if (!function_exists('ai_i18n_client_config')) {
    /**
     * @return array<string, mixed>
     */
    function ai_i18n_client_config(): array
    {
        return [
            'storageKey' => 'topcv_ai_ui_lang',
            'defaultLang' => 'en',
            'supportedLangs' => ai_ui_supported_langs(),
            'labels' => ai_i18n_labels(),
            'phrases' => ai_i18n_phrase_map(),
            'enums' => ai_i18n_enum_maps(),
        ];
    }
}

if (!function_exists('ai_i18n_script_tags')) {
    function ai_i18n_script_tags(): string
    {
        $base = defined('BASE_URL') ? BASE_URL : '/topcv_lite/';
        $config = ai_i18n_client_config();
        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{}';
        }

        return '<script>window.__TOPCV_AI_I18N__=' . $json . ';</script>'
            . '<script src="' . htmlspecialchars($base, ENT_QUOTES) . 'assets/js/ai-ui-i18n.js?v=4"></script>';
    }
}
