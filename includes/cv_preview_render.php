<?php

require_once __DIR__ . '/cv_avatar.php';
require_once __DIR__ . '/cv_rules.php';

if (!function_exists('cv_preview_normalize_data')) {
    /**
     * @param array<string, mixed> $data
     * @return array{
     *   profile: array<string, mixed>,
     *   educations: list,
     *   experiences: list,
     *   skills: list,
     *   activities: list,
     *   certificates: list,
     *   awards: list,
     *   references: list
     * }
     */
    function cv_preview_normalize_data(array $data): array
    {
        $profile = $data['profile'] ?? $data;

        return [
            'profile' => is_array($profile) ? $profile : [],
            'educations' => array_values($data['educations'] ?? []),
            'experiences' => array_values($data['experiences'] ?? []),
            'skills' => array_values($data['skills'] ?? []),
            'activities' => array_values($data['activities'] ?? []),
            'certificates' => array_values($data['certificates'] ?? []),
            'awards' => array_values($data['awards'] ?? []),
            'references' => array_values($data['references'] ?? []),
        ];
    }
}

if (!function_exists('cv_preview_period_range_label')) {
    function cv_preview_period_range_label(?string $start, ?string $end): string
    {
        $startLabel = cv_format_year_month_display($start);
        $endLabel = ($end ?? '') !== ''
            ? cv_format_year_month_display($end)
            : 'Hiện tại';

        return $startLabel . ' – ' . $endLabel;
    }
}

if (!function_exists('cv_preview_render_contact_list')) {
    /**
     * @param array<string, mixed> $p
     */
    function cv_preview_render_contact_list(array $p, string $extraClass = ''): void
    {
        $class = 'list-unstyled small mb-0 cv-preview-contact' . ($extraClass !== '' ? ' ' . $extraClass : '');
        ?>
        <ul class="<?= htmlspecialchars($class) ?>">
            <?php if (!empty($p['phone'])): ?>
                <li class="mb-1"><i class="fas fa-phone fa-fw me-1"></i><?= htmlspecialchars((string) $p['phone']) ?></li>
            <?php endif; ?>
            <?php if (!empty($p['email'])): ?>
                <li class="mb-1 text-break"><i class="fas fa-envelope fa-fw me-1"></i><?= htmlspecialchars((string) $p['email']) ?></li>
            <?php endif; ?>
            <?php if (!empty($p['date_of_birth'])): ?>
                <li class="mb-1"><i class="fas fa-calendar-alt fa-fw me-1"></i>Ngày sinh: <?= htmlspecialchars(cv_format_date_of_birth_display((string) $p['date_of_birth'])) ?></li>
            <?php endif; ?>
            <?php if (!empty($p['gender'])): ?>
                <li class="mb-1"><i class="fas fa-venus-mars fa-fw me-1"></i>Giới tính: <?= htmlspecialchars((string) $p['gender']) ?></li>
            <?php endif; ?>
            <?php if (!empty($p['address'])): ?>
                <li class="mb-1"><i class="fas fa-map-marker-alt fa-fw me-1"></i><?= htmlspecialchars((string) $p['address']) ?></li>
            <?php endif; ?>
            <?php if (!empty($p['website'])): ?>
                <li class="mb-1 text-break"><i class="fas fa-link fa-fw me-1"></i><?= htmlspecialchars((string) $p['website']) ?></li>
            <?php endif; ?>
        </ul>
        <?php
    }
}

