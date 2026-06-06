<?php

require_once __DIR__ . '/PdfTextExtractor.php';
require_once __DIR__ . '/AiCvParserService.php';
require_once __DIR__ . '/../ai_config.php';
require_once __DIR__ . '/../cv_import_rules.php';
require_once __DIR__ . '/../cv_import_text_clean.php';
require_once __DIR__ . '/../cv_import_pdf_quality.php';
require_once __DIR__ . '/../cv_parse_fallback.php';

/**
 * Orchestrator: PDF/text → router → AI text / GPT vision → normalized draft.
 */
class CvParseService
{
    /**
     * @param array<string, mixed> $options parse_mode: auto|text|vision
     * @return array{
     *   ok: bool,
     *   message: string,
     *   profile: array<string, mixed>,
     *   children: array<string, list>,
     *   meta: array<string, mixed>
     * }
     */
    public static function importFromPdfPath(string $absolutePath, array $options = []): array
    {
        $extract = PdfTextExtractor::extract($absolutePath);
        if (!$extract['ok']) {
            return self::fail((string) ($extract['message'] ?? 'Không đọc được PDF.'));
        }

        $rawText = (string) ($extract['text'] ?? '');
        $cleanResult = cv_import_clean_extracted_text($rawText);
        $quality = cv_import_analyze_pdf_quality($rawText, $cleanResult);

        $requestedMode = cv_import_normalize_parse_mode_request($options['parse_mode'] ?? 'auto');
        $route = cv_import_resolve_parse_mode($requestedMode, $quality, ai_openai_ready());

        if ($route['mode'] === 'vision_unavailable') {
            return self::fail(
                'Chưa cấu hình GPT vision (block openai trong config/ai.local.php). '
                . 'Chọn phân tích text nhanh hoặc cấu hình API trước.'
            );
        }

        if (in_array($route['mode'], ['vision_gpt', 'vision_gpt_forced'], true)) {
            return self::importFromPdfVision($absolutePath, $cleanResult, $quality, $route);
        }

        return self::importFromText(
            (string) ($cleanResult['text'] ?? ''),
            $cleanResult,
            $quality,
            $route
        );
    }

    /**
     * GPT vision path — parser thực tế ở khối F2.
     *
     * @param array<string, mixed> $cleanResult
     * @param array<string, mixed> $quality
     * @param array{mode: string, reason: string, requested: string} $route
     * @return array<string, mixed>
     */
    private static function importFromPdfVision(
        string $absolutePath,
        array $cleanResult,
        array $quality,
        array $route
    ): array {
        if (!ai_openai_ready()) {
            return self::fail('GPT vision chưa sẵn sàng — kiểm tra cấu hình openai.');
        }

        $cleanLen = (int) ($quality['clean_len'] ?? 0);
        if ($cleanLen >= cv_import_min_text_len()) {
            $textFallback = self::importFromText(
                (string) ($cleanResult['text'] ?? ''),
                $cleanResult,
                $quality,
                [
                    'mode' => 'text_fast',
                    'reason' => 'vision_pending_f2_not_used',
                    'requested' => $route['requested'] ?? 'auto',
                ]
            );

            if ($textFallback['ok']) {
                $textFallback['meta']['parse_mode'] = $route['mode'];
                $textFallback['meta']['parse_mode_reason'] = $route['reason'];
                $textFallback['meta']['vision_deferred'] = true;
                $textFallback['meta']['warnings'][] =
                    'Router chọn GPT vision nhưng parser vision (F2) chưa triển khai — tạm dùng text Groq.';
            }

            return $textFallback;
        }

        return self::fail(
            'PDF có vẻ là file scan (không có text layer). '
            . 'Cần GPT vision scan (khối F2 — sắp triển khai).'
        );
    }

