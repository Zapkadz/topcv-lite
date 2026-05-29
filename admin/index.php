<?php
include 'includes/header.php';

// 1. Thống kê con số tổng quát
$count_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$count_jobs = $conn->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$count_apps = $conn->query("SELECT COUNT(*) FROM applications")->fetchColumn();

// 2. Dữ liệu cho Biểu đồ Tròn: Phân loại User (Admin, Employer, Candidate)
$user_roles = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
$role_labels = [];
$role_data = [];
foreach($user_roles as $r) {
    $role_labels[] = ucfirst($r['role']);
    $role_data[] = $r['count'];
}
$sql_top_jobs = "
    SELECT j.title, c.name as company_name, COUNT(app.id) as total_apps 
    FROM jobs j
    JOIN companies c ON j.company_id = c.id
    LEFT JOIN applications app ON j.id = app.job_id
    GROUP BY j.id
    ORDER BY total_apps DESC
    LIMIT 3";
$top_jobs = $conn->query($sql_top_jobs)->fetchAll(PDO::FETCH_ASSOC);
// 3. Dữ liệu cho Biểu đồ Cột & Sóng: Đơn ứng tuyển theo 7 ngày gần nhất
$apps_7days = $conn->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM applications 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

$app_labels = [];
$app_data = [];
// Tạo mảng 7 ngày để đảm bảo biểu đồ không bị trống ngày nào
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $app_labels[] = date('d/m', strtotime($d));
    $found = false;
    foreach($apps_7days as $a) {
        if($a['date'] == $d) {
            $app_data[] = $a['count'];
            $found = true;
            break;
        }
    }
    if(!$found) $app_data[] = 0;
}
?>
<style>
    .bg-soft-success { background-color: #e8f7ee; color: #00b14f; }
    .table-hover tbody tr:hover { background-color: #fcfcfc; }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<h3 class="mb-4 fw-bold text-success">Tổng quan hệ thống</h3>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card p-3 border-0 text-white shadow-sm" style="background: linear-gradient(45deg, #00b14f, #02dfa5);">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title opacity-75 small">Người dùng</h5>
                    <h2 class="fw-bold mb-0"><?= number_format($count_users) ?></h2>
                </div>
                <i class="fas fa-users fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 border-0 text-white shadow-sm" style="background: linear-gradient(45deg, #3498db, #5dade2);">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title opacity-75 small">Tin tuyển dụng</h5>
                    <h2 class="fw-bold mb-0"><?= number_format($count_jobs) ?></h2>
                </div>
                <i class="fas fa-briefcase fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 border-0 text-white shadow-sm" style="background: linear-gradient(45deg, #f1c40f, #f7dc6f);">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title opacity-75 small">Đơn ứng tuyển</h5>
                    <h2 class="fw-bold mb-0"><?= number_format($count_apps) ?></h2>
                </div>
                <i class="fas fa-file-alt fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold mb-4 text-dark">
                <i class="fas fa-chart-line text-primary me-2"></i>Xu hướng ứng tuyển (7 ngày qua)
            </h5>
            <canvas id="lineChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold mb-4 text-dark">
                <i class="fas fa-chart-bar text-success me-2"></i>Số lượng đơn ứng tuyển
            </h5>
            <canvas id="barChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <div class="row g-4 mt-1">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold mb-4 text-dark">
                <i class="fas fa-chart-pie text-warning me-2"></i>Phân loại người dùng
            </h5>
            <div style="position: relative; height: 250px;">
                <canvas id="pieChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold mb-4 text-dark">
                <i class="fas fa-fire text-danger me-2"></i>Top tin tuyển dụng thu hút nhất
            </h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.85rem;">
                            <th>Tin tuyển dụng</th>
                            <th class="text-center">Số đơn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($top_jobs as $job): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-truncate" style="max-width: 250px;"><?= htmlspecialchars($job['title']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($job['company_name']) ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-soft-success text-success rounded-pill px-3">
                                    <?= $job['total_apps'] ?> ứng tuyển
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<script>
// Cấu hình chung cho Tiếng Việt
Chart.defaults.font.family = "'Inter', sans-serif";

// 1. BIỂU ĐỒ SÓNG (Xu hướng đơn nộp)
const ctxLine = document.getElementById('lineChart').getContext('2d');
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: <?= json_encode($app_labels) ?>,
        datasets: [{
            label: 'Lượt ứng tuyển',
            data: <?= json_encode($app_data) ?>,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#3498db'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { 
                callbacks: { label: (context) => ` ${context.raw} đơn nộp` } 
            }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// 2. BIỂU ĐỒ CỘT (Số lượng đơn nộp)
const ctxBar = document.getElementById('barChart').getContext('2d');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: <?= json_encode($app_labels) ?>,
        datasets: [{
            label: 'Số lượng đơn',
            data: <?= json_encode($app_data) ?>,
            backgroundColor: '#00b14f',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        }
    }
});

// 3. BIỂU ĐỒ TRÒN (Việt hóa vai trò)
const ctxPie = document.getElementById('pieChart').getContext('2d');
// Việt hóa nhãn dữ liệu
const vnLabels = <?= json_encode($role_labels) ?>.map(label => {
    if(label === 'Admin') return 'Quản trị viên';
    if(label === 'Employer') return 'Nhà tuyển dụng';
    if(label === 'Candidate') return 'Ứng viên';
    return label;
});

new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: vnLabels,
        datasets: [{
            data: <?= json_encode($role_data) ?>,
            backgroundColor: ['#e74c3c', '#f1c40f', '#00b14f'],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { 
                position: 'bottom',
                labels: { usePointStyle: true, padding: 20 }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>