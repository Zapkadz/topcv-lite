<?php
/**
 * Mẫu cấu hình AI screening (EMP-B) — gọi Python CLI.
 * Copy -> config/ai_screening.local.php (gitignore).
 *
 * Python project: C:\SEMANTIC_SKILLS_RESUME (ngoài topcv_lite)
 */
return [
    'python_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\.venv\\Scripts\\python.exe',
    'main_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\main.py',
    'taxonomy_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\data\\taxonomy\\skills.json',
    'runtime_dir' => 'C:\\topcv_ai_runtime',
    'cli_timeout_seconds' => 120,
    'enabled' => true,
];
