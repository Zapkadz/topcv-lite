<?php

require_once __DIR__ . '/ai_config.php';
require_once __DIR__ . '/cv_import_rules.php';

/**
 * Phân tích chất lượng text PDF và chọn parse mode (CV-F router).
 */

if (!function_exists('cv_import_parse_mode_choices')) {
    /**
     * Giá trị user/UI gửi lên.
     *
     * @return list<string>
     */
    function cv_import_parse_mode_choices(): array
    {
        return ['auto', 'text', 'vision'];
    }
}

if (!function_exists('cv_import_normalize_parse_mode_request')) {
    function cv_import_normalize_parse_mode_request(mixed $mode): string
    {
        $mode = strtolower(trim((string) $mode));
        if ($mode === '') {
            return 'auto';
        }

        return in_array($mode, cv_import_parse_mode_choices(), true) ? $mode : 'auto';
    }
}

if (!function_exists('cv_import_text_ratio_alnum')) {
    function cv_import_text_ratio_alnum(string $text): float
    {
        $len = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
        if ($len <= 0) {
            return 0.0;
        }

        if (preg_match_all('/[\p{L}\p{N}]/u', $text, $matches)) {
            $alnum = count($matches[0]);
        } else {
            $alnum = 0;
        }

        return round($alnum / $len, 3);
    }
}

if (!function_exists('cv_import_analyze_pdf_quality')) {
    /**
     * @param array{raw_len?: int, clean_len?: int, noise_score?: float, steps?: list<string>} $cleanResult
     * @return array{
     *   raw_len: int,
     *   clean_len: int,
     *   noise_score: float,
     *   ratio_alnum: float,
     *   likely_scan: bool,
     *   likely_noisy_layout: bool,
     *   text_quality: string
     * }
     */
    function cv_import_analyze_pdf_quality(string $rawText, array $cleanResult): array
    {
        $rawLen = (int) ($cleanResult['raw_len'] ?? 0);
        if ($rawLen <= 0) {
            $rawLen = function_exists('mb_strlen') ? mb_strlen($rawText) : strlen($rawText);
        }

        $cleanLen = (int) ($cleanResult['clean_len'] ?? 0);
        $cleanText = (string) ($cleanResult['text'] ?? '');
        if ($cleanLen <= 0 && $cleanText !== '') {
            $cleanLen = function_exists('mb_strlen') ? mb_strlen($cleanText) : strlen($cleanText);
        }

        $noiseScore = (float) ($cleanResult['noise_score'] ?? 0.0);
        $ratioAlnum = cv_import_text_ratio_alnum($cleanText);
        $minLen = cv_import_min_text_len();
        $steps = is_array($cleanResult['steps'] ?? null) ? $cleanResult['steps'] : [];
        $heavyDedup = in_array('dedup_repeats', $steps, true)
            || in_array('dedup_patterns', $steps, true);

        $likelyScan = $cleanLen < $minLen;
        $likelyNoisyLayout = $noiseScore >= 0.35
            || ($noiseScore >= 0.25 && $ratioAlnum < 0.55)
            || ($heavyDedup && $noiseScore >= 0.15);

        $textQuality = 'medium';
        if ($likelyScan) {
            $textQuality = 'scan';
        } elseif ($cleanLen > 500 && $noiseScore < 0.15 && $ratioAlnum >= 0.5) {
            $textQuality = 'good';
        } elseif ($likelyNoisyLayout) {
            $textQuality = 'noisy';
        } elseif ($cleanLen < $minLen) {
            $textQuality = 'poor';
        }

        return [
            'raw_len' => $rawLen,
            'clean_len' => $cleanLen,
            'noise_score' => $noiseScore,
            'ratio_alnum' => $ratioAlnum,
            'likely_scan' => $likelyScan,
            'likely_noisy_layout' => $likelyNoisyLayout,
            'text_quality' => $textQuality,
        ];
    }
}

if (!function_exists('cv_import_resolve_parse_mode')) {
    /**
     * @param array<string, mixed> $quality
     * @return array{mode: string, reason: string, requested: string}
     */
    function cv_import_resolve_parse_mode(string $requestedMode, array $quality, bool $openaiReady = false): array
    {
        $requested = cv_import_normalize_parse_mode_request($requestedMode);
        $cleanLen = (int) ($quality['clean_len'] ?? 0);
        $noiseScore = (float) ($quality['noise_score'] ?? 0.0);
        $ratioAlnum = (float) ($quality['ratio_alnum'] ?? 0.0);
        $minLen = cv_import_min_text_len();

        if ($requested === 'text') {
            return [
                'mode' => 'text_fast',
                'reason' => 'user_chose_text',
                'requested' => $requested,
            ];
        }

        if ($requested === 'vision') {
            if (!$openaiReady) {
                return [
                    'mode' => 'vision_unavailable',
                    'reason' => 'user_chose_vision_no_openai',
                    'requested' => $requested,
                ];
            }

            return [
                'mode' => 'vision_gpt_forced',
                'reason' => 'user_chose_vision',
                'requested' => $requested,
            ];
        }

        // auto
        if ($cleanLen < $minLen) {
            if ($openaiReady) {
                return [
                    'mode' => 'vision_gpt',
                    'reason' => 'auto_scan_short_text',
                    'requested' => $requested,
                ];
            }

            return [
                'mode' => 'text_fast',
                'reason' => 'auto_scan_no_openai_fallback_text',
                'requested' => $requested,
            ];
        }

        if (!empty($quality['likely_noisy_layout']) || $noiseScore >= 0.35 || $ratioAlnum < 0.5) {
            if ($openaiReady) {
                return [
                    'mode' => 'vision_gpt',
                    'reason' => 'auto_noisy_or_low_alnum',
                    'requested' => $requested,
                ];
            }

            return [
                'mode' => 'text_fast',
                'reason' => 'auto_noisy_no_openai_fallback_text',
                'requested' => $requested,
            ];
        }

        if ($cleanLen > 500 && $noiseScore < 0.15) {
            return [
                'mode' => 'text_fast',
                'reason' => 'auto_clean_long_text',
                'requested' => $requested,
            ];
        }

        return [
            'mode' => 'text_fast',
            'reason' => 'auto_default_text',
            'requested' => $requested,
        ];
    }
}

if (!function_exists('cv_import_parse_mode_label')) {
    function cv_import_parse_mode_label(string $mode): string
    {
        return match ($mode) {
            'text_fast' => 'Text-base (Groq)',
            'vision_gpt' => 'Chuẩn GPT (vision)',
            'vision_gpt_forced' => 'Chuẩn GPT (vision)',
            'vision_unavailable' => 'Chuẩn GPT (chưa cấu hình)',
            default => $mode,
        };
    }
}

if (!function_exists('cv_import_build_pending_from_path')) {
    /**
     * Phân tích chất lượng PDF sau upload — chưa gọi AI parse.
     *
     * @return array<string, mixed>
     */
    function cv_import_build_pending_from_path(
        int $userId,
        string $absolutePath,
        string $relativePath,
        string $originalName = ''
    ): array {
        require_once __DIR__ . '/services/PdfTextExtractor.php';
        require_once __DIR__ . '/cv_import_text_clean.php';

        $extract = PdfTextExtractor::extractLenient($absolutePath);
        $rawText = (string) ($extract['text'] ?? '');
        $cleanResult = cv_import_clean_extracted_text($rawText);
        $quality = cv_import_analyze_pdf_quality($rawText, $cleanResult);
        $route = cv_import_resolve_parse_mode('auto', $quality, ai_openai_ready());

        return [
            'user_id' => $userId,
            'absolute_path' => $absolutePath,
            'attachment_path' => $relativePath,
            'original_name' => $originalName !== '' ? $originalName : basename($relativePath),
            'quality' => $quality,
            'route_auto' => (string) ($route['mode'] ?? 'text_fast'),
            'route_reason' => (string) ($route['reason'] ?? ''),
            'uploaded_at' => time(),
        ];
    }
}

if (!function_exists('cv_import_get_valid_pending')) {
    /**
     * @return array<string, mixed>|null
     */
    function cv_import_get_valid_pending(int $userId): ?array
    {
        $pending = $_SESSION['cv_import_pending'] ?? null;
        if (!is_array($pending)) {
            return null;
        }

        if ((int) ($pending['user_id'] ?? 0) !== $userId) {
            return null;
        }

        $absolutePath = trim((string) ($pending['absolute_path'] ?? ''));
        if ($absolutePath === '' || !is_file($absolutePath)) {
            return null;
        }

        $uploadedAt = (int) ($pending['uploaded_at'] ?? 0);
        if ($uploadedAt > 0 && (time() - $uploadedAt) > 3600) {
            return null;
        }

        return $pending;
    }
}

if (!function_exists('cv_import_quality_is_noisy')) {
    /**
     * @param array<string, mixed> $quality
     */
    function cv_import_quality_is_noisy(array $quality): bool
    {
        return !empty($quality['likely_noisy_layout']) || !empty($quality['likely_scan']);
    }
}
