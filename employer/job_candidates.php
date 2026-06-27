<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/job_rules.php';
require_once __DIR__ . '/../includes/employer_screening_rules.php';
require_once __DIR__ . '/../includes/ai_diagnostics_presenter.php';
require_once __DIR__ . '/../includes/ai_i18n.php';
require_once __DIR__ . '/../includes/schema_applications_cv.php';
require_once __DIR__ . '/../includes/schema_ai_screening.php';
require_once __DIR__ . '/../includes/ai_screening_config.php';
require_once __DIR__ . '/../includes/services/ApplicationService.php';
require_once __DIR__ . '/../includes/repositories/AiScreeningRepository.php';
include 'auth_check.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$jobId = isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0;

$stmt = $conn->prepare('SELECT id, name FROM companies WHERE user_id = ? LIMIT 1');
$stmt->execute([$userId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    header('Location: company.php');
    exit();
}

$companyId = (int) $company['id'];
$schemaReady = applications_cv_columns_ready($conn);

if (!$schemaReady) {
    include '../includes/header.php';
    echo '<div class="container py-5">' . applications_cv_migration_hint_html() . '</div>';
    include '../includes/footer.php';
    exit();
}

if ($jobId <= 0) {
    $_SESSION['swal_icon'] = 'warning';
    $_SESSION['swal_title'] = 'Thiếu mã tin tuyển dụng.';
    header('Location: candidate_screening.php');
    exit();
}

$job = ApplicationService::getJobOwnedByCompany($conn, $jobId, $companyId);
if ($job === null) {
    http_response_code(404);
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Không tìm thấy tin tuyển dụng hoặc bạn không có quyền xem.';
    header('Location: candidate_screening.php');
    exit();
}

$redirectUrl = 'job_candidates.php?job_id=' . $jobId;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'])) {
    if (!csrf_validate('employer_job_candidate_status_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: ' . $redirectUrl);
        exit();
    }

    $result = ApplicationService::updateApplicationStatusForCompany(
        $conn,
        (int) $_POST['app_id'],
        $companyId,
        (string) ($_POST['status'] ?? '')
    );
    $_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
    $_SESSION['swal_title'] = $result['message'];
    header('Location: ' . $redirectUrl);
    exit();
}

$apps = ApplicationService::listApplicationsForJob($conn, $jobId, $companyId);

$aiResultsMap = [];
$aiLatestRunId = null;
$aiSchemaReady = ai_screening_results_ready($conn);
$aiConfigReady = ai_screening_config_ready();
$cvTextReady = applications_cv_snapshot_text_ready($conn);
$appsWithCvText = 0;
$screeningDiag = employer_screening_get_diag($jobId);
$diagTraceId = is_array($screeningDiag) ? trim((string) ($screeningDiag['trace_id'] ?? '')) : '';
$diagRunId = is_array($screeningDiag) ? trim((string) ($screeningDiag['run_id'] ?? '')) : '';
$screeningJobApi = employer_screening_job_api_from_diag($screeningDiag);
$diagRoot = (is_array($screeningDiag) && is_array($screeningDiag['diagnostics'] ?? null))
    ? $screeningDiag['diagnostics']
    : [];
$diagPayload = is_array($diagRoot['payload'] ?? null) ? $diagRoot['payload'] : [];
$diagRuntime = is_array($diagRoot['runtime'] ?? null) ? $diagRoot['runtime'] : [];
$diagJob = is_array($diagPayload['job'] ?? null) ? $diagPayload['job'] : [];
$diagCandidates = is_array($diagPayload['candidates'] ?? null) ? $diagPayload['candidates'] : [];
$diagJobFlags = is_array($diagJob['flags'] ?? null) ? $diagJob['flags'] : [];
$diagJobReasons = is_array($diagJob['warnings'] ?? null) ? $diagJob['warnings'] : [];
$diagCandidateFlaggedCount = (int) ($diagCandidates['flagged_count'] ?? 0);
$diagJobQuality = is_array($diagRuntime['job_quality'] ?? null) ? $diagRuntime['job_quality'] : [];
$diagScreeningConfidence = is_array($diagRuntime['screening_confidence'] ?? null) ? $diagRuntime['screening_confidence'] : [];

if ($aiSchemaReady && $cvTextReady) {
    $aiResultsMap = AiScreeningRepository::mapByApplicationForJob($conn, $jobId);
    $aiLatestRunId = AiScreeningRepository::latestRunIdForJob($conn, $jobId);
    foreach (ApplicationService::listApplicationsForAiScreening($conn, $jobId, $companyId) as $aiApp) {
        if (trim((string) ($aiApp['cv_snapshot_text'] ?? '')) !== '') {
            $appsWithCvText++;
        }
    }
}