    /**
     * @param array<string, mixed>|null $cleanResult
     * @param array<string, mixed>|null $quality
     * @param array{mode?: string, reason?: string, requested?: string}|null $route
     * @return array<string, mixed>
     */
    public static function importFromText(
        string $text,
        ?array $cleanResult = null,
        ?array $quality = null,
        ?array $route = null
    ): array {
        if ($cleanResult === null) {
            $cleanResult = cv_import_clean_extracted_text($text);
        }

        $text = cv_import_truncate_text((string) ($cleanResult['text'] ?? $text));

        if ($quality === null) {
            $quality = cv_import_analyze_pdf_quality($text, $cleanResult);
        }

        if ($route === null) {
            $route = [
                'mode' => 'text_fast',
                'reason' => 'direct_text_import',
                'requested' => 'text',
            ];
        }

        if (mb_strlen($text) < cv_import_min_text_len()) {
            return self::fail('Nội dung CV quá ngắn hoặc PDF không có text layer.');
        }

        $warnings = [];
        $noiseScore = (float) ($cleanResult['noise_score'] ?? 0.0);
        if ($noiseScore >= 0.25) {
            $warnings[] = 'PDF thiết kế có thể còn text lộn xộn — vui lòng kiểm tra kỹ trước khi lưu.';
        }

        $parseSource = 'fallback';
        $rawDraft = null;

        $ai = AiCvParserService::parseTextToDraft($text);
        if ($ai['ok'] && !empty($ai['draft']) && is_array($ai['draft'])) {
            $rawDraft = $ai['draft'];
            $parseSource = 'ai';
        } else {
            $warnings[] = 'AI parse thất bại: ' . trim((string) ($ai['message'] ?? 'unknown'));
        }

        $fallback = cv_parse_fallback_from_text($text);

        if ($rawDraft === null) {
            $rawDraft = $fallback;
            $parseSource = 'fallback';
        } else {
            $merged = self::mergeDrafts($rawDraft, $fallback);
            if ($merged['filled']) {
                $parseSource = 'ai+fallback';
                $warnings[] = 'Đã bổ sung một số field trống từ fallback regex.';
            }
            $rawDraft = $merged['draft'];
        }

        $normalized = cv_normalize_import_draft($rawDraft);
        $normalized['meta'] = [
            'parse_source' => $parseSource,
            'warnings' => $warnings,
            'text_noise_score' => $noiseScore,
            'text_clean_steps' => $cleanResult['steps'] ?? [],
            'parse_mode' => (string) ($route['mode'] ?? 'text_fast'),
            'parse_mode_reason' => (string) ($route['reason'] ?? ''),
            'parse_mode_requested' => (string) ($route['requested'] ?? 'auto'),
            'text_quality' => (string) ($quality['text_quality'] ?? ''),
            'text_clean_len' => (int) ($quality['clean_len'] ?? 0),
            'text_ratio_alnum' => (float) ($quality['ratio_alnum'] ?? 0.0),
        ];

        return [
            'ok' => true,
            'message' => '',
            'profile' => $normalized['profile'],
            'children' => $normalized['children'],
            'meta' => $normalized['meta'],
        ];
    }

    /**
     * @param array<string, mixed> $primary
     * @param array<string, mixed> $fallback
     * @return array{draft: array<string, mixed>, filled: bool}
     */
    private static function mergeDrafts(array $primary, array $fallback): array
    {
        $filled = false;
        $profileKeys = [
            'title', 'full_name', 'target_position', 'date_of_birth', 'gender',
            'phone', 'email', 'website', 'address', 'career_objective', 'interests',
        ];

        foreach ($profileKeys as $key) {
            $primaryVal = trim((string) ($primary[$key] ?? ''));
            $fallbackVal = trim((string) ($fallback[$key] ?? ''));
            if ($primaryVal === '' && $fallbackVal !== '') {
                $primary[$key] = $fallbackVal;
                $filled = true;
            }
        }

        $childKeys = [
            'educations', 'experiences', 'skills', 'projects', 'activities',
            'certificates', 'awards', 'references',
        ];
        foreach ($childKeys as $key) {
            $primaryRows = $primary[$key] ?? [];
            $fallbackRows = $fallback[$key] ?? [];
            if (!is_array($primaryRows) || $primaryRows === []) {
                if (is_array($fallbackRows) && $fallbackRows !== []) {
                    $primary[$key] = $fallbackRows;
                    $filled = true;
                }
            }
        }

        return ['draft' => $primary, 'filled' => $filled];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fail(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'profile' => self::emptyProfile(),
            'children' => self::emptyChildren(),
            'meta' => [
                'parse_source' => 'none',
                'warnings' => [],
                'parse_mode' => 'none',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyProfile(): array
    {
        return [
            'title' => '',
            'full_name' => '',
            'target_position' => '',
            'date_of_birth' => '',
            'gender' => '',
            'phone' => '',
            'email' => '',
            'website' => '',
            'address' => '',
            'career_objective' => '',
            'interests' => '',
            'template_key' => 'classic',
        ];
    }

    /**
     * @return array<string, list>
     */
    private static function emptyChildren(): array
    {
        return [
            'educations' => [],
            'experiences' => [],
            'skills' => [],
            'projects' => [],
            'activities' => [],
            'certificates' => [],
            'awards' => [],
            'references' => [],
        ];
    }
}
