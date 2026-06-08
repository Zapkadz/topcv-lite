<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_ai_taxonomy.php';
require_once __DIR__ . '/../../includes/services/AiTaxonomyService.php';

if (!ai_taxonomy_schema_ready($conn)) {
    echo "Schema not ready\n";
    exit(1);
}

$result = AiTaxonomyService::exportMergedTaxonomy($conn, 1);
echo ($result['ok'] ? 'OK' : 'FAIL') . ': ' . $result['message'] . "\n";
echo 'path=' . ($result['path'] ?? '') . "\n";
echo 'exists=' . (is_file((string) ($result['path'] ?? '')) ? 'yes' : 'no') . "\n";
