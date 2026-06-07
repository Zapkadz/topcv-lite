<?php
require_once __DIR__ . '/../../config/db.php';
$sql = trim(file_get_contents(__DIR__ . '/phase-emp-b-ai-screening.sql'));
try {
    $conn->exec($sql);
    echo "exec ok\n";
    $s = $conn->query("SHOW TABLES LIKE 'ai_screening_results'");
    var_export($s->fetch(PDO::FETCH_NUM));
} catch (Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
}
