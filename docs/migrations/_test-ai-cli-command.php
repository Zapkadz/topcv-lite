<?php
declare(strict_types=1);

/**
 * In ra CLI command (không chạy) — verify flags BGE-M3 theo cursor-prompt-topcv-lite-cli-bge-m3.md
 */
require_once __DIR__ . '/../../includes/ai_screening_config.php';

$cfg = ai_screening_config();

echo 'config_ready=' . (ai_screening_config_ready() ? 'true' : 'false') . "\n";
echo 'status=' . ai_screening_config_status_message() . "\n";
echo 'taxonomy=' . ($cfg['taxonomy_path'] ?? '') . "\n";
echo 'timeout=' . ($cfg['cli_timeout_seconds'] ?? '') . "\n";
echo 'enable_embedding=' . (!empty($cfg['enable_embedding']) ? 'true' : 'false') . "\n";
echo 'embedding_model=' . ($cfg['embedding_model'] ?? '') . "\n";
echo 'hf_hub_offline=' . (!empty($cfg['hf_hub_offline']) ? 'true' : 'false') . "\n";

$jd = 'C:\\topcv_ai_runtime\\job-10\\run-test\\jd.txt';
$cv = 'C:\\topcv_ai_runtime\\job-10\\run-test\\cvs';
$out = 'C:\\topcv_ai_runtime\\job-10\\run-test\\ranking_results.json';

$cmd = '"' . str_replace('"', '\\"', (string) $cfg['python_path']) . '"'
    . ' "' . str_replace('"', '\\"', (string) $cfg['main_path']) . '"'
    . ' --jd "' . $jd . '"'
    . ' --cv-dir "' . $cv . '"'
    . ' --taxonomy "' . str_replace('"', '\\"', (string) $cfg['taxonomy_path']) . '"'
    . ' --output-json "' . $out . '"';

if (!empty($cfg['enable_embedding'])) {
    $cmd .= ' --enable-embedding'
        . ' --embedding-model "' . str_replace('"', '\\"', (string) ($cfg['embedding_model'] ?? 'BAAI/bge-m3')) . '"';
    if (!empty($cfg['embedding_local_only'])) {
        $cmd .= ' --embedding-local-only';
    }
}

echo "\n--- expected CLI shape ---\n" . $cmd . "\n";
