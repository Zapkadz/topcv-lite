<?php
// --- PHẦN 1: LOGIC PHP ---
session_start();
include 'config/db.php';
require_once __DIR__ . '/includes/job_rules.php';
include 'includes/header.php';

// 1. DATA TỪ DATABASE
$locations = $conn->query("SELECT * FROM locations")->fetchAll();
$categories = $conn->query("SELECT * FROM categories")->fetchAll();

// 2. DATA CỐ ĐỊNH
$salary_ranges = ['Dưới 10 triệu', '10 - 15 triệu', '15 - 20 triệu', '20 - 30 triệu', '30 - 50 triệu', 'Trên 50 triệu', 'Thỏa thuận'];
$experiences = ['Không yêu cầu', 'Dưới 1 năm', '1 năm', '2 năm', '3 năm', '4 năm', '5 năm', 'Trên 5 năm'];

// 3. THỐNG KÊ
$jobActive = job_sql_not_deleted('jobs');
$sql_count_active = "SELECT COUNT(*) FROM jobs WHERE status = 'approved' AND deadline >= CURDATE() AND {$jobActive}";
$count_active = $conn->query($sql_count_active)->fetchColumn();
$sql_count_new = "SELECT COUNT(*) FROM jobs WHERE DATE(created_at) = CURDATE() AND status = 'approved' AND {$jobActive}";
$count_new = $conn->query($sql_count_new)->fetchColumn();

// 4. XỬ LÝ TÌM KIẾM & PHÂN TRANG
$limit = 9; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Query Builder
$where = 'WHERE j.status = \'approved\' AND j.deadline >= CURDATE() AND ' . job_sql_not_deleted('j');
$params = [];

// --- SỬA LỖI CÚ PHÁP TẠI ĐÂY (Dùng ngoặc nhọn {} chuẩn) ---
if (!empty($_GET['q'])) {
    $where .= " AND (j.title LIKE ? OR c.name LIKE ?)";
    $params[] = "%" . $_GET['q'] . "%";
    $params[] = "%" . $_GET['q'] . "%";
}

if (!empty($_GET['location_id'])) {
    $where .= " AND j.location_id = ?";
    $params[] = $_GET['location_id'];
}

if (!empty($_GET['category_id'])) {
    $where .= " AND j.category_id = ?";
    $params[] = $_GET['category_id'];
}

if (!empty($_GET['salary_range'])) {
    $where .= " AND j.salary_range LIKE ?";
    $params[] = "%" . $_GET['salary_range'] . "%";
}

if (!empty($_GET['experience'])) {
    $where .= " AND j.experience = ?";
    $params[] = $_GET['experience'];
}
// -----------------------------------------------------------

// Đếm tổng & Lấy Job
$sql_count = "SELECT COUNT(*) FROM jobs j JOIN companies c ON j.company_id = c.id $where";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

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

