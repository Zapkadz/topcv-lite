<?php

require __DIR__ . '/../../includes/cv_import_pdf_quality.php';
require __DIR__ . '/../../includes/cv_import_vip.php';

$p = 'uploads/cv/import/6_20260605110257_4826b4b2.pdf';
$abs = realpath(dirname(__DIR__, 2) . '/' . $p);
if ($abs === false) {
    fwrite(STDERR, "PDF not found\n");
    exit(1);
}

$pending = cv_import_build_pending_from_path(6, $abs, $p, 'test.pdf');
echo 'route_auto=' . $pending['route_auto'] . PHP_EOL;
echo 'noisy=' . (cv_import_quality_is_noisy($pending['quality']) ? 'yes' : 'no') . PHP_EOL;
$q = cv_import_gpt_quota_check(6);
echo 'quota remaining=' . $q['remaining'] . PHP_EOL;
