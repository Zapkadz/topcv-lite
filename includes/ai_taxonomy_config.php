<?php

if (!function_exists('ai_taxonomy_config')) {
    /**
     * @return array<string, string>
     */
    function ai_taxonomy_config(): array
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $defaults = [
            'base_taxonomy_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\data\\taxonomy\\skills.json',
            'suggestion_queue_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\outputs\\taxonomy_suggestions.json',
            'merged_taxonomy_path' => 'C:\\topcv_ai_runtime\\taxonomy\\skills_merged.json',
        ];

        $localPath = __DIR__ . '/../config/ai_taxonomy.local.php';
        if (is_file($localPath)) {
            $loaded = require $localPath;
            if (is_array($loaded)) {
                $defaults = array_merge($defaults, $loaded);
            }
        }

        foreach (['base_taxonomy_path', 'suggestion_queue_path', 'merged_taxonomy_path'] as $key) {
            $defaults[$key] = trim((string) ($defaults[$key] ?? ''));
        }

        $config = $defaults;

        return $config;
    }
}

if (!function_exists('ai_taxonomy_effective_screening_path')) {
    /**
     * Đường dẫn taxonomy cho AI screening: ưu tiên skills_merged.json, fallback base.
     */
    function ai_taxonomy_effective_screening_path(): string
    {
        $cfg = ai_taxonomy_config();
        $merged = trim((string) ($cfg['merged_taxonomy_path'] ?? ''));
        if ($merged !== '' && is_file($merged)) {
            return $merged;
        }

        $base = trim((string) ($cfg['base_taxonomy_path'] ?? ''));
        if ($base !== '' && is_file($base)) {
            return $base;
        }

        if (function_exists('ai_screening_config')) {
            $screen = ai_screening_config();
            $fallback = trim((string) ($screen['taxonomy_path'] ?? ''));
            if ($fallback !== '' && is_file($fallback)) {
                return $fallback;
            }
        }

        return $merged !== '' ? $merged : $base;
    }
}

if (!function_exists('ai_taxonomy_dedupe_aliases')) {
    /**
     * @param list<string> $aliases
     * @return list<string>
     */
    function ai_taxonomy_dedupe_aliases(array $aliases): array
    {
        $seen = [];
        $out = [];

        foreach ($aliases as $alias) {
            $alias = trim((string) $alias);
            if ($alias === '') {
                continue;
            }
            $key = strtolower($alias);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $alias;
        }

        return $out;
    }
}

if (!function_exists('ai_taxonomy_parse_aliases_text')) {
    /**
     * @return list<string>
     */
    function ai_taxonomy_parse_aliases_text(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $aliases = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $aliases[] = $line;
            }
        }

        return ai_taxonomy_dedupe_aliases($aliases);
    }
}