if (!function_exists('cv_preview_render_body_sections')) {
    /**
     * @param array<string, mixed> $data
     */
    function cv_preview_render_body_sections(array $data, string $headingClass = 'h6 fw-bold text-success text-uppercase border-bottom pb-1'): void
    {
        $p = $data['profile'];

        if (!empty($p['career_objective'])): ?>
            <section class="mb-4">
                <h2 class="<?= htmlspecialchars($headingClass) ?>">Mục tiêu nghề nghiệp</h2>
                <p class="mb-0"><?= nl2br(htmlspecialchars((string) $p['career_objective'])) ?></p>
            </section>
        <?php endif;

        if (count($data['educations']) > 0): ?>
            <section class="mb-4">
                <h2 class="<?= htmlspecialchars($headingClass) ?>">Học vấn</h2>
                <?php foreach ($data['educations'] as $edu): ?>
                    <div class="mb-2">
                        <div class="fw-semibold">
                            <?= htmlspecialchars((string) ($edu['school_name'] ?? '')) ?>
                            <span class="text-muted fw-normal">(<?= htmlspecialchars(cv_preview_period_range_label($edu['start_date'] ?? null, $edu['end_date'] ?? null)) ?>)</span>
                        </div>
                        <?php if (!empty($edu['major'])): ?><div class="small"><?= htmlspecialchars((string) $edu['major']) ?></div><?php endif; ?>
                        <?php if (!empty($edu['description'])): ?><div class="small text-muted"><?= nl2br(htmlspecialchars((string) $edu['description'])) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif;

        if (count($data['experiences']) > 0): ?>
            <section class="mb-4">
                <h2 class="<?= htmlspecialchars($headingClass) ?>">Kinh nghiệm</h2>
                <?php foreach ($data['experiences'] as $exp): ?>
                    <div class="mb-2">
                        <div class="fw-semibold">
                            <?= htmlspecialchars((string) ($exp['position'] ?? 'Ứng viên')) ?>
                            — <?= htmlspecialchars((string) ($exp['company_name'] ?? '')) ?>
                            <span class="text-muted fw-normal">(<?= htmlspecialchars(cv_preview_period_range_label($exp['start_date'] ?? null, $exp['end_date'] ?? null)) ?>)</span>
                        </div>
                        <?php if (!empty($exp['description'])): ?><div class="small text-muted"><?= nl2br(htmlspecialchars((string) $exp['description'])) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif;

        if (count($data['activities']) > 0): ?>
            <section class="mb-4">
                <h2 class="<?= htmlspecialchars($headingClass) ?>">Hoạt động</h2>
                <?php foreach ($data['activities'] as $act): ?>
                    <div class="mb-2">
                        <div class="fw-semibold">
                            <?= htmlspecialchars((string) ($act['organization'] ?? '')) ?>
                            <?php if (!empty($act['role'])): ?> — <?= htmlspecialchars((string) $act['role']) ?><?php endif; ?>
                            <span class="text-muted fw-normal">(<?= htmlspecialchars(cv_preview_period_range_label($act['start_date'] ?? null, $act['end_date'] ?? null)) ?>)</span>
                        </div>
                        <?php if (!empty($act['description'])): ?><div class="small text-muted"><?= nl2br(htmlspecialchars((string) $act['description'])) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif;

        if (count($data['certificates']) > 0): ?>
            <section class="mb-4">
                <h2 class="<?= htmlspecialchars($headingClass) ?>">Chứng chỉ</h2>
                <ul class="mb-0">
                    <?php foreach ($data['certificates'] as $cert): ?>
                        <li class="mb-1">
                            <strong><?= htmlspecialchars((string) ($cert['certificate_name'] ?? '')) ?></strong>
                            <?php if (!empty($cert['issued_at'])): ?>
                                <span class="text-muted">(<?= htmlspecialchars(cv_format_year_month_display((string) $cert['issued_at'])) ?>)</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif;

        if (count($data['awards']) > 0): ?>
            <section class="mb-4">
                <h2 class="<?= htmlspecialchars($headingClass) ?>">Giải thưởng</h2>
                <?php foreach ($data['awards'] as $award): ?>
                    <div class="mb-2">
                        <div class="fw-semibold">
                            <?= htmlspecialchars((string) ($award['title'] ?? '')) ?>
                            <?php if (!empty($award['awarded_at'])): ?>
                                <span class="text-muted fw-normal">(<?= htmlspecialchars(cv_format_year_month_display((string) $award['awarded_at'])) ?>)</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($award['description'])): ?><div class="small text-muted"><?= nl2br(htmlspecialchars((string) $award['description'])) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif;

        if (count($data['skills']) > 0): ?>
            <section class="mb-4">
                <h2 class="<?= htmlspecialchars($headingClass) ?>">Kỹ năng</h2>
                <ul class="mb-0">
                    <?php foreach ($data['skills'] as $skill): ?>
                        <li>
                            <strong><?= htmlspecialchars((string) ($skill['skill_name'] ?? '')) ?></strong>
                            <?php if (!empty($skill['description'])): ?> — <?= htmlspecialchars((string) $skill['description']) ?><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif;

        if (!empty($p['interests'])): ?>
            <section class="mb-4">
                <h2 class="<?= htmlspecialchars($headingClass) ?>">Sở thích</h2>
                <p class="mb-0"><?= nl2br(htmlspecialchars((string) $p['interests'])) ?></p>
            </section>
        <?php endif;

        if (count($data['references']) > 0): ?>
            <section>
                <h2 class="<?= htmlspecialchars($headingClass) ?>">Người giới thiệu</h2>
                <?php foreach ($data['references'] as $ref): ?>
                    <div class="mb-2">
                        <div class="fw-semibold"><?= htmlspecialchars((string) ($ref['full_name'] ?? '')) ?></div>
                        <?php if (!empty($ref['position'])): ?><div class="small"><?= htmlspecialchars((string) $ref['position']) ?></div><?php endif; ?>
                        <?php if (!empty($ref['contact_info'])): ?><div class="small text-muted"><?= htmlspecialchars((string) $ref['contact_info']) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif;
    }
}

