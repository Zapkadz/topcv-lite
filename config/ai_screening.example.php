<?php
/**
 * Mẫu cấu hình AI screening — copy -> config/ai_screening.local.php (gitignore).
 *
 * driver=api  : FastAPI POST JSON (mặc định, khuyến nghị)
 * driver=cli  : Python CLI + file runtime (rollback)
 */
return [
    'driver' => 'api',
    'api_url' => 'http://127.0.0.1:8000/screening',
    'health_url' => 'http://127.0.0.1:8000/health',
    'api_timeout_seconds' => 180,
    'connect_timeout_seconds' => 5,
    'enabled' => true,

    // Chỉ dùng khi driver=cli
    'python_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\.venv\\Scripts\\python.exe',
    'main_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\main.py',
    'taxonomy_path' => 'C:\\SEMANTIC_SKILLS_RESUME\\data\\taxonomy\\skills.json',
    'runtime_dir' => 'C:\\topcv_ai_runtime',
    'cli_timeout_seconds' => 180,
    'hf_hub_offline' => true,
    'enable_embedding' => true,
    'embedding_model' => 'BAAI/bge-m3',
    'embedding_local_only' => true,
];