if ($aiResultsMap !== []) {
    $apps = employer_screening_sort_apps_by_ai_rank($apps, $aiResultsMap);
}

$aiDiagDebugEnabled = ai_diag_debug_enabled();
$employerDiagBanner = ai_diag_recruiter_banner($screeningJobApi, [
    'screening_confidence' => $diagScreeningConfidence,
    'candidate_flagged_count' => $diagCandidateFlaggedCount,
    'has_screening_run' => $diagTraceId !== '' || $aiResultsMap !== [],
]);
$employerDiagDebug = [
    'trace_id' => $diagTraceId,
    'run_id' => $diagRunId,
    'reason_codes' => $employerDiagBanner['confidence']['reason_codes'] ?? [],
    'runtime_json' => $diagRoot !== [] ? json_encode($diagRoot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '',
];

$canRunAi = $aiSchemaReady && $aiConfigReady && $cvTextReady && $apps !== [] && $appsWithCvText > 0;
$aiPanelHint = '';
if (!$aiSchemaReady) {
    $aiPanelHint = 'Chưa có bảng kết quả AI — chạy migration EMP-B.';
} elseif (!$cvTextReady) {
    $aiPanelHint = 'Chưa có cột cv_snapshot_text — chạy migration text CV.';
} elseif (!$aiConfigReady) {
    $aiPanelHint = ai_screening_config_status_message();
} elseif ($apps === []) {
    $aiPanelHint = 'Chưa có ứng viên để xếp hạng.';
} elseif ($appsWithCvText === 0) {
    $aiPanelHint = 'Không có hồ sơ nào có CV text — cần apply mới bằng CV online.';
}

$jobExpired = job_is_expired($job['deadline'] ?? null);
$jobTitle = (string) ($job['title'] ?? '');

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-2">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Bảng tin</a></li>
                    <li class="breadcrumb-item"><a href="candidate_screening.php" class="text-decoration-none">Sàng lọc ứng viên</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($jobTitle) ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold text-success mb-1">
                <i class="fas fa-users"></i> Ứng viên — <?= htmlspecialchars($jobTitle) ?>
            </h3>
            <p class="text-muted mb-0 small">
                Hạn nộp: <strong><?= employer_screening_format_deadline($job['deadline'] ?? null) ?></strong>
                · <?= count($apps) ?> hồ sơ
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="candidate_screening.php" class="btn btn-outline-success btn-sm">
                <i class="fas fa-arrow-left"></i> Quay lại hub
            </a>
            <a href="applicants.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-inbox"></i> Hộp thư CV (tất cả)
            </a>
        </div>
    </div>

    <?php if ($jobExpired): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <strong><i class="fas fa-clock"></i> Tin đã hết hạn nộp hồ sơ</strong>
            (<?= employer_screening_format_deadline($job['deadline'] ?? null) ?>).
            Bạn vẫn có thể xem và cập nhật trạng thái ứng viên đã nộp.
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="fw-bold mb-1"><i class="fas fa-robot text-primary"></i> AI gợi ý xếp hạng ứng viên</h5>
                    <p class="text-muted small mb-0">
                        Phân tích CV text theo JD và gợi ý thứ tự ưu tiên.
                        <?php if ($aiResultsMap !== []): ?>
                            <span class="badge bg-light text-dark border ms-1">Đã có kết quả AI</span>
                        <?php endif; ?>
                        <?php if ($aiDiagDebugEnabled && $aiLatestRunId !== null): ?>
                            <span class="badge bg-light text-dark border ms-1">Run: <?= htmlspecialchars($aiLatestRunId) ?></span>
                        <?php endif; ?>
                        <?php if ($aiDiagDebugEnabled && $diagTraceId !== ''): ?>
                            <span class="badge bg-light text-dark border ms-1">Trace: <?= htmlspecialchars($diagTraceId) ?></span>
                        <?php endif; ?>
                    </p>
                    <?php if ($aiPanelHint !== ''): ?>
                        <p class="small text-warning mb-0 mt-2"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($aiPanelHint) ?></p>
                    <?php endif; ?>
                </div>
                <form method="POST" action="run_ai_screening.php" class="mb-0">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('employer_run_ai_screening_form')) ?>">
                    <input type="hidden" name="job_id" value="<?= (int) $jobId ?>">
                    <button type="submit" class="btn btn-primary fw-bold" <?= $canRunAi ? '' : 'disabled' ?>>
                        <i class="fas fa-magic"></i> Chạy AI gợi ý xếp hạng
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php
    include __DIR__ . '/../includes/partials/employer_screening_diagnostics.php';
    ?>

    <?php if (!$aiSchemaReady): ?>
        <?= ai_screening_migration_hint_html() ?>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <?php if ($apps !== []): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Ứng viên</th>
                                <th>Hồ sơ</th>
                                <th>Ngày nộp</th>
                                <th>Trạng thái</th>
                                <th class="text-center">AI Rank</th>
                                <th class="text-center">AI Score</th>
                                <th>Gợi ý AI</th>
                                <th class="text-end pe-4">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apps as $row): ?>
                                <?php
                                $appId = (int) ($row['app_id'] ?? 0);
                                $aiRow = $aiResultsMap[$appId] ?? null;
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars((string) $row['fullname']) ?></div>
                                        <div class="small text-muted">
                                            <i class="fas fa-envelope fa-fw"></i> <?= htmlspecialchars((string) $row['email']) ?>
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fas fa-phone fa-fw"></i> <?= htmlspecialchars((string) ($row['phone'] ?? 'Chưa cập nhật')) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php if (!empty($row['cv_snapshot_json'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary shadow-sm"
                                                    onclick="openCvSnapshotModal(<?= (int) $row['app_id'] ?>, <?= htmlspecialchars(json_encode($row['fullname'], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)">
                                                    <i class="fas fa-id-card"></i> CV online
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($row['cv_snapshot'])): ?>
                                                <a href="../<?= htmlspecialchars((string) $row['cv_snapshot']) ?>" target="_blank"
                                                    class="btn btn-sm btn-outline-danger shadow-sm">
                                                    <i class="fas fa-file-pdf"></i> File CV
                                                </a>
                                            <?php endif; ?>
                                            <?php if (empty($row['cv_snapshot_json']) && empty($row['cv_snapshot'])): ?>
                                                <span class="small text-muted">—</span>
                                            <?php endif; ?>
                                            <?php if (!empty($row['cover_letter'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-info shadow-sm"
                                                    onclick="showCoverLetter(<?= htmlspecialchars(json_encode($row['fullname']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($row['cover_letter']), ENT_QUOTES) ?>)">
                                                    <i class="fas fa-envelope-open-text"></i> Thư
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted small"><?= date('H:i d/m/Y', strtotime((string) $row['time_apply'])) ?></span>
                                    </td>
                                    <td><?= employer_application_status_badge_html((string) ($row['status'] ?? '')) ?></td>
                                    <td class="text-center">
                                        <?php if ($aiRow !== null && isset($aiRow['ai_rank'])): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">#<?= (int) $aiRow['ai_rank'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($aiRow !== null && isset($aiRow['final_score'])): ?>
                                            <strong class="text-primary"><?= (int) $aiRow['final_score'] ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= employer_ai_recommendation_badge_html(isset($aiRow['recommendation']) ? (string) $aiRow['recommendation'] : null) ?></td>
                                    <td class="text-end pe-4">
                                        <?php
                                        $reviewCard = $aiRow !== null
                                            ? employer_ai_review_card_parse((string) ($aiRow['review_card_json'] ?? ''))
                                            : null;
                                        $decisionConfidence = $aiRow !== null
                                            ? employer_screening_raw_candidate_field((string) ($aiRow['raw_result_json'] ?? ''), 'decision_confidence')
                                            : null;
                                        ?>
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <?php if ($reviewCard !== null && employer_ai_review_card_has_content($reviewCard)): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                    onclick="openAiReviewModal(<?= htmlspecialchars(json_encode([
                                                        'name' => (string) $row['fullname'],
                                                        'rank' => $aiRow['ai_rank'] ?? null,
                                                        'score' => $aiRow['final_score'] ?? null,
                                                        'recommendation' => $aiRow['recommendation'] ?? null,
                                                        'card' => employer_ai_review_card_for_ui($reviewCard),
                                                        'decision_confidence' => is_array($decisionConfidence) ? $decisionConfidence : null,
                                                        'job_api' => [
                                                            'confidence_guardrails' => $screeningJobApi['confidence_guardrails'] ?? null,
                                                            'explicit_technical_recovery_summary' => $screeningJobApi['explicit_technical_recovery_summary'] ?? null,
                                                            'promoted_requirements' => $screeningJobApi['promoted_requirements'] ?? null,
                                                            'technical_responsibility_candidates' => $screeningJobApi['technical_responsibility_candidates'] ?? null,
                                                            'open_set_requirements' => $screeningJobApi['open_set_requirements'] ?? null,
                                                        ],
                                                        'diagnostics' => [
                                                            'trace_id' => $diagTraceId,
                                                            'run_id' => $diagRunId,
                                                            'job_flags' => $diagJobFlags,
                                                            'job_warnings' => $diagJobReasons,
                                                            'candidate_flagged_count' => $diagCandidateFlaggedCount,
                                                            'job_quality' => $diagJobQuality,
                                                            'screening_confidence' => $diagScreeningConfidence,
                                                        ],
                                                    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)">
                                                    <i class="fas fa-robot me-1"></i> AI review
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-primary px-3 rounded-pill"
                                                data-bs-toggle="modal" data-bs-target="#statusModal"
                                                onclick="setModalData(<?= (int) $row['app_id'] ?>, '<?= htmlspecialchars((string) $row['status'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-cog me-1"></i> Xử lý
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <p class="mb-0">Chưa có ứng viên nộp hồ sơ cho tin này.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="cvSnapshotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="cvSnapshotModalTitle">CV online (snapshot)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="min-height: 400px;">
                <iframe id="cvSnapshotFrame" title="CV snapshot" class="w-100 border-0" style="min-height: 70vh;"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="aiReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header align-items-start">
                <div class="me-auto pe-3">
                    <h5 class="modal-title fw-bold mb-1" id="aiReviewModalTitle">AI review</h5>
                    <div class="small text-muted" id="aiReviewModalMeta"></div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <div class="btn-group btn-group-sm ai-lang-toggle" role="group" id="aiReviewLangToggle">
                        <button type="button" class="btn btn-outline-secondary active" data-lang="en">English</button>
                        <button type="button" class="btn btn-outline-secondary" data-lang="vi">Tiếng Việt</button>
                    </div>
                    <button type="button" class="btn-close mt-1" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body" id="aiReviewModalBody"></div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content shadow-lg border-0">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('employer_job_candidate_status_form')) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Cập nhật trạng thái ứng viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <input type="hidden" name="app_id" id="modal_app_id">
                <div class="mb-3">
                    <label class="form-label fw-bold">Trạng thái mới:</label>
                    <select name="status" id="modal_status" class="form-select form-select-lg">
                        <option value="pending">Chờ duyệt</option>
                        <option value="viewed">Đã xem</option>
                        <option value="interview">Mời phỏng vấn</option>
                        <option value="rejected">Từ chối hồ sơ</option>
                    </select>
                </div>
                <div class="alert alert-info border-0 small mb-0">
                    <i class="fas fa-info-circle me-1"></i> Hệ thống sẽ ghi nhận thay đổi này và hiển thị cho ứng viên.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-success px-4 fw-bold">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<?= ai_i18n_script_tags() ?>

<script>
    function setModalData(id, status) {
        document.getElementById('modal_app_id').value = id;
        document.getElementById('modal_status').value = status;
    }

    function showCoverLetter(name, text) {
        Swal.fire({
            title: '<h5 class="fw-bold">Thư giới thiệu từ ' + name + '</h5>',
            html: '<div class="text-start p-2 border rounded bg-light" style="font-size: 0.95rem; line-height: 1.6;">' + text.replace(/\n/g, '<br>') + '</div>',
            icon: 'info',
            confirmButtonText: 'Đóng',
            confirmButtonColor: '#0d6efd'
        });
    }

    function openAiReviewModal(payload) {
        if (window.TopCvAiUiI18n) {
            TopCvAiUiI18n.openEmployerModal(payload);
        }
    }

    function openCvSnapshotModal(appId, fullname) {
        document.getElementById('cvSnapshotModalTitle').textContent = 'CV online — ' + fullname;
        document.getElementById('cvSnapshotFrame').src = 'applicant-cv-snapshot.php?app_id=' + appId;
        var modal = new bootstrap.Modal(document.getElementById('cvSnapshotModal'));
        modal.show();
    }

    document.getElementById('cvSnapshotModal')?.addEventListener('hidden.bs.modal', function () {
        document.getElementById('cvSnapshotFrame').src = 'about:blank';
    });
</script>

<style>
    .table thead th {
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>

<?php include '../includes/footer.php'; ?>