if (!function_exists('cv_render_preview_classic_html')) {
    /**
     * @param array<string, mixed> $data
     */
    function cv_render_preview_classic_html(array $data): string
    {
        $data = cv_preview_normalize_data($data);
        $p = $data['profile'];
        $avatarUrl = cv_avatar_public_url($p['avatar_path'] ?? null);
        ob_start();
        ?>
        <article class="cv-preview-classic bg-white p-4 p-md-5 border rounded shadow-sm">
            <header class="d-flex flex-column flex-md-row gap-3 align-items-center align-items-md-start border-bottom pb-3 mb-4">
                <?php if ($avatarUrl): ?>
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Ảnh đại diện" width="120" height="120"
                        class="rounded flex-shrink-0" style="object-fit:cover;">
                <?php endif; ?>
                <div class="text-center text-md-start flex-grow-1">
                    <h1 class="h3 fw-bold mb-1"><?= htmlspecialchars((string) ($p['full_name'] ?? '')) ?></h1>
                    <p class="text-success fw-semibold mb-2"><?= htmlspecialchars((string) ($p['target_position'] ?? '')) ?></p>
                    <?php cv_preview_render_contact_list($p); ?>
                </div>
            </header>
            <?php cv_preview_render_body_sections($data); ?>
        </article>
        <?php

        return (string) ob_get_clean();
    }
}

if (!function_exists('cv_render_preview_modern_html')) {
    /**
     * @param array<string, mixed> $data
     */
    function cv_render_preview_modern_html(array $data): string
    {
        $data = cv_preview_normalize_data($data);
        $p = $data['profile'];
        $avatarUrl = cv_avatar_public_url($p['avatar_path'] ?? null);
        ob_start();
        ?>
        <article class="cv-preview-modern bg-white border rounded shadow-sm overflow-hidden">
            <div class="row g-0">
                <aside class="col-md-4 cv-preview-modern-sidebar text-white p-4 p-md-5">
                    <?php if ($avatarUrl): ?>
                        <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Ảnh đại diện" width="140" height="140"
                            class="rounded-circle d-block mx-auto mb-3 border border-2 border-light" style="object-fit:cover;">
                    <?php endif; ?>
                    <h1 class="h4 fw-bold text-center mb-1"><?= htmlspecialchars((string) ($p['full_name'] ?? '')) ?></h1>
                    <p class="text-center small mb-4 opacity-75"><?= htmlspecialchars((string) ($p['target_position'] ?? '')) ?></p>
                    <?php cv_preview_render_contact_list($p, 'text-white-50'); ?>
                </aside>
                <main class="col-md-8 p-4 p-md-5">
                    <?php cv_preview_render_body_sections($data, 'h6 fw-bold text-primary text-uppercase border-bottom border-primary pb-1'); ?>
                </main>
            </div>
        </article>
        <?php

        return (string) ob_get_clean();
    }
}

if (!function_exists('cv_render_preview_html')) {
    /**
     * @param array<string, mixed> $data
     */
    function cv_render_preview_html(array $data): string
    {
        $normalized = cv_preview_normalize_data($data);
        $template = cv_normalize_template_key((string) ($normalized['profile']['template_key'] ?? 'classic'));

        return $template === 'modern'
            ? cv_render_preview_modern_html($normalized)
            : cv_render_preview_classic_html($normalized);
    }
}

if (!function_exists('cv_render_snapshot_from_json')) {
    function cv_render_snapshot_from_json(?string $json): string
    {
        if ($json === null || trim($json) === '') {
            return '<p class="text-muted mb-0">Không có dữ liệu CV online.</p>';
        }

        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['profile'])) {
            return '<p class="text-danger mb-0">Dữ liệu snapshot không hợp lệ.</p>';
        }

        return cv_render_preview_html($data);
    }
}
