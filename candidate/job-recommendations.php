<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_cvs.php';
require_once __DIR__ . '/../includes/cv_rules.php';
require_once __DIR__ . '/../includes/ai_screening_config.php';
require_once __DIR__ . '/../includes/job_recommendation_rules.php';
require_once __DIR__ . '/../includes/services/CvService.php';
require_once __DIR__ . '/../includes/services/JobRecommendationService.php';
require_once __DIR__ . '/../includes/repositories/JobRepository.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$schemaReady = cvs_schema_ready($conn);
$cvs = $schemaReady ? CvService::listForUser($conn, $userId) : [];
$openJobCount = $schemaReady ? JobRepository::countOpenForRecommendation($conn) : 0;

$defaultCvId = 0;
foreach ($cvs as $cv) {
    if ((int) ($cv['is_primary'] ?? 0) === 1) {
        $defaultCvId = (int) $cv['id'];
        break;
    }
}
if ($defaultCvId === 0 && $cvs !== []) {
    $defaultCvId = (int) $cvs[0]['id'];
}

$selectedCvId = isset($_GET['cv_id']) ? (int) $_GET['cv_id'] : $defaultCvId;
$validCv = false;
foreach ($cvs as $cv) {
    if ((int) ($cv['id'] ?? 0) === $selectedCvId) {
        $validCv = true;
        break;
    }
}
if (!$validCv) {
    $selectedCvId = $defaultCvId;
}

$panelHint = $schemaReady && $selectedCvId > 0
    ? JobRecommendationService::buildPanelHint($conn, $userId, $selectedCvId)['hint']
    : ($schemaReady ? '' : 'Schema CV chưa sẵn sàng.');

$canRun = $schemaReady
    && $selectedCvId > 0
    && $panelHint === ''
    && ai_screening_config_ready();

$sessionResult = JobRecommendationService::getSessionResult();
$appliedJobIds = [];
if ($sessionResult !== null && $schemaReady) {
    $candidateId = CvService::resolveCandidateId($conn, $userId);
    if ($candidateId !== null) {
        $appliedJobIds = JobRecommendationService::loadAppliedJobIds($conn, $candidateId);
    }
}

include '../includes/header.php';
?>

<div id="recLoadingOverlay" class="rec-loading-overlay d-none" aria-hidden="true">
    <div class="rec-loading-box text-center">
        <div class="spinner-border text-success mb-3" role="status"></div>
        <div class="fw-bold">Đang phân tích CV…</div>
        <p class="small text-muted mb-0">
            Đang so khớp với <?= (int) $openJobCount ?> tin tuyển dụng.
            Có thể mất 1–3 phút — vui lòng không đóng trang.
        </p>
    </div>