<style>
    /* CSS SMART FILTER */
    .smart-filter-container {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        padding: 12px 20px;
        margin-top: -30px;
        position: relative;
        z-index: 10;
        display: flex;
        align-items: center;
    }

    .filter-left-control {
        display: flex;
        align-items: center;
        border-right: 2px solid #f0f0f0;
        padding-right: 20px;
        margin-right: 15px;
        min-width: 240px;
    }
    
    .filter-icon { color: #00b14f; font-size: 1.1rem; margin-right: 8px; }
    .filter-label-text { font-weight: 500; color: #666; margin-right: 5px; white-space: nowrap; }

    .custom-select-wrapper { position: relative; flex-grow: 1; }

    .custom-select-box {
        appearance: none; -webkit-appearance: none; -moz-appearance: none;
        border: none; background: transparent;
        font-weight: 700; font-size: 1rem; color: #333;
        padding-right: 25px; cursor: pointer; width: 100%; outline: none;
    }
    
    .custom-select-arrow {
        position: absolute; right: 0; top: 50%;
        transform: translateY(-50%); pointer-events: none;
        color: #333; font-size: 0.8rem;
    }

    .filter-right-options {
        display: flex; overflow-x: auto; gap: 8px;
        align-items: center; padding: 5px 0; scrollbar-width: none;
    }
    .filter-right-options::-webkit-scrollbar { display: none; }

    .filter-pill {
        padding: 8px 16px; border: 1px solid #e9eaec; border-radius: 50px;
        background: #fff; color: #555; font-size: 0.9rem;
        text-decoration: none; white-space: nowrap; transition: all 0.2s;
    }
    .filter-pill:hover { background: #f4f5f5; color: #00b14f; }
    .filter-pill.active {
        background: #e5f7ed; color: #00b14f; border-color: #00b14f; font-weight: 600;
    }

    .filter-group-content { display: none; }
    .filter-group-content.active { display: flex; }
    
    .job-card:hover { border-color: #00b14f !important; box-shadow: 0 5px 15px rgba(0,177,79,0.1) !important; transform: translateY(-3px); }
</style>

<div class="position-relative" style="background: linear-gradient(135deg, #00b14f 0%, #00d65e 100%); padding: 60px 0 80px 0;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7 text-white">
                <h1 class="fw-bold mb-3">Tìm việc làm nhanh 24h, việc làm mới nhất</h1>
                <p class="opacity-75 mb-4">Tiếp cận <strong>40,000+</strong> tin tuyển dụng việc làm mỗi ngày từ hàng nghìn doanh nghiệp uy tín tại Việt Nam</p>
                
                <div class="bg-white p-2 rounded-3 shadow-lg">
                    <form action="index.php" method="GET" class="row g-2">
                        <div class="col-md-9">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-0 ps-3"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="q" class="form-control border-0 shadow-none ps-2" 
                                       placeholder="Vị trí tuyển dụng, tên công ty..." 
                                       value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-success w-100 h-100 fw-bold" style="background-color: #00b14f;">Tìm kiếm</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-5 ps-lg-5 mt-4 mt-lg-0">
                <div class="d-flex align-items-center text-white mb-2">
                     <i class="fas fa-chart-bar me-2"></i>
                     <span class="fw-bold">Thị trường việc làm hôm nay <?= date('d/m/Y') ?></span>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 rounded-3 text-center text-white" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.2);">
                            <div class="fs-3 fw-bold text-warning"><?= number_format($count_active) ?></div>
                            <div class="small opacity-75">Việc làm đang tuyển</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3 text-center text-white" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.2);">
                            <div class="fs-3 fw-bold" style="color: #a8ffc3;"><?= number_format($count_new) ?></div>
                            <div class="small opacity-75">Việc làm mới hôm nay</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="smart-filter-container d-flex flex-column flex-md-row">
        <div class="filter-left-control">
            <i class="fas fa-filter filter-icon"></i>
            <span class="filter-label-text">Lọc theo:</span>
            
            <div class="custom-select-wrapper">
                <select id="smartFilterSelect" class="custom-select-box" style="text-align: center;">
                    <option value="location" style="text-align: center;">Địa điểm</option>
                    <option value="salary" style="text-align: center;">Mức lương</option>
                    <option value="experience" style="text-align: center;">Kinh nghiệm</option>
                    <option value="category" style="text-align: center;">Ngành nghề</option>
                </select>
                <i class="fas fa-chevron-down custom-select-arrow"></i>
            </div>
        </div>

        <div class="filter-right-options flex-grow-1">
            <div id="filter-group-location" class="filter-group-content active">
                <a href="index.php" class="filter-pill <?= !isset($_GET['location_id']) ? 'active' : '' ?>">Ngẫu nhiên</a>
                <?php foreach($locations as $l): ?>
                    <a href="index.php?location_id=<?= $l['id'] ?>" class="filter-pill <?= (isset($_GET['location_id']) && $_GET['location_id'] == $l['id']) ? 'active' : '' ?>"><?= $l['name'] ?></a>
                <?php endforeach; ?>
            </div>

            <div id="filter-group-salary" class="filter-group-content">
                <a href="index.php" class="filter-pill <?= !isset($_GET['salary_range']) ? 'active' : '' ?>">Tất cả mức lương</a>
                <?php foreach($salary_ranges as $s): ?>
                    <a href="index.php?salary_range=<?= urlencode($s) ?>" class="filter-pill <?= (isset($_GET['salary_range']) && $_GET['salary_range'] == $s) ? 'active' : '' ?>"><?= $s ?></a>
                <?php endforeach; ?>
            </div>

            <div id="filter-group-experience" class="filter-group-content">
                <a href="index.php" class="filter-pill <?= !isset($_GET['experience']) ? 'active' : '' ?>">Tất cả kinh nghiệm</a>
                <?php foreach($experiences as $e): ?>
                    <a href="index.php?experience=<?= urlencode($e) ?>" class="filter-pill <?= (isset($_GET['experience']) && $_GET['experience'] == $e) ? 'active' : '' ?>"><?= $e ?></a>
                <?php endforeach; ?>
            </div>

            <div id="filter-group-category" class="filter-group-content">
                <a href="index.php" class="filter-pill <?= !isset($_GET['category_id']) ? 'active' : '' ?>">Tất cả ngành nghề</a>
                <?php foreach($categories as $c): ?>
                    <a href="index.php?category_id=<?= $c['id'] ?>" class="filter-pill <?= (isset($_GET['category_id']) && $_GET['category_id'] == $c['id']) ? 'active' : '' ?>"><?= $c['name'] ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="container py-5 bg-light mt-4 rounded-top-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <h3 class="fw-bold text-success mb-0 me-3">Việc làm tốt nhất</h3>
            <span class="badge bg-danger rounded-pill">TOP</span>
        </div>
        <a href="jobs.php" class="text-success text-decoration-none fw-bold d-flex align-items-center hover-underline">
            Xem tất cả <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>

    <?php if(count($jobs) > 0): ?>
        <div class="row g-3">
            <?php foreach($jobs as $job): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card job-card h-100 p-3 bg-white rounded-3 border-0 shadow-sm transition-all">
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0 me-3">
                            <a href="company-detail.php?id=<?= $job['company_id'] ?>">
                                <img src="<?= !empty($job['logo']) ? $job['logo'] : 'uploads/default-logo.png' ?>" 
                                     class="rounded border bg-white p-1" 
                                     style="width: 50px; height: 50px; object-fit: contain;">
                            </a>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <h6 class="mb-1 text-truncate fw-bold">
                                <a href="job-detail.php?id=<?= $job['id'] ?>" class="text-dark text-decoration-none" title="<?= htmlspecialchars($job['title']) ?>">
                                    <?= htmlspecialchars($job['title']) ?>
                                </a>
                            </h6>
                            <div class="small text-muted text-truncate mb-2"><?= htmlspecialchars($job['company_name']) ?></div>
                        </div>
                    </div>
                    
                    <div class="mt-auto">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-light text-dark fw-normal border"><?= $job['salary_range'] ?></span>
                            <span class="badge bg-light text-dark fw-normal border"><?= $job['city'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light">
                             <div class="text-muted" style="font-size: 0.8rem;">
                                 <?= (strtotime($job['deadline']) - time()) > 0 ? 'Còn ' . ceil((strtotime($job['deadline']) - time()) / 86400) . ' ngày' : 'Hết hạn' ?>
                             </div>
                             <button class="btn btn-sm text-danger border-0 p-0" title="Lưu tin"><i class="far fa-heart"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <?php 
                    $queryParams = $_GET; 
                    unset($queryParams['page']);
                    $queryString = http_build_query($queryParams);
                    $link = "index.php?" . ($queryString ? $queryString . "&" : "") . "page=";
                ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link <?= ($page == $i) ? 'bg-success border-success text-white' : 'text-success' ?>" 
                           href="<?= $link . $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="text-center py-5">
            <p class="text-muted">Chưa tìm thấy công việc phù hợp.</p>
            <a href="index.php" class="btn btn-outline-success">Xóa bộ lọc</a>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectBox = document.getElementById('smartFilterSelect');
        const groups = {
            'location': document.getElementById('filter-group-location'),
            'salary': document.getElementById('filter-group-salary'),
            'experience': document.getElementById('filter-group-experience'),
            'category': document.getElementById('filter-group-category')
        };

        function switchFilter(value) {
            for (let key in groups) {
                if(groups[key]) groups[key].classList.remove('active');
            }
            if(groups[value]) groups[value].classList.add('active');
        }

        selectBox.addEventListener('change', function() {
            switchFilter(this.value);
        });

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('salary_range')) { selectBox.value = 'salary'; switchFilter('salary'); } 
        else if (urlParams.has('experience')) { selectBox.value = 'experience'; switchFilter('experience'); } 
        else if (urlParams.has('category_id')) { selectBox.value = 'category'; switchFilter('category'); } 
        else { selectBox.value = 'location'; switchFilter('location'); }
    });
</script>

<?php include 'includes/footer.php'; ?>