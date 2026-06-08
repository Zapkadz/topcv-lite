<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_ai_taxonomy.php';
require_once __DIR__ . '/../../includes/ai_taxonomy_config.php';
require_once __DIR__ . '/../../includes/services/AiTaxonomyService.php';

echo "=== Admin Taxonomy — test report ===\n\n";

$schemaOk = ai_taxonomy_schema_ready($conn);
echo 'Schema ready: ' . ($schemaOk ? 'YES' : 'NO') . "\n";
if (!$schemaOk) {
    echo "Run: php docs/migrations/migrate-phase-admin-taxonomy.php\n";
    exit(1);
}

$cfg = ai_taxonomy_config();
echo "base_taxonomy_path: " . ($cfg['base_taxonomy_path'] ?? '') . "\n";
echo "  exists: " . (is_file((string) ($cfg['base_taxonomy_path'] ?? '')) ? 'yes' : 'no') . "\n";
echo "suggestion_queue_path: " . ($cfg['suggestion_queue_path'] ?? '') . "\n";
echo "  exists: " . (is_file((string) ($cfg['suggestion_queue_path'] ?? '')) ? 'yes' : 'no') . "\n";
echo "merged_taxonomy_path: " . ($cfg['merged_taxonomy_path'] ?? '') . "\n";
echo "  exists: " . (is_file((string) ($cfg['merged_taxonomy_path'] ?? '')) ? 'yes' : 'no') . "\n";
echo "effective_screening_path: " . ai_taxonomy_effective_screening_path() . "\n\n";

$base = AiTaxonomyService::loadBaseTaxonomy();
echo 'Base taxonomy skills: ' . count($base) . "\n";

$custom = AiTaxonomyService::loadCustomTaxonomySkills($conn);
echo 'Custom taxonomy skills (DB): ' . count($custom) . "\n";

$pending = $conn->query("SELECT COUNT(*) FROM ai_taxonomy_suggestions WHERE status = 'pending_review'");
echo 'Pending suggestions: ' . (int) ($pending ? $pending->fetchColumn() : 0) . "\n\n";

if (is_file((string) ($cfg['suggestion_queue_path'] ?? ''))) {
    echo "Suggestion queue file found — import via Admin UI or:\n";
    echo "  admin/ai_taxonomy_suggestion_import.php\n";
} else {
    echo "Suggestion queue missing — chạy taxonomy_suggest.py trước khi import.\n";
}

echo "\nAdmin URLs:\n";
echo "  http://localhost/topcv_lite/admin/ai_taxonomy_suggestions.php\n";
echo "  http://localhost/topcv_lite/admin/ai_taxonomy_suggestion_import.php\n";
