<?php
declare(strict_types=1);

/**
 * F6 — Batch test 4 loại PDF (router + parse pipeline).
 *
 * Đặt file vào uploads/cv/f6/ (tên gợi ý):
 *   t1-topcv-text.pdf   — PDF text sạch TopCV
 *   t2-canva.pdf        — Canva 2 cột
 *   t3-scan.pdf         — Scan ảnh
 *   t4-en-2page.pdf     — CV EN 2 trang
 *
 * Hoặc truyền path từng file:
 *   php _test-cv-f6-batch.php --t1=path --t2=path --t3=path --t4=path
 *   php _test-cv-f6-batch.php --all   (chỉ chạy file có trong f6/)
 *
 * @see docs/project-memory/phase-cv-f-checklist.md F6
 */

require_once __DIR__ . '/../../includes/services/PdfTextExtractor.php';
require_once __DIR__ . '/../../includes/cv_import_text_clean.php';
require_once __DIR__ . '/../../includes/cv_import_pdf_quality.php';
require_once __DIR__ . '/../../includes/ai_config.php';
require_once __DIR__ . '/../../includes/services/CvParseService.php';

$root = realpath(__DIR__ . '/../..') ?: dirname(__DIR__, 2);
$f6Dir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cv' . DIRECTORY_SEPARATOR . 'f6';

$slots = [
    'T1' => [
        'label' => 'TopCV text sạch',
        'glob' => ['t1*.pdf', '*topcv*.pdf', '*text*.pdf'],
        'parse_mode' => 'text',
        'expect_route' => ['text_fast'],
        'validate' => static function (array $r, array $route): array {
            $errors = [];
            if (!in_array($route['mode'], ['text_fast', 'text_fast_forced'], true)) {
                $errors[] = 'router nên gợi ý text_fast, got ' . $route['mode'];
            }
            if (!$r['ok']) {
                $errors[] = 'parse failed: ' . ($r['message'] ?? '');

                return $errors;
            }
            $p = $r['profile'] ?? [];
            if (trim((string) ($p['full_name'] ?? '')) === '' && trim((string) ($p['email'] ?? '')) === '') {
                $errors[] = 'thiếu full_name và email';
            }
            $ch = $r['children'] ?? [];
            $edu = is_array($ch['educations'] ?? null) ? count($ch['educations']) : 0;
            $exp = is_array($ch['experiences'] ?? null) ? count($ch['experiences']) : 0;
            if ($edu + $exp < 1) {
                $errors[] = 'cần ≥1 education hoặc experience';
            }

            return $errors;
        },
    ],
    'T2' => [
        'label' => 'Canva 2 cột',
        'glob' => ['t2*.pdf', '*canva*.pdf'],
        'parse_mode' => 'vision',
        'expect_route' => ['vision_gpt', 'vision_gpt_forced'],
        'validate' => static function (array $r, array $route): array {
            $errors = [];
            if (!$r['ok']) {
                $errors[] = 'parse failed: ' . ($r['message'] ?? '');

                return $errors;
            }
            $meta = $r['meta'] ?? [];
            $mode = (string) ($meta['parse_mode'] ?? '');
            if (!in_array($mode, ['vision_gpt', 'vision_gpt_forced'], true) && ($meta['parse_source'] ?? '') !== 'vision') {
                $errors[] = 'kỳ vọng vision parse, got mode=' . $mode;
            }
            $p = $r['profile'] ?? [];
            if (trim((string) ($p['full_name'] ?? '')) === '') {
                $errors[] = 'thiếu full_name';
            }
            $ch = $r['children'] ?? [];
            $skills = is_array($ch['skills'] ?? null) ? count($ch['skills']) : 0;
            $exp = is_array($ch['experiences'] ?? null) ? count($ch['experiences']) : 0;
            $edu = is_array($ch['educations'] ?? null) ? count($ch['educations']) : 0;
            if ($skills + $exp + $edu < 1) {
                $errors[] = 'cần ≥1 skill/experience/education';
            }

            return $errors;
        },
    ],
    'T3' => [
        'label' => 'Scan ảnh',
        'glob' => ['t3*.pdf', '*scan*.pdf'],
        'parse_mode' => 'vision',
        'expect_route' => ['vision_gpt', 'vision_gpt_forced'],
        'validate' => static function (array $r, array $route): array {
            $errors = [];
            if (!$r['ok']) {
                $errors[] = 'parse failed: ' . ($r['message'] ?? '');

                return $errors;
            }
            $p = $r['profile'] ?? [];
            $hasContact = trim((string) ($p['full_name'] ?? '')) !== ''
                || trim((string) ($p['email'] ?? '')) !== ''
                || trim((string) ($p['phone'] ?? '')) !== '';
            if (!$hasContact) {
                $errors[] = 'thiếu contact (tên/email/phone)';
            }

            return $errors;
        },
    ],
    'T4' => [
        'label' => 'CV EN 2 trang',
        'glob' => ['t4*.pdf', '*en*.pdf', '*english*.pdf'],
        'parse_mode' => 'vision',
        'expect_route' => ['vision_gpt', 'vision_gpt_forced', 'text_fast'],
        'validate' => static function (array $r, array $route): array {
            $errors = [];
            if (!$r['ok']) {
                $errors[] = 'parse failed: ' . ($r['message'] ?? '');

                return $errors;
            }
            $ch = $r['children'] ?? [];
            $dates = [];
            foreach (['educations', 'experiences', 'projects'] as $sec) {
                if (!is_array($ch[$sec] ?? null)) {
                    continue;
                }
                foreach ($ch[$sec] as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    foreach (['start_date', 'end_date'] as $k) {
                        $d = trim((string) ($row[$k] ?? ''));
                        if ($d !== '') {
                            $dates[] = $d;
                        }
                    }
                }
            }
            if ($dates === []) {
                $errors[] = 'không có date nào để kiểm tra YYYY-MM';

                return $errors;
            }
            foreach ($dates as $d) {
                if (!preg_match('/^\d{4}-\d{2}$/', $d)) {
                    $errors[] = 'date không YYYY-MM: ' . $d;
                }
            }

            return $errors;
        },
    ],
];