</div>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="cv-manage.php">Quản lý CV</a></li>
            <li class="breadcrumb-item active" aria-current="page">AI gợi ý việc làm</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fas fa-robot text-success"></i> AI gợi ý việc làm phù hợp</h3>
            <p class="text-muted mb-0">Phân tích CV hiện tại và gợi ý top công việc phù hợp cùng điểm cần cải thiện.</p>
        </div>
        <a href="cv-manage.php" class="btn btn-outline-success btn-sm"><i class="fas fa-arrow-left"></i> Quản lý CV</a>
    </div>

    <?php if (!$schemaReady): ?>
        <?= cvs_schema_migration_hint_html() ?>
    <?php elseif ($cvs === []): ?>
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h5 class="fw-bold">Chưa có CV online</h5>
                <p class="text-muted">Tạo CV có cấu trúc để AI phân tích chính xác hơn.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="<?= htmlspecialchars(cv_template_picker_url()) ?>" class="btn btn-success fw-bold">Tạo CV mới</a>
                    <a href="cv-import.php" class="btn btn-outline-success fw-bold">Tạo từ PDF</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="POST" action="run-job-recommendation.php" id="recRunForm" class="row g-3 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_job_recommendation_form')) ?>">
                    <div class="col-md-6">
                        <label class="form-label fw-bold" for="cv_profile_id">Chọn CV để phân tích</label>
                        <select name="cv_profile_id" id="cv_profile_id" class="form-select" onchange="location.href='job-recommendations.php?cv_id=' + this.value;">
                            <?php foreach ($cvs as $cv): ?>
                                <?php
                                $cvId = (int) $cv['id'];
                                $label = (string) ($cv['title'] ?? 'CV');
                                if ((int) ($cv['is_primary'] ?? 0) === 1) {
                                    $label .= ' ★';
                                }
                                $label .= ' — ' . (int) ($cv['completion_percent'] ?? 0) . '%';
                                ?>
                                <option value="<?= $cvId ?>" <?= $cvId === $selectedCvId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2">
                            <a href="cv-preview.php?id=<?= (int) $selectedCvId ?>" class="small text-primary" target="_blank" rel="noopener">
                                <i class="fas fa-eye"></i> Xem trước CV đã chọn
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-light text-dark border">
                                <i class="fas fa-briefcase"></i> <?= (int) $openJobCount ?> tin đang tuyển
                            </span>
                            <?php if ($sessionResult !== null && !empty($sessionResult['ran_at'])): ?>
                                <span class="badge bg-light text-dark border">
                                    <i class="fas fa-clock"></i> Lần chạy: <?= date('d/m/Y H:i', (int) $sessionResult['ran_at']) ?>
                                </span>
                                <?php if (!empty($sessionResult['trace_id'])): ?>
                                    <span class="badge bg-light text-dark border">
                                        Trace: <?= htmlspecialchars((string) $sessionResult['trace_id']) ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($panelHint !== ''): ?>
                            <p class="small text-warning mb-2"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($panelHint) ?></p>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-success fw-bold" id="recRunBtn" <?= $canRun ? '' : 'disabled' ?>>
                            <i class="fas fa-magic"></i> Chạy AI gợi ý
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($sessionResult !== null && !empty($sessionResult['ran_at'])): ?>
            <?php include __DIR__ . '/../includes/partials/job_recommendation_results.php'; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="recDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="recDetailTitle">Chi tiết gợi ý</h5>
                    <div class="small text-muted" id="recDetailMeta"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="recDetailBody"></div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<style>
