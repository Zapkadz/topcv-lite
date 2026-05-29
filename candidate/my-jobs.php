<?php
// File: candidate/my-jobs.php
include '../includes/header.php';

// Kiểm tra quyền: Chỉ ứng viên mới được vào
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'candidate') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- CẤU HÌNH PHÂN TRANG ---
$limit = 10; // Số lượng bản ghi trên một trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// 1. ĐẾM TỔNG SỐ BẢN GHI (Để tính số trang)
// Cần JOIN với bảng candidates để tìm đúng đơn của user này
$sql_count = "SELECT COUNT(*) 
              FROM applications app
              JOIN candidates cand ON app.candidate_id = cand.id
              WHERE cand.user_id = ?";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute([$user_id]);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 2. LẤY DỮ LIỆU CHO TRANG HIỆN TẠI
$sql = "SELECT app.*, j.title, c.name as company_name, j.id as job_id
        FROM applications app
        JOIN jobs j ON app.job_id = j.id
        JOIN companies c ON j.company_id = c.id
        JOIN candidates cand ON app.candidate_id = cand.id
        WHERE cand.user_id = ? 
        ORDER BY app.created_at DESC
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$my_jobs = $stmt->fetchAll();
?>

<div class="container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="fas fa-file-contract text-success"></i> Lịch sử ứng tuyển</h5>
            <span class="badge bg-light text-dark border">Tổng: <?= $total_records ?> đơn</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Công việc</th>
                            <th>Thời gian nộp</th>
                            <th>CV đã nộp</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_jobs as $row): ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="../job-detail.php?id=<?= $row['job_id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($row['title']) ?>
                                    </a>
                                </strong><br>
                                <span class="text-muted small"><?= htmlspecialchars($row['company_name']) ?></span>
                            </td>
                            <td><?= date('H:i - d/m/Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <a href="../<?= $row['cv_snapshot'] ?>" target="_blank" class="text-danger fw-bold text-decoration-none">
                                    <i class="fas fa-file-pdf"></i> Xem CV
                                </a>
                            </td>
                            <td>
                                <?php 
                                    if($row['status'] == 'pending') echo '<span class="badge bg-warning text-dark">Chờ duyệt</span>';
                                    elseif($row['status'] == 'viewed') echo '<span class="badge bg-info">NTD đã xem</span>';
                                    elseif($row['status'] == 'interview') echo '<span class="badge bg-success">Mời phỏng vấn</span>';
                                    elseif($row['status'] == 'rejected') echo '<span class="badge bg-secondary">Từ chối</span>';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if(count($my_jobs) == 0): ?>
                <div class="text-center py-5">
                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486754.png" width="80" class="opacity-50 mb-3">
                    <p class="text-muted">Bạn chưa ứng tuyển công việc nào.</p>
                    <a href="../index.php" class="btn btn-success">Tìm việc ngay</a>
                </div>
            <?php endif; ?>

            <?php if($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">Trước</a>
                    </li>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">Sau</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>