function f6_resolve_path(string $root, string $path): ?string
{
    if ($path === '') {
        return null;
    }
    if (is_file($path)) {
        return realpath($path) ?: $path;
    }
    $candidate = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

    return is_file($candidate) ? (realpath($candidate) ?: $candidate) : null;
}

function f6_find_in_dir(string $dir, array $patterns): ?string
{
    if (!is_dir($dir)) {
        return null;
    }
    foreach ($patterns as $pattern) {
        $matches = glob($dir . DIRECTORY_SEPARATOR . $pattern);
        if ($matches !== false && $matches !== []) {
            return realpath($matches[0]) ?: $matches[0];
        }
    }

    return null;
}

function f6_analyze_route(string $pdfPath): array
{
    $extract = PdfTextExtractor::extractLenient($pdfPath);
    $rawText = (string) ($extract['text'] ?? '');
    $cleanResult = cv_import_clean_extracted_text($rawText);
    $quality = cv_import_analyze_pdf_quality($rawText, $cleanResult);

    return cv_import_resolve_parse_mode('auto', $quality, ai_openai_ready());
}

function f6_run_slot(string $id, array $slot, string $pdfPath): array
{
    $route = f6_analyze_route($pdfPath);
    $parseMode = (string) ($slot['parse_mode'] ?? 'auto');
    $result = CvParseService::importFromPdfPath($pdfPath, ['parse_mode' => $parseMode]);
    $errors = ($slot['validate'])($result, $route);

    return [
        'id' => $id,
        'label' => $slot['label'],
        'path' => $pdfPath,
        'route_mode' => $route['mode'],
        'route_reason' => $route['reason'],
        'parse_ok' => !empty($result['ok']),
        'parse_mode' => (string) (($result['meta'] ?? [])['parse_mode'] ?? ''),
        'full_name' => (string) (($result['profile'] ?? [])['full_name'] ?? ''),
        'email' => (string) (($result['profile'] ?? [])['email'] ?? ''),
        'errors' => $errors,
        'pass' => $errors === [] && !empty($result['ok']),
    ];
}

// CLI args
$paths = [];
$runAll = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--all') {
        $runAll = true;
    } elseif (preg_match('/^--(t[1-4])=(.+)$/', $arg, $m)) {
        $paths[strtoupper($m[1])] = $m[2];
    }
}

if (!is_dir($f6Dir)) {
    @mkdir($f6Dir, 0755, true);
}

echo "=== CV-F F6 batch test ===\n";
echo 'openai_ready=' . (ai_openai_ready() ? 'yes' : 'no') . "\n";
echo 'f6_dir=' . $f6Dir . "\n\n";

$results = [];
$missing = [];

foreach ($slots as $id => $slot) {
    $pdfPath = null;
    if (isset($paths[$id])) {
        $pdfPath = f6_resolve_path($root, $paths[$id]);
    } elseif ($runAll || !array_filter($paths)) {
        $pdfPath = f6_find_in_dir($f6Dir, $slot['glob']);
    }

    if ($pdfPath === null) {
        $missing[] = $id;
        echo "[{$id}] SKIP — chưa có PDF ({$slot['label']})\n";
        echo "      Đặt file vào uploads/cv/f6/ hoặc --{$id}=path\n\n";
        continue;
    }

    echo "[{$id}] {$slot['label']}\n";
    echo '      file=' . basename($pdfPath) . "\n";

    $row = f6_run_slot($id, $slot, $pdfPath);
    $results[] = $row;

    echo '      route=' . $row['route_mode'] . ' (' . $row['route_reason'] . ")\n";
    echo '      parse=' . ($row['parse_ok'] ? 'ok' : 'FAIL') . ' mode=' . $row['parse_mode'] . "\n";
    echo '      name=' . $row['full_name'] . ' email=' . $row['email'] . "\n";
    if ($row['pass']) {
        echo "      => PASS\n\n";
    } else {
        echo "      => FAIL\n";
        foreach ($row['errors'] as $e) {
            echo '         - ' . $e . "\n";
        }
        echo "\n";
    }
}

$passed = count(array_filter($results, static fn(array $r): bool => $r['pass']));
$ran = count($results);
$total = count($slots);

echo "=== Summary: {$passed}/{$ran} ran, {$total} slots ===\n";

if ($missing !== []) {
    echo 'Missing PDFs: ' . implode(', ', $missing) . "\n";
    echo "Copy PDF vào uploads/cv/f6/ (t1-topcv-text.pdf, t2-canva.pdf, t3-scan.pdf, t4-en-2page.pdf)\n";
}

if ($ran === 0) {
    exit(2);
}

exit($passed === $ran && $ran === $total ? 0 : 1);
