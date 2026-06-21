<?php
/** @var array<string, mixed> $sessionResult */
/** @var list<int> $appliedJobIds */

$topJobs = is_array($sessionResult['top_jobs'] ?? null) ? $sessionResult['top_jobs'] : [];
$excludedJobs = is_array($sessionResult['excluded_jobs'] ?? null) ? $sessionResult['excluded_jobs'] : [];
$qualityStats = is_array($sessionResult['job_quality_stats'] ?? null) ? $sessionResult['job_quality_stats'] : [];
$warnings = is_array($sessionResult['warnings'] ?? null) ? $sessionResult['warnings'] : [];
$diagnostics = is_array($sessionResult['diagnostics'] ?? null) ? $sessionResult['diagnostics'] : [];
$diagPayload = is_array($diagnostics['payload'] ?? null) ? $diagnostics['payload'] : [];
$diagRuntime = is_array($diagnostics['runtime'] ?? null) ? $diagnostics['runtime'] : [];
$diagCandidate = is_array($diagPayload['candidate'] ?? null) ? $diagPayload['candidate'] : [];
$diagJobs = is_array($diagPayload['jobs'] ?? null) ? $diagPayload['jobs'] : [];
$candidateFlags = is_array($diagCandidate['flags'] ?? null) ? $diagCandidate['flags'] : [];
$jobsWarnings = is_array($diagJobs['warnings'] ?? null) ? $diagJobs['warnings'] : [];
$flaggedJobsCount = (int) ($diagJobs['flagged_count'] ?? 0);
$traceId = trim((string) ($sessionResult['trace_id'] ?? ''));
$appliedSet = array_flip($appliedJobIds);

$jobsAnalyzed = (int) ($qualityStats['jobs_received'] ?? $sessionResult['jobs_received'] ?? count($topJobs) + count($excludedJobs));
$eligibleCount = (int) ($qualityStats['eligible_jobs'] ?? count($topJobs));
$excludedCount = (int) ($qualityStats['excluded_jobs'] ?? count($excludedJobs));
$cvWeak = array_intersect(
    array_map('strval', $candidateFlags),
    ['cv_text_too_short', 'candidate_profile_sparse', 'html_cleaning_changed_text_heavily']
) !== [];
?>

<div class="alert alert-info border-0 small mb-4">
    <i class="fas fa-info-circle me-1"></i>
    Kết quả xếp hạng dựa trên mức độ phù hợp giữa CV và mô tả công việc.
    Chưa tính ưu tiên cá nhân như mức lương, địa điểm, hình thức làm việc.
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="border rounded-3 p-3 bg-white h-100">
            <div class="small text-muted">Tin đã phân tích</div>
            <div class="fs-4 fw-bold text-success"><?= $jobsAnalyzed ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded-3 p-3 bg-white h-100">
            <div class="small text-muted">Đủ dữ liệu JD</div>
            <div class="fs-4 fw-bold text-primary"><?= $eligibleCount ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded-3 p-3 bg-white h-100">
            <div class="small text-muted">Bị loại</div>
            <div class="fs-4 fw-bold text-secondary"><?= $excludedCount ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded-3 p-3 bg-white h-100">
            <div class="small text-muted">JD bị gắn cờ</div>
            <div class="fs-4 fw-bold text-warning"><?= $flaggedJobsCount ?></div>
        </div>
    </div>
</div>

<?php if ($cvWeak): ?>
    <div class="alert alert-warning border-0 small mb-3">
        <i class="fas fa-triangle-exclamation me-1"></i>
        CV hiện tại có thể chưa đủ thông tin để AI đánh giá tối ưu.
    </div>
<?php endif; ?>

<?php if ($jobsWarnings !== []): ?>
    <div class="alert alert-warning border-0 small mb-3">
        <i class="fas fa-info-circle me-1"></i>
        <?= htmlspecialchars((string) $jobsWarnings[0]) ?>
    </div>
<?php endif; ?>

<?php if ($topJobs !== [] && $excludedCount > 0): ?>
    <div class="alert alert-warning border-0 small mb-4">
        <i class="fas fa-exclamation-triangle me-1"></i>
        Một số tin đã bị loại khỏi AI recommendation vì dữ liệu JD chưa đủ mạnh.
    </div>
<?php endif; ?>

