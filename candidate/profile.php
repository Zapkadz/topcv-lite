<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_cvs.php';
require_once __DIR__ . '/../includes/cv_rules.php';
require_once __DIR__ . '/../includes/services/CvService.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];

$stmtProfile = $conn->prepare('SELECT title, cv_path, bio FROM candidates WHERE user_id = ? LIMIT 1');
$stmtProfile->execute([$userId]);
$profile = $stmtProfile->fetch(PDO::FETCH_ASSOC);
if (!is_array($profile)) {
    $profile = ['title' => '', 'cv_path' => '', 'bio' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate('candidate_profile_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: profile.php');
        exit();
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));

    $check = $conn->prepare('SELECT id FROM candidates WHERE user_id = ? LIMIT 1');
    $check->execute([$userId]);

    if ($check->fetchColumn()) {
        $stmt = $conn->prepare('UPDATE candidates SET title = ?, bio = ? WHERE user_id = ?');
        $stmt->execute([$title, $bio, $userId]);
    } else {
        $stmt = $conn->prepare('INSERT INTO candidates (user_id, title, bio, cv_path) VALUES (?, ?, ?, NULL)');
        $stmt->execute([$userId, $title, $bio]);
    }

    $_SESSION['swal_icon'] = 'success';
    $_SESSION['swal_title'] = 'Cập nhật hồ sơ thành công!';
    header('Location: profile.php');
    exit();
}

$onlineCvs = [];
$primaryCv = null;
if (cvs_schema_ready($conn)) {
    $onlineCvs = CvService::listForUser($conn, $userId);
    foreach ($onlineCvs as $cv) {
        if ((int) ($cv['is_primary'] ?? 0) === 1) {
            $primaryCv = $cv;
            break;
        }
    }
    if ($primaryCv === null && $onlineCvs !== []) {
        $primaryCv = $onlineCvs[0];
    }
}

$legacyCvPath = trim((string) ($profile['cv_path'] ?? ''));

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if ($legacyCvPath !== ''): ?>
                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <strong><i class="fas fa-info-circle"></i> CV file cũ trên hồ sơ</strong>
                    <p class="mb-2 small">Hệ thống không dùng file PDF/DOC này khi ứng tuyển. Hãy tạo hoặc chọn <strong>CV online</strong> trong Quản lý CV.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="../<?= htmlspecialchars($legacyCvPath) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Xem file cũ</a>
                        <a href="cv-import.php" class="btn btn-sm btn-outline-success">Tạo CV từ PDF</a>
                        <a href="cv-manage.php" class="btn btn-sm btn-success">Quản lý CV online</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (cvs_schema_ready($conn)): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="fas fa-file-alt text-success"></i> CV online</h5>
                            <p class="text-muted small mb-0">Bản CV bạn chọn khi ứng tuyển — nhà tuyển dụng xem trực tiếp trên web.</p>
                        </div>
                        <a href="cv-manage.php" class="btn btn-sm btn-outline-success">Quản lý CV</a>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php if ($primaryCv === null): ?>
                        <p class="text-muted mb-3">Chưa có CV online. Tạo CV có cấu trúc để ứng tuyển và được AI đánh giá chính xác hơn.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="cv-builder.php" class="btn btn-success btn-sm fw-bold">
                                <i class="fas fa-plus"></i> Tạo CV mới
                            </a>
                            <a href="cv-import.php" class="btn btn-outline-success btn-sm fw-bold">
                                <i class="fas fa-file-upload"></i> Tạo từ PDF
                            </a>
                        </div>
                    <?php else: ?>
                        <?php
                        $primaryId = (int) ($primaryCv['id'] ?? 0);
                        $completion = (int) ($primaryCv['completion_percent'] ?? 0);
                        $isPrimary = (int) ($primaryCv['is_primary'] ?? 0) === 1;
                        ?>
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <span class="fw-bold fs-5"><?= htmlspecialchars((string) ($primaryCv['title'] ?? 'CV')) ?></span>
                                    <?php if ($isPrimary): ?>
                                        <span class="badge bg-success ms-1">Mặc định</span>
                                    <?php endif; ?>
                                    <div class="small text-muted mt-1">
                                        Cập nhật: <?= htmlspecialchars(cv_format_updated_at($primaryCv['updated_at'] ?? null)) ?>
                                    </div>
                                </div>
                                <a href="cv-preview.php?id=<?= $primaryId ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Xem trước</a>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Hoàn thiện</span>
                                    <span class="fw-bold text-success"><?= $completion ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: <?= $completion ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="cv-builder.php?id=<?= $primaryId ?>" class="btn btn-success btn-sm fw-bold">Sửa CV</a>
                            <a href="cv-manage.php" class="btn btn-outline-success btn-sm">Tất cả CV (<?= count($onlineCvs) ?>)</a>
                            <a href="cv-builder.php" class="btn btn-outline-secondary btn-sm">Tạo CV mới</a>
                        </div>
                        <?php if (count($onlineCvs) > 1): ?>
                            <ul class="list-group list-group-flush small">
                                <?php foreach (array_slice($onlineCvs, 0, 4) as $cv): ?>
                                    <?php if ((int) ($cv['id'] ?? 0) === $primaryId) {
                                        continue;
                                    } ?>
                                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                        <span><?= htmlspecialchars((string) ($cv['title'] ?? '')) ?></span>
                                        <a href="cv-builder.php?id=<?= (int) $cv['id'] ?>" class="btn btn-sm btn-link">Sửa</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-success text-white rounded-top-4 p-4">
                    <h4 class="mb-0 fw-bold"><i class="fas fa-user-edit"></i> Hồ sơ cá nhân</h4>
                    <p class="mb-0 opacity-75">Vị trí mong muốn và giới thiệu ngắn — CV chi tiết quản lý ở mục CV online phía trên.</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_profile_form')) ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Vị trí mong muốn / Chức danh</label>
                            <input type="text" name="title" class="form-control" placeholder="VD: Senior PHP Developer"
                                value="<?= htmlspecialchars((string) ($profile['title'] ?? '')) ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Giới thiệu bản thân (Bio)</label>
                            <textarea name="bio" class="form-control" rows="5" placeholder="Kinh nghiệm, kỹ năng nổi bật..."><?= htmlspecialchars((string) ($profile['bio'] ?? '')) ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold py-2">Lưu hồ sơ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
