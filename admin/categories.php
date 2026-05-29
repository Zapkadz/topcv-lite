<?php
require_once __DIR__ . '/../includes/csrf.php';
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!csrf_validate('admin_category_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: categories.php');
        exit();
    }
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $name = $_POST['name'];
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        $_SESSION['swal_icon'] = 'success';
        $_SESSION['swal_title'] = 'Thêm thành công!';
    } 
    elseif (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        $_SESSION['swal_icon'] = 'success';
        $_SESSION['swal_title'] = 'Cập nhật thành công!';
    }
    header("Location: categories.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['swal_icon'] = 'success';
    $_SESSION['swal_title'] = 'Đã xóa!';
    header("Location: categories.php");
    exit();
}

$cats = $conn->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Quản lý Danh mục</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Thêm mới</button>
</div>

<table class="table table-bordered table-hover">
    <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>Tên danh mục</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($cats as $c): ?>
        <tr>
            <td><?= $c['id'] ?></td>
            <td><?= htmlspecialchars($c['name']) ?></td>
            <td>
                <button class="btn btn-sm btn-warning btn-edit" 
                        data-id="<?= $c['id'] ?>" 
                        data-name="<?= htmlspecialchars($c['name']) ?>" 
                        data-bs-toggle="modal" data-bs-target="#editModal">Sửa</button>
                <button onclick="confirmDelete('categories.php?delete=<?= $c['id'] ?>')" class="btn btn-sm btn-danger">Xóa</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('admin_category_form')) ?>">
            <div class="modal-header"><h5 class="modal-title">Thêm danh mục</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <label>Tên danh mục</label>
                <input type="text" name="name" class="form-control" required>
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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('admin_category_form')) ?>">
            <div class="modal-header"><h5 class="modal-title">Sửa danh mục</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <label>Tên danh mục</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function(){
        const editBtns = document.querySelectorAll('.btn-edit');
        editBtns.forEach(btn => {
            btn.addEventListener('click', function(){
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_name').value = this.dataset.name;
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>