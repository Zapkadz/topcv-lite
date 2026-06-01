<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_cvs.php';

if (cvs_schema_ready($conn)) {
    $stmt = $conn->query("SHOW TABLES LIKE 'cv_skills'");
    if ($stmt->fetch()) {
        echo "SKIP — CV-A tables đã tồn tại.\n";
        exit(0);
    }
}

require __DIR__ . '/_cv-a-migrate-steps.php';

try {
    foreach ($cv_a_migration_steps as $sql) {
        $conn->exec($sql);
    }
    echo "OK — CV-A structured tables migration done.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
}