<?php if ($traceId !== '' || $diagnostics !== []): ?>
<details class="mb-4">
    <summary class="small fw-bold text-muted">AI diagnostics</summary>
    <div class="small text-muted mt-2 border rounded-3 p-3 bg-light">
        <?php if ($traceId !== ''): ?>
            <div><strong>Trace ID:</strong> <code><?= htmlspecialchars($traceId) ?></code></div>
        <?php endif; ?>
        <?php if ($candidateFlags !== []): ?>
            <div><strong>Candidate payload flags:</strong> <?= htmlspecialchars(implode(', ', array_map('strval', $candidateFlags))) ?></div>
        <?php endif; ?>
        <div><strong>Job payload flagged count:</strong> <?= $flaggedJobsCount ?></div>
        <div><strong>Eligible / Excluded:</strong> <?= $eligibleCount ?> / <?= $excludedCount ?></div>
        <?php
        $topIds = [];
        foreach ($topJobs as $r) {
            if (is_array($r)) {
                $topIds[] = (string) ($r['job_id'] ?? '?');
            }
        }
        $excludedIds = [];
        foreach ($excludedJobs as $r) {
            if (is_array($r)) {
                $excludedIds[] = (string) ($r['job_id'] ?? '?');
            }
        }
        ?>
        <div><strong>Top job IDs:</strong> <?= htmlspecialchars($topIds !== [] ? implode(', ', $topIds) : 'none') ?></div>
        <div><strong>Excluded job IDs:</strong> <?= htmlspecialchars($excludedIds !== [] ? implode(', ', $excludedIds) : 'none') ?></div>
        <?php if (is_array($diagRuntime) && $diagRuntime !== []): ?>
            <div><strong>Runtime:</strong> <?= htmlspecialchars(json_encode($diagRuntime, JSON_UNESCAPED_UNICODE)) ?></div>
        <?php endif; ?>
    </div>
</details>
<?php endif; ?>

<?php if ($topJobs === []): ?>
    <div class="alert alert-warning border-0 mb-4">
        <div class="fw-bold mb-1">Hiện chưa có tin tuyển dụng đủ dữ liệu để AI gợi ý chính xác.</div>
        <?php if ($excludedCount > 0): ?>
            <p class="small mb-0">
                Một số tin đã bị loại vì mô tả tuyển dụng quá ngắn hoặc thiếu yêu cầu công việc.
            </p>
        <?php endif; ?>
    </div>
<?php else: ?>

<div class="d-none d-md-block card border-0 shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4" style="width: 4rem;">#</th>
                    <th>Việc làm</th>
                    <th style="width: 11rem;">Độ phù hợp</th>
                    <th>Thiếu hụt</th>
                    <th class="text-end pe-4" style="width: 12rem;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topJobs as $row): ?>
                    <?php
                    if (!is_array($row)) {
                        continue;
                    }
                    $jobId = (int) ($row['job_id'] ?? 0);
                    $rank = (int) ($row['rank'] ?? 0);
                    $hasApplied = isset($appliedSet[$jobId]);
                    $jobQuality = is_array($row['job_quality'] ?? null) ? $row['job_quality'] : null;
                    $payloadJson = htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                    ?>
                    <tr>
                        <td class="ps-4"><span class="badge bg-light text-dark border">#<?= $rank > 0 ? $rank : '—' ?></span></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars((string) ($row['job_title'] ?? '')) ?></div>
                            <div class="small text-muted">
                                <?= htmlspecialchars((string) ($row['company_name'] ?? '')) ?>
                                <?php if (!empty($row['city'])): ?>
                                    · <?= htmlspecialchars((string) $row['city']) ?>
                                <?php endif; ?>
                            </div>
                            <?php if ($hasApplied): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle mt-1">Đã ứng tuyển</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= job_recommendation_fit_label_badge_html(isset($row['fit_label']) ? (string) $row['fit_label'] : null) ?>
                            <?php if (isset($row['fit_score'])): ?>
                                <div class="small fw-bold text-primary mt-1"><?= (int) $row['fit_score'] ?> điểm</div>
                            <?php endif; ?>
                            <?= job_recommendation_jd_quality_warning_badge_html($jobQuality) ?>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars(job_recommendation_gap_counts_line($row)) ?></td>
                        <td class="text-end pe-4 text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary js-rec-detail" data-job="<?= $payloadJson ?>">
                                Chi tiết AI
                            </button>
                            <a href="../job-detail.php?id=<?= $jobId ?>" class="btn btn-sm btn-success">Xem tin</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-md-none mb-4">
    <?php foreach ($topJobs as $row): ?>
        <?php
        if (!is_array($row)) {
            continue;
        }
        $jobId = (int) ($row['job_id'] ?? 0);
        $rank = (int) ($row['rank'] ?? 0);
        $hasApplied = isset($appliedSet[$jobId]);
        $jobQuality = is_array($row['job_quality'] ?? null) ? $row['job_quality'] : null;
        $payloadJson = htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <span class="badge bg-light text-dark border">#<?= $rank > 0 ? $rank : '—' ?></span>
                    <div class="text-end">
                        <?= job_recommendation_fit_label_badge_html(isset($row['fit_label']) ? (string) $row['fit_label'] : null) ?>
                        <?= job_recommendation_jd_quality_warning_badge_html($jobQuality) ?>
                    </div>
                </div>
                <h6 class="fw-bold mb-1"><?= htmlspecialchars((string) ($row['job_title'] ?? '')) ?></h6>
                <p class="small text-muted mb-2">
                    <?= htmlspecialchars((string) ($row['company_name'] ?? '')) ?>
                    <?php if (!empty($row['city'])): ?>
                        · <?= htmlspecialchars((string) $row['city']) ?>
                    <?php endif; ?>
                </p>
                <?php if (isset($row['fit_score'])): ?>
                    <div class="small fw-bold text-primary mb-2"><?= (int) $row['fit_score'] ?> điểm phù hợp</div>
                <?php endif; ?>
                <p class="small text-muted mb-3"><?= htmlspecialchars(job_recommendation_gap_counts_line($row)) ?></p>
                <?php if ($hasApplied): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle mb-2">Đã ứng tuyển</span>
                <?php endif; ?>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm js-rec-detail" data-job="<?= $payloadJson ?>">
                        Chi tiết AI
                    </button>
                    <a href="../job-detail.php?id=<?= $jobId ?>" class="btn btn-success btn-sm">Xem tin</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php if ($excludedJobs !== []): ?>
