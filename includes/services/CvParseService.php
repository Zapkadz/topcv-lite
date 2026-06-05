<?php

require_once __DIR__ . '/PdfTextExtractor.php';
require_once __DIR__ . '/AiCvParserService.php';
require_once __DIR__ . '/../cv_import_rules.php';
require_once __DIR__ . '/../cv_parse_fallback.php';

/**
 * Orchestrator: PDF/text → AI (+ fallback) → normalized draft cho cv-builder.
 */
class CvParseService
{
    /**
     * @return array{
     *   ok: bool,
     *   message: string,
     *   profile: array<string, mixed>,
     *   children: array<string, list>,
     *   meta: array{parse_source: string, warnings: list<string>}
     * }
     */
    public static function importFromPdfPath(string $absolutePath): array
    {
        $extract = PdfTextExtractor::extract($absolutePath);
        if (!$extract['ok']) {
            return self::fail((string) ($extract['message'] ?? 'Không đọc được PDF.'));
        }

        return self::importFromText((string) ($extract['text'] ?? ''));
    }

    /**
     * @return array{
     *   ok: bool,
     *   message: string,
     *   profile: array<string, mixed>,
     *   children: array<string, list>,
     *   meta: array{parse_source: string, warnings: list<string>}
     * }
     */
    public static function importFromText(string $text): array
    {
        $text = cv_import_truncate_text($text);
        if (mb_strlen($text) < cv_import_min_text_len()) {
            return self::fail('Nội dung CV quá ngắn hoặc PDF không có text layer.');
        }

        $warnings = [];
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
     * @return array{
     *   ok: bool,
     *   message: string,
     *   profile: array<string, mixed>,
     *   children: array<string, list>,
     *   meta: array{parse_source: string, warnings: list<string>}
     * }
     */
    private static function fail(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'profile' => self::emptyProfile(),
            'children' => self::emptyChildren(),
            'meta' => ['parse_source' => 'none', 'warnings' => []],
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
