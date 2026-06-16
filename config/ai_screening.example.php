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

    // Debug API payload/response (chỉ bật trên local — không bật production)
    // 'debug_api_payload' => true,
    // 'debug_api_dir' => 'C:\\topcv_ai_runtime\\api-debug',

    // Candidate-side job recommendation (Phase 23)
    // 'recommend_jobs_api_url' => 'http://127.0.0.1:8000/recommend-jobs',
    // 'recommend_top_k' => 10,
    // 'recommend_retrieval_top_n' => 50,
    // 'recommend_min_cv_text_length' => 150,
];
