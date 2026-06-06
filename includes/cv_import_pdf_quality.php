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
            'text_fast' => 'Phân tích text nhanh (Groq)',
            'vision_gpt' => 'GPT vision (tự động)',
            'vision_gpt_forced' => 'GPT vision (chọn thủ công)',
            'vision_unavailable' => 'GPT vision (chưa cấu hình)',
            default => $mode,
        };
    }
}
