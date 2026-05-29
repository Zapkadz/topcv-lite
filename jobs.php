<?php
// File: jobs.php
session_start();
include 'config/db.php';
include 'includes/header.php';

// LẤY DỮ LIỆU BỘ LỌC
$cats = $conn->query("SELECT * FROM categories")->fetchAll();
$locs = $conn->query("SELECT * FROM locations")->fetchAll();

// XỬ LÝ TÌM KIẾM
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat_id = isset($_GET['cat']) ? $_GET['cat'] : '';
$loc_id = isset($_GET['loc']) ? $_GET['loc'] : '';

// QUERY CƠ BẢN
$where = "WHERE j.status = 'approved' AND j.deadline >= CURDATE()";
$params = [];

if ($keyword) {
    $where .= " AND j.title LIKE ?";
    $params[] = "%$keyword%";
}
if ($cat_id) {
    $where .= " AND j.category_id = ?";
    $params[] = $cat_id;
}
if ($loc_id) {
    $where .= " AND j.location_id = ?";
    $params[] = $loc_id;
}

// PHÂN TRANG
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Đếm tổng
$stmt_count = $conn->prepare("SELECT COUNT(*) FROM jobs j $where");
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Lấy job
$sql = "SELECT j.*, c.name as company_name, c.logo, l.name as city 
        FROM jobs j 
        JOIN companies c ON j.company_id = c.id 
        JOIN locations l ON j.location_id = l.id 
        $where 
        ORDER BY j.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3 text-success">
                    <i class="fas fa-filter me-2"></i> Lọc việc làm
                </div>
                <div class="card-body">
                    <form action="" method="GET">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Từ khóa</label>
                            <input type="text" name="q" class="form-control form-control-sm" placeholder="Tìm tên việc làm..." value="<?= htmlspecialchars($keyword) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Ngành nghề</label>
                            <select name="cat" class="form-select form-select-sm">
                                <option value="">Tất cả ngành nghề</option>
                                <?php foreach($cats as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $cat_id == $c['id'] ? 'selected' : '' ?>><?= $c['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Địa điểm</label>
                            <select name="loc" class="form-select form-select-sm">
                                <option value="">Tất cả địa điểm</option>
                                <?php foreach($locs as $l): ?>
                                    <option value="<?= $l['id'] ?>" <?= $loc_id == $l['id'] ? 'selected' : '' ?>><?= $l['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">Áp dụng lọc</button>
                        <a href="jobs.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">Xóa bộ lọc</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <h4 class="fw-bold mb-3 text-success">
                <?= $keyword ? 'Kết quả tìm kiếm: "' . htmlspecialchars($keyword) . '"' : 'Việc làm mới nhất' ?>
                <small class="text-muted fs-6 ms-2">(<?= $total_records ?> tin)</small>
            </h4>

            <?php if(count($jobs) > 0): ?>
                <?php foreach($jobs as $job): ?>
                <div class="card border border-light shadow-sm mb-3 hover-shadow transition">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="me-3" style="width: 80px; height: 80px;">
                                <a href="company-detail.php?id=<?= $job['company_id'] ?>">
                                    <img src="<?= !empty($job['logo']) ? $job['logo'] : 'uploads/default-logo.png' ?>" class="w-100 h-100 object-fit-contain border rounded p-1">
                                </a>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-1">
                                    <a href="job-detail.php?id=<?= $job['id'] ?>" class="text-dark text-decoration-none">
                                        <?= htmlspecialchars($job['title']) ?>
                                    </a>
                                </h5>
                                <div class="mb-1">
                                    <a href="company-detail.php?id=<?= $job['company_id'] ?>" class="text-secondary small text-decoration-none fw-bold">
                                        <?= htmlspecialchars($job['company_name']) ?>
                                    </a>
                                </div>
                                <div class="d-flex flex-wrap gap-3 small text-muted">
                                    <span class="text-success fw-bold"><i class="fas fa-dollar-sign"></i> <?= htmlspecialchars($job['salary_range']) ?></span>
                                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['city']) ?></span>
                                    <span><i class="fas fa-clock"></i> <?= date('d/m/Y', strtotime($job['deadline'])) ?></span>
                                </div>
                            </div>
                            <div class="d-none d-md-block">
                                <a href="job-detail.php?id=<?= $job['id'] ?>" class="btn btn-outline-success btn-sm">Ứng tuyển</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($total_pages > 1): ?>
                    <?php 
                        // Giữ lại tham số tìm kiếm khi chuyển trang
                        $query_params = $_GET; 
                    ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php $query_params['page'] = $i; ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link text-success <?= ($page == $i) ? 'bg-success border-success text-white' : '' ?>" 
                                       href="?<?= http_build_query($query_params) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-light text-center border py-5">
                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486754.png" width="60" class="opacity-50 mb-3">
                    <p class="mb-0 text-muted">Chưa tìm thấy công việc phù hợp với tiêu chí của bạn.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .hover-shadow:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important; border-color: #198754 !important; }
    .transition { transition: all 0.2s; }
</style>

<?php include 'includes/footer.php'; ?>