<?php

require_once __DIR__ . '/job_rules.php';

/**
 * Quy tắc hub Sàng lọc ứng viên (EMP-A).
 */

if (!function_exists('employer_screening_sql_base')) {
    /** Điều kiện chung: tin approved, chưa xóa mềm. */
    function employer_screening_sql_base(string $jobAlias = 'j'): string
    {
        $j = $jobAlias !== '' ? $jobAlias . '.' : '';

        return "{$j}status = 'approved' AND " . job_sql_not_deleted($jobAlias);
    }
}

if (!function_exists('employer_screening_sql_active')) {
    /** Section 「Đang tuyển」 — còn nhận hồ sơ. */
    function employer_screening_sql_active(string $jobAlias = 'j'): string
    {
        $j = $jobAlias !== '' ? $jobAlias . '.' : '';
        $today = job_today_date();

        return employer_screening_sql_base($jobAlias)
            . " AND ({$j}deadline IS NULL OR {$j}deadline >= " . quote_sql_date($today) . ')';
    }
}

if (!function_exists('employer_screening_sql_expired_with_apps')) {
    /** Section 「Hết hạn — còn CV」 — hết hạn nộp nhưng còn đơn. */
    function employer_screening_sql_expired_with_apps(string $jobAlias = 'j'): string
    {
        $j = $jobAlias !== '' ? $jobAlias . '.' : '';
        $idCol = $jobAlias !== '' ? $jobAlias . '.id' : 'id';
        $today = job_today_date();

        return employer_screening_sql_base($jobAlias)
            . " AND {$j}deadline IS NOT NULL AND {$j}deadline < " . quote_sql_date($today)
            . " AND EXISTS (SELECT 1 FROM applications app_e WHERE app_e.job_id = {$idCol})";
    }
}

if (!function_exists('employer_screening_sql_pending_eligible')) {
    /** Job có thể xuất hiện trên hub (active hoặc expired có đơn). */
    function employer_screening_sql_pending_eligible(string $jobAlias = 'j'): string
    {
        return '(' . employer_screening_sql_active($jobAlias) . ') OR (' . employer_screening_sql_expired_with_apps($jobAlias) . ')';
    }
}

if (!function_exists('quote_sql_date')) {
    function quote_sql_date(string $dateYmd): string
    {
        return "'" . str_replace("'", "''", $dateYmd) . "'";
    }
}

if (!function_exists('employer_screening_order_sql')) {
    function employer_screening_order_sql(): string
    {
        return 'pending_apps DESC, j.deadline IS NULL, j.deadline ASC, j.title ASC';
    }
}

if (!function_exists('employer_screening_format_deadline')) {
    function employer_screening_format_deadline(?string $deadline): string
    {
        if ($deadline === null || trim($deadline) === '') {
            return 'Không giới hạn';
        }

        $ts = strtotime($deadline);
        if ($ts === false) {
            return (string) $deadline;
        }

        return date('d/m/Y', $ts);
    }
}

if (!function_exists('employer_screening_job_badge_html')) {
    function employer_screening_job_badge_html(array $job, string $section): string
    {
        if ($section === 'expired') {
            return '<span class="badge bg-secondary">Hết hạn nộp</span>';
        }

        return '<span class="badge bg-success">Đang nhận hồ sơ</span>';
    }
}

if (!function_exists('employer_application_status_badge_html')) {
    function employer_application_status_badge_html(string $status): string
    {
        return match ($status) {
            'pending' => '<span class="badge rounded-pill bg-warning text-dark">Chờ duyệt</span>',
            'viewed' => '<span class="badge rounded-pill bg-info">Đã xem</span>',
            'interview' => '<span class="badge rounded-pill bg-success">Hẹn PV</span>',
            'rejected' => '<span class="badge rounded-pill bg-secondary">Từ chối</span>',
            default => '<span class="badge rounded-pill bg-light text-dark border">' . htmlspecialchars($status) . '</span>',
        };
    }
}

if (!function_exists('employer_ai_recommendation_badge_html')) {
    function employer_ai_recommendation_badge_html(?string $recommendation): string
    {
        if ($recommendation === null || trim($recommendation) === '') {
            return '<span class="text-muted small">—</span>';
        }

        $label = trim($recommendation);
        $lower = strtolower($label);
        $class = match (true) {
            str_contains($lower, 'strong') => 'bg-success',
            str_contains($lower, 'review') && !str_contains($lower, 'not') => 'bg-primary',
            str_contains($lower, 'consider') => 'bg-info text-dark',
            str_contains($lower, 'not') || str_contains($lower, 'reject') => 'bg-secondary',
            default => 'bg-light text-dark border',
        };

        return '<span class="badge rounded-pill ' . $class . '">' . htmlspecialchars($label) . '</span>';
    }
}

if (!function_exists('employer_screening_sort_apps_by_ai_rank')) {
    /**
     * @param list<array<string, mixed>> $apps
     * @param array<int, array<string, mixed>> $aiMap
     * @return list<array<string, mixed>>
     */
    function employer_screening_sort_apps_by_ai_rank(array $apps, array $aiMap): array
    {
        if ($aiMap === []) {
            return $apps;
        }

        usort($apps, static function (array $a, array $b) use ($aiMap): int {
            $appA = (int) ($a['app_id'] ?? 0);
            $appB = (int) ($b['app_id'] ?? 0);
            $rankA = isset($aiMap[$appA]['ai_rank']) ? (int) $aiMap[$appA]['ai_rank'] : PHP_INT_MAX;
            $rankB = isset($aiMap[$appB]['ai_rank']) ? (int) $aiMap[$appB]['ai_rank'] : PHP_INT_MAX;

            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            $scoreA = isset($aiMap[$appA]['final_score']) ? (int) $aiMap[$appA]['final_score'] : -1;
            $scoreB = isset($aiMap[$appB]['final_score']) ? (int) $aiMap[$appB]['final_score'] : -1;

            return $scoreB <=> $scoreA;
        });

        return $apps;
    }
}

if (!function_exists('employer_ai_review_card_parse')) {
    /**
     * @return array<string, mixed>|null
     */
    function employer_ai_review_card_parse(?string $json): ?array
    {
        if ($json === null || trim($json) === '') {
            return null;
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }
}

if (!function_exists('employer_ai_review_card_has_content')) {
    /**
     * @param array<string, mixed>|null $card
     */
    function employer_ai_review_card_has_content(?array $card): bool
    {
        if ($card === null) {
            return false;
        }

        if (trim((string) ($card['summary'] ?? '')) !== '') {
            return true;
        }

        foreach (['strengths', 'concerns', 'evidence_highlights', 'suggested_interview_questions'] as $key) {
            $items = $card[$key] ?? null;
            if (is_array($items) && $items !== []) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('employer_ai_review_plain_text')) {
    function employer_ai_review_plain_text(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', strip_tags($text));

        return trim($text);
    }
}

if (!function_exists('employer_ai_review_expand_item')) {
    /**
     * Tách chuỗi AI (có thể nhúng HTML list) thành các dòng plain text.
     *
     * @return list<string>
     */
    function employer_ai_review_expand_item(string $raw): array
    {
        $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (!preg_match('/<li[\s>]/i', $raw)) {
            $plain = employer_ai_review_plain_text($raw);

            return $plain !== '' ? [$plain] : [];
        }

        $prefix = trim(strip_tags((string) preg_replace('/<ul[\s>].*$/is', '', preg_replace('/<li[\s>].*$/is', '', $raw))));
        $liTexts = [];
        if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $raw, $matches)) {
            foreach ($matches[1] as $li) {
                $plain = employer_ai_review_plain_text((string) $li);
                if ($plain !== '') {
                    $liTexts[] = $plain;
                }
            }
        }

        if ($liTexts === []) {
            $plain = employer_ai_review_plain_text($raw);

            return $plain !== '' ? [$plain] : [];
        }

        $isQuestion = $prefix !== '' && (
            str_ends_with(rtrim($prefix), '?')
            || preg_match('/^(how|do|can|what|why|when|where|describe|tell|have you|are you)/i', $prefix) === 1
        );
        if ($isQuestion) {
            $merged = rtrim($prefix, ' :') . ' ' . implode(', ', $liTexts);
            if (!str_ends_with($merged, '?')) {
                $merged .= '?';
            }

            return [$merged];
        }

        $items = [];
        if ($prefix !== '') {
            $items[] = rtrim($prefix, ':') . ':';
        }
        foreach ($liTexts as $text) {
            $items[] = $text;
        }

        return $items;
    }
}

if (!function_exists('employer_ai_review_normalize_list')) {
    /**
     * @param mixed $items
     * @return list<string>
     */
    function employer_ai_review_normalize_list($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $item) {
            if (!is_string($item) && !is_numeric($item)) {
                continue;
            }
            foreach (employer_ai_review_expand_item((string) $item) as $line) {
                $out[] = $line;
            }
        }

        return $out;
    }
}

if (!function_exists('employer_ai_review_card_for_ui')) {
    /**
     * @param array<string, mixed>|null $card
     * @return array<string, mixed>|null
     */
    function employer_ai_review_card_for_ui(?array $card): ?array
    {
        if ($card === null) {
            return null;
        }

        $card['summary'] = employer_ai_review_plain_text((string) ($card['summary'] ?? ''));

        foreach (['strengths', 'concerns', 'evidence_highlights', 'suggested_interview_questions'] as $key) {
            $card[$key] = employer_ai_review_normalize_list($card[$key] ?? []);
        }

        return $card;
    }
}