.rec-loading-overlay {
    position: fixed;
    inset: 0;
    background: rgba(255, 255, 255, 0.85);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.rec-loading-box {
    max-width: 360px;
    padding: 1.5rem;
}
</style>

<script>
document.getElementById('recRunForm')?.addEventListener('submit', function () {
    var overlay = document.getElementById('recLoadingOverlay');
    var btn = document.getElementById('recRunBtn');
    if (overlay) {
        overlay.classList.remove('d-none');
    }
    if (btn) {
        btn.disabled = true;
    }
});

function recEscape(text) {
    if (text == null) {
        return '';
    }
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function recListHtml(items, emptyText) {
    if (!Array.isArray(items) || items.length === 0) {
        return '<p class="text-muted small mb-0">' + recEscape(emptyText) + '</p>';
    }
    return '<ul class="mb-0 ps-3">' + items.map(function (item) {
        if (typeof item === 'object' && item !== null) {
            return '<li>' + recEscape(item.skill || item.name || item.label || JSON.stringify(item)) + '</li>';
        }
        return '<li>' + recEscape(item) + '</li>';
    }).join('') + '</ul>';
}

function recGapListHtml(gaps, key) {
    var items = gaps && gaps[key] ? gaps[key] : [];
    if (!Array.isArray(items) || items.length === 0) {
        return '<p class="text-muted small mb-0">Không có mục nào.</p>';
    }
    return '<ul class="mb-0 ps-3">' + items.map(function (item) {
        if (typeof item === 'string') {
            return '<li>' + recEscape(item) + '</li>';
        }
        var label = item.skill || item.requirement || item.name || item.label || '';
        var detail = item.reason || item.detail || item.note || '';
        var line = recEscape(label);
        if (detail) {
            line += ' — <span class="text-muted">' + recEscape(detail) + '</span>';
        }
        return '<li>' + line + '</li>';
    }).join('') + '</ul>';
}

document.querySelectorAll('.js-rec-detail').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var raw = btn.getAttribute('data-job');
        if (!raw) {
            return;
        }
        var job;
        try {
            job = JSON.parse(raw);
        } catch (e) {
            return;
        }

        document.getElementById('recDetailTitle').textContent = job.job_title || 'Chi tiết gợi ý';
        var meta = [];
        if (job.fit_label) {
            meta.push(job.fit_label);
        }
        if (job.fit_score != null) {
            meta.push(job.fit_score + ' điểm');
        }
        document.getElementById('recDetailMeta').textContent = meta.join(' · ');

        var gaps = job.skill_gaps || {};
        var reviewCard = job.review_card || {};
        var evidence = reviewCard.evidence_highlights || job.matched_must_have_skills || [];

        var html = '<div class="accordion" id="recDetailAccordion">';
        html += '<div class="accordion-item"><h2 class="accordion-header">';
        html += '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#recWhyFit">Vì sao phù hợp</button></h2>';
        html += '<div id="recWhyFit" class="accordion-collapse collapse show" data-bs-parent="#recDetailAccordion"><div class="accordion-body">';
        html += '<p>' + recEscape(job.fit_summary || '') + '</p>';
        html += recListHtml(job.why_fit, 'Chưa có lý do cụ thể.');
        html += '</div></div></div>';

        html += '<div class="accordion-item"><h2 class="accordion-header">';
        html += '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recGaps">Điểm còn thiếu / yếu</button></h2>';
        html += '<div id="recGaps" class="accordion-collapse collapse" data-bs-parent="#recDetailAccordion"><div class="accordion-body">';
        var s = job.skill_gap_summary || {};
        var gapLine = [];
        if (s.missing_must_have_count > 0) gapLine.push('Thiếu bắt buộc: ' + s.missing_must_have_count);
        if (s.weak_evidence_count > 0) gapLine.push('Bằng chứng yếu: ' + s.weak_evidence_count);
        if (s.optional_growth_count > 0) gapLine.push('Phát triển thêm: ' + s.optional_growth_count);
        html += '<p class="small text-muted">' + recEscape(gapLine.join(' · ') || 'Không có thiếu hụt lớn.') + '</p>';
        html += '<h6 class="fw-bold small">Thiếu bắt buộc</h6>' + recGapListHtml(gaps, 'missing_must_have');
        html += '<h6 class="fw-bold small mt-3">Bằng chứng yếu</h6>' + recGapListHtml(gaps, 'weak_evidence');
        html += '<h6 class="fw-bold small mt-3">Phát triển thêm</h6>' + recGapListHtml(gaps, 'optional_growth');
        html += '</div></div></div>';

        html += '<div class="accordion-item"><h2 class="accordion-header">';
        html += '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recImprove">Cách cải thiện CV</button></h2>';
        html += '<div id="recImprove" class="accordion-collapse collapse" data-bs-parent="#recDetailAccordion"><div class="accordion-body">';
        html += '<h6 class="fw-bold small">Việc nên làm tiếp</h6>' + recListHtml(job.next_best_actions, 'Chưa có gợi ý.');
        html += '<h6 class="fw-bold small mt-3">Gợi ý chi tiết</h6>' + recListHtml(job.cv_improvement_suggestions, 'Chưa có gợi ý chi tiết.');
        html += '</div></div></div>';

        html += '<div class="accordion-item"><h2 class="accordion-header">';
        html += '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recEvidence">Bằng chứng kỹ năng</button></h2>';
        html += '<div id="recEvidence" class="accordion-collapse collapse" data-bs-parent="#recDetailAccordion"><div class="accordion-body">';
        html += recListHtml(job.matched_must_have_skills, 'Chưa có kỹ năng khớp rõ.');
        html += '<h6 class="fw-bold small mt-3">Evidence highlights</h6>' + recListHtml(evidence, 'Chưa có highlight.');
        html += '</div></div></div>';

        var jq = job.job_quality || {};
        if (jq.quality_label) {
            html += '<div class="accordion-item"><h2 class="accordion-header">';
            html += '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recJdQuality">Chất lượng JD</button></h2>';
            html += '<div id="recJdQuality" class="accordion-collapse collapse" data-bs-parent="#recDetailAccordion"><div class="accordion-body">';
            html += '<p class="small mb-2"><strong>Điểm chất lượng:</strong> ' + recEscape(jq.quality_score != null ? jq.quality_score : '—') + '</p>';
            html += '<p class="small mb-2"><strong>Nhãn:</strong> ' + recEscape(jq.quality_label || '—') + '</p>';
            if (Array.isArray(jq.reasons) && jq.reasons.length > 0) {
                html += '<h6 class="fw-bold small">Lý do</h6>' + recListHtml(jq.reasons, '');
            }
            html += '</div></div></div>';
        }
        html += '</div>';

        document.getElementById('recDetailBody').innerHTML = html;
        var modal = new bootstrap.Modal(document.getElementById('recDetailModal'));
        modal.show();
    });
});
</script>

<?php include '../includes/footer.php'; ?>
