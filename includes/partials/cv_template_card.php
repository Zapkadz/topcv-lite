<?php

/**
 * Partial: 1 card trong gallery chọn mẫu CV.
 *
 * @var array{key: string, label: string, description: string, tags: list<string>} $tpl
 * @var array<string, scalar|null> $builderQuery
 */
$builderUrl = cv_template_builder_url((string) $tpl['key'], $builderQuery);
$previewHtml = cv_template_render_card_preview_html((string) $tpl['key']);
?>
<div class="col-sm-11 col-md-6 col-lg-5 col-xl-4">
    <div class="card border-0 shadow-sm h-100 cv-template-card">
        <div class="cv-template-preview-panel">
            <div class="cv-template-thumb-wrap" aria-hidden="true">
                <div class="cv-template-thumb-inner">
                    <?= $previewHtml ?>
                </div>
            </div>
        </div>
        <div class="cv-template-card-footer d-flex flex-column flex-grow-1">
            <div class="cv-template-card-title"><?= htmlspecialchars((string) $tpl['label']) ?></div>
            <div class="mb-1">
                <?php foreach ((array) ($tpl['tags'] ?? []) as $tag): ?>
                    <span class="cv-template-tag"><?= htmlspecialchars((string) $tag) ?></span>
                <?php endforeach; ?>
            </div>
            <p class="cv-template-card-desc mb-0"><?= htmlspecialchars((string) $tpl['description']) ?></p>
            <a href="<?= htmlspecialchars($builderUrl) ?>" class="btn btn-success cv-template-use-btn w-100 mt-auto">
                Dùng mẫu này
            </a>
        </div>
    </div>
</div>
