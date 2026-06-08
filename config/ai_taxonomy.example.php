<?php

/**
 * Cấu hình taxonomy AI (Admin suggestion management).
 *
 * Copy → config/ai_taxonomy.local.php (gitignore) và chỉnh đường dẫn local.
 *
 * @return array<string, string>
 */
return [
    'base_taxonomy_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\data\\taxonomy\\skills.json',
    'suggestion_queue_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\outputs\\taxonomy_suggestions.json',
    'merged_taxonomy_path' => 'C:\\topcv_ai_runtime\\taxonomy\\skills_merged.json',
];
