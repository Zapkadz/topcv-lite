<?php

require_once __DIR__ . '/cv_avatar.php';

if (!function_exists('cv_render_preview_html')) {
    /**
     * @param array{profile: array<string, mixed>, educations: list, experiences: list, skills: list} $data
     */
    function cv_render_preview_html(array $data): string
    {
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
                <ul class="list-unstyled small text-muted mb-0 cv-preview-contact">
                    <?php if (!empty($p['phone'])): ?>
                        <li class="mb-1">
                            <i class="fas fa-phone fa-fw me-1"></i>
                            <?= htmlspecialchars((string) $p['phone']) ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($p['email'])): ?>
                        <li class="mb-1 text-break">
                            <i class="fas fa-envelope fa-fw me-1"></i>
                            <?= htmlspecialchars((string) $p['email']) ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($p['date_of_birth'])): ?>
                        <li class="mb-1">
                            <i class="fas fa-calendar-alt fa-fw me-1"></i>
                            Ngày sinh: <?= htmlspecialchars(cv_format_date_of_birth_display((string) $p['date_of_birth'])) ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($p['gender'])): ?>
                        <li class="mb-1">
                            <i class="fas fa-venus-mars fa-fw me-1"></i>
                            Giới tính: <?= htmlspecialchars((string) $p['gender']) ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($p['address'])): ?>
                        <li class="mb-1">
                            <i class="fas fa-map-marker-alt fa-fw me-1"></i>
                            <?= htmlspecialchars((string) $p['address']) ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($p['website'])): ?>
                        <li class="mb-1 text-break">
                            <i class="fas fa-link fa-fw me-1"></i>
                            <?= htmlspecialchars((string) $p['website']) ?>
                        </li>
                    <?php endif; ?>
                </ul>
                </div>
            </header>

            <?php if (!empty($p['career_objective'])): ?>
                <section class="mb-4">
                    <h2 class="h6 fw-bold text-success text-uppercase border-bottom pb-1">Mục tiêu nghề nghiệp</h2>
                    <p class="mb-0"><?= nl2br(htmlspecialchars((string) $p['career_objective'])) ?></p>
                </section>
            <?php endif; ?>

            <?php if (count($data['educations']) > 0): ?>
                <section class="mb-4">
                    <h2 class="h6 fw-bold text-success text-uppercase border-bottom pb-1">Học vấn</h2>
                    <?php foreach ($data['educations'] as $edu): ?>
                        <div class="mb-2">
                            <div class="fw-semibold">
                                <?= htmlspecialchars((string) ($edu['school_name'] ?? '')) ?>
                                <span class="text-muted fw-normal">
                                    (<?= htmlspecialchars(cv_format_year_month_display($edu['start_date'] ?? null)) ?>
                                    –
                                    <?= htmlspecialchars(
                                        ($edu['end_date'] ?? '') !== ''
                                            ? cv_format_year_month_display($edu['end_date'])
                                            : 'Hiện tại'
                                    ) ?>)
                                </span>
                            </div>
                            <?php if (!empty($edu['major'])): ?>
                                <div class="small"><?= htmlspecialchars((string) $edu['major']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($edu['description'])): ?>
                                <div class="small text-muted"><?= nl2br(htmlspecialchars((string) $edu['description'])) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if (count($data['experiences']) > 0): ?>
                <section class="mb-4">
                    <h2 class="h6 fw-bold text-success text-uppercase border-bottom pb-1">Kinh nghiệm</h2>
                    <?php foreach ($data['experiences'] as $exp): ?>
                        <div class="mb-2">
                            <div class="fw-semibold">
                                <?= htmlspecialchars((string) ($exp['position'] ?? 'Ứng viên')) ?>
                                — <?= htmlspecialchars((string) ($exp['company_name'] ?? '')) ?>
                                <span class="text-muted fw-normal">
                                    (<?= htmlspecialchars(cv_format_year_month_display($exp['start_date'] ?? null)) ?>
                                    –
                                    <?= htmlspecialchars(
                                        ($exp['end_date'] ?? '') !== ''
                                            ? cv_format_year_month_display($exp['end_date'])
                                            : 'Hiện tại'
                                    ) ?>)
                                </span>
                            </div>
                            <?php if (!empty($exp['description'])): ?>
                                <div class="small text-muted"><?= nl2br(htmlspecialchars((string) $exp['description'])) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if (count($data['skills']) > 0): ?>
                <section>
                    <h2 class="h6 fw-bold text-success text-uppercase border-bottom pb-1">Kỹ năng</h2>
                    <ul class="mb-0">
                        <?php foreach ($data['skills'] as $skill): ?>
                            <li>
                                <strong><?= htmlspecialchars((string) ($skill['skill_name'] ?? '')) ?></strong>
                                <?php if (!empty($skill['description'])): ?>
                                    — <?= htmlspecialchars((string) $skill['description']) ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </article>
        <?php

        return (string) ob_get_clean();
    }
}
