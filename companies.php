<?php
// File: companies.php
session_start();
include 'config/db.php';
include 'includes/header.php';

// TÌM KIẾM
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

// PHÂN TRANG
$limit = 8; // 8 công ty mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// QUERY
$sql_base = "FROM companies WHERE name LIKE ?";
$params = ["%$keyword%"];

// Đếm tổng
$stmt_count = $conn->prepare("SELECT COUNT(*) " . $sql_base);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Lấy dữ liệu
$sql = "SELECT * " . $sql_base . " ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$companies = $stmt->fetchAll();
?>

<div class="bg-light py-5">
    <div class="container">
        <h3 class="fw-bold text-center mb-4 text-success">Danh sách Công ty hàng đầu</h3>
        
        <div class="row justify-content-center mb-5">
            <div class="col-md-6">
                <form action="" method="GET" class="input-group shadow-sm">
                    <input type="text" name="q" class="form-control border-0 py-3 ps-4" placeholder="Nhập tên công ty..." value="<?= htmlspecialchars($keyword) ?>">
                    <button class="btn btn-success fw-bold px-4" type="submit"><i class="fas fa-search"></i> Tìm kiếm</button>
                </form>
            </div>
        </div>

        <div class="row">
            <?php if(count($companies) > 0): ?>
                <?php foreach($companies as $comp): ?>
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm h-100 hover-shadow transition">
                        <div class="card-body text-center d-flex flex-column">
                            <div class="mb-3 mx-auto" style="width: 80px; height: 80px;">
                                <img src="<?= !empty($comp['logo']) ? $comp['logo'] : 'uploads/default-logo.png' ?>" class="w-100 h-100 object-fit-contain rounded border p-1">
                            </div>
                            <h6 class="fw-bold text-truncate mb-2"><?= htmlspecialchars($comp['name']) ?></h6>
                            <p class="small text-muted mb-3 flex-grow-1 text-truncate-2">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($comp['address']) ?>
                            </p>
                            <a href="company-detail.php?id=<?= $comp['id'] ?>" class="btn btn-outline-success btn-sm w-100 mt-auto">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Không tìm thấy công ty nào phù hợp.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link text-success <?= ($page == $i) ? 'bg-success border-success text-white' : '' ?>" 
                           href="?q=<?= $keyword ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<style>
    .hover-shadow:hover { transform: translateY(-5px); transition: 0.3s; box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    .text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<?php include 'includes/footer.php'; ?>