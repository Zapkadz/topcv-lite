<?php
require_once __DIR__ . '/../includes/csrf.php';
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!csrf_validate('admin_location_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: locations.php');
        exit();
    }

    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Tên địa điểm không được để trống.';
        header('Location: locations.php');
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $check = $conn->prepare('SELECT id FROM locations WHERE name = ? LIMIT 1');
        $check->execute([$name]);
        if ($check->fetch()) {
            $_SESSION['swal_icon'] = 'warning';
            $_SESSION['swal_title'] = 'Địa điểm này đã tồn tại.';
        } else {
            $stmt = $conn->prepare('INSERT INTO locations (name) VALUES (?)');
            $stmt->execute([$name]);
            $_SESSION['swal_icon'] = 'success';
            $_SESSION['swal_title'] = 'Thêm địa điểm thành công!';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = (int) $_POST['id'];
        $dup = $conn->prepare('SELECT id FROM locations WHERE name = ? AND id != ? LIMIT 1');
        $dup->execute([$name, $id]);
        if ($dup->fetch()) {
            $_SESSION['swal_icon'] = 'warning';
            $_SESSION['swal_title'] = 'Tên địa điểm đã được dùng bởi bản ghi khác.';
        } else {
            $stmt = $conn->prepare('UPDATE locations SET name = ? WHERE id = ?');
            $stmt->execute([$name, $id]);
            $_SESSION['swal_icon'] = 'success';
            $_SESSION['swal_title'] = 'Cập nhật thành công!';
        }
    }

    header('Location: locations.php');
    exit();
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $used = $conn->prepare('SELECT COUNT(*) FROM jobs WHERE location_id = ?');
    $used->execute([$id]);
    if ((int) $used->fetchColumn() > 0) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Không thể xóa — đang có tin tuyển dụng dùng địa điểm này.';
    } else {
        $stmt = $conn->prepare('DELETE FROM locations WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['swal_icon'] = 'success';
        $_SESSION['swal_title'] = 'Đã xóa địa điểm!';
    }
    header('Location: locations.php');
    exit();
}

$locations = $conn->query('SELECT l.*, (SELECT COUNT(*) FROM jobs j WHERE j.location_id = l.id) AS job_count FROM locations l ORDER BY l.name ASC')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1">Quản lý Địa điểm</h3>
        <p class="text-muted small mb-0">36 địa điểm (34 đơn vị + Remote + Khác). DB cũ: <code>php docs/migrations/run-phase-1-1-locations.php</code></p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Thêm mới</button>
</div>

<table class="table table-bordered table-hover">
    <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>Tên địa điểm</th>
            <th>Số tin đang dùng</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($locations as $loc): ?>
        <tr>
            <td><?= (int) $loc['id'] ?></td>
            <td><?= htmlspecialchars($loc['name']) ?></td>
            <td><?= (int) $loc['job_count'] ?></td>
            <td>
                <button class="btn btn-sm btn-warning btn-edit"
                        data-id="<?= (int) $loc['id'] ?>"
                        data-name="<?= htmlspecialchars($loc['name']) ?>"
                        data-bs-toggle="modal" data-bs-target="#editModal">Sửa</button>
                <?php if ((int) $loc['job_count'] === 0): ?>
                <button onclick="confirmDelete('locations.php?delete=<?= (int) $loc['id'] ?>')" class="btn btn-sm btn-danger">Xóa</button>
                <?php else: ?>
                <button class="btn btn-sm btn-danger" disabled title="Đang có tin tuyển dụng">Xóa</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('admin_location_form')) ?>">
            <div class="modal-header"><h5 class="modal-title">Thêm địa điểm</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <label class="form-label">Tên địa điểm</label>
                <input type="text" name="name" class="form-control" required maxlength="100">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('admin_location_form')) ?>">
            <div class="modal-header"><h5 class="modal-title">Sửa địa điểm</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <label class="form-label">Tên địa điểm</label>
                <input type="text" name="name" id="edit_name" class="form-control" required maxlength="100">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