<div class="accordion mb-4" id="recExcludedAccordion">
    <div class="accordion-item border shadow-sm">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recExcludedPanel" aria-expanded="false">
                Tin không đủ dữ liệu để AI đánh giá (<?= count($excludedJobs) ?>)
            </button>
        </h2>
        <div id="recExcludedPanel" class="accordion-collapse collapse" data-bs-parent="#recExcludedAccordion">
            <div class="accordion-body p-0">
                <div class="d-none d-md-block">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Việc làm</th>
                                <th style="width: 10rem;">Trạng thái JD</th>
                                <th>Lý do</th>
                                <th class="text-end pe-4" style="width: 7rem;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($excludedJobs as $row): ?>
                                <?php
                                if (!is_array($row)) {
                                    continue;
                                }
                                $jobId = (int) ($row['job_id'] ?? 0);
                                $quality = is_array($row['job_quality'] ?? null) ? $row['job_quality'] : [];
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= htmlspecialchars((string) ($row['job_title'] ?? '')) ?></div>
                                        <div class="small text-muted">
                                            <?= htmlspecialchars((string) ($row['company_name'] ?? '')) ?>
                                            <?php if (!empty($row['city'])): ?>
                                                · <?= htmlspecialchars((string) $row['city']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars(job_recommendation_quality_label_vi((string) ($quality['quality_label'] ?? ''))) ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars(job_recommendation_excluded_reasons_line($row)) ?></td>
                                    <td class="text-end pe-4">
                                        <a href="../job-detail.php?id=<?= $jobId ?>" class="btn btn-sm btn-outline-secondary">Xem tin</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-md-none p-3">
                    <?php foreach ($excludedJobs as $row): ?>
                        <?php
                        if (!is_array($row)) {
                            continue;
                        }
                        $jobId = (int) ($row['job_id'] ?? 0);
                        $quality = is_array($row['job_quality'] ?? null) ? $row['job_quality'] : [];
                        ?>
                        <div class="border rounded-3 p-3 mb-2 bg-light">
                            <div class="fw-bold mb-1"><?= htmlspecialchars((string) ($row['job_title'] ?? '')) ?></div>
                            <div class="small text-muted mb-2">
                                <?= htmlspecialchars((string) ($row['company_name'] ?? '')) ?>
                                <?php if (!empty($row['city'])): ?>
                                    · <?= htmlspecialchars((string) $row['city']) ?>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-light text-dark border mb-2">
                                <?= htmlspecialchars(job_recommendation_quality_label_vi((string) ($quality['quality_label'] ?? ''))) ?>
                            </span>
                            <p class="small text-muted mb-2"><?= htmlspecialchars(job_recommendation_excluded_reasons_line($row)) ?></p>
                            <a href="../job-detail.php?id=<?= $jobId ?>" class="btn btn-sm btn-outline-secondary">Xem tin</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
