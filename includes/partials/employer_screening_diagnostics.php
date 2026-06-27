<?php
/** @var array<string, mixed> $employerDiagBanner */
/** @var array<string, mixed> $employerDiagDebug */

if (empty($employerDiagBanner['show'])) {
    echo ai_diag_render_debug_block(is_array($employerDiagDebug ?? null) ? $employerDiagDebug : []);

    return;
}

$badge = is_array($employerDiagBanner['badge'] ?? null) ? $employerDiagBanner['badge'] : ai_diag_confidence_badge('unknown');
$technicalDetails = is_array($employerDiagBanner['technical_details'] ?? null) ? $employerDiagBanner['technical_details'] : [];
?>

<div class="alert <?= htmlspecialchars((string) ($badge['alert_class'] ?? 'alert-secondary')) ?> border-0 shadow-sm mb-3">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <span class="fw-bold"><i class="fas fa-shield-alt me-1"></i> Độ tin cậy AI</span>
        <span class="badge <?= htmlspecialchars((string) ($badge['class'] ?? 'bg-light text-dark border')) ?> border">
            <?= htmlspecialchars((string) ($badge['label'] ?? 'Chưa xác định')) ?>
        </span>
        <span class="badge bg-light text-dark border">
            <?= htmlspecialchars((string) ($employerDiagBanner['review_status'] ?? '')) ?>
        </span>
    </div>
    <p class="small mb-0"><?= htmlspecialchars((string) ($employerDiagBanner['summary'] ?? '')) ?></p>
</div>

<?php if ($technicalDetails !== []): ?>
<details class="mb-4 border rounded-3 shadow-sm bg-white">
    <summary class="px-3 py-2 fw-bold small text-secondary">Chi tiết kỹ thuật</summary>
    <div class="px-3 pb-3 pt-1 small">
        <?php foreach ($technicalDetails as $row): ?>
            <?php if (!is_array($row)) {
                continue;
            } ?>
            <div class="mb-2">
                <strong><?= htmlspecialchars((string) ($row['label'] ?? '')) ?>:</strong>
                <?= htmlspecialchars((string) ($row['value'] ?? '')) ?>
            </div>
        <?php endforeach; ?>
    </div>
</details>
<?php endif; ?>

<?= ai_diag_render_debug_block(is_array($employerDiagDebug ?? null) ? $employerDiagDebug : []) ?>
