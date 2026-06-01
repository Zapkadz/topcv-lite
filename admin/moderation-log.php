<?php
require_once __DIR__ . '/../includes/schema_moderation.php';
require_once __DIR__ . '/../includes/moderation_log.php';
require_once __DIR__ . '/../includes/services/ModerationLogService.php';
include 'includes/header.php';

$filter = $_GET['type'] ?? '';
if ($filter !== '' && !in_array($filter, moderation_entity_types(), true)) {
    $filter = '';
}

if (!moderation_schema_ready($conn)) {
    echo moderation_schema_migration_hint_html();
    include 'includes/footer.php';
    exit;
}

$logs = ModerationLogService::listRecent($conn, 150, $filter !== '' ? $filter : null);

$entityLabels = [
    '' => 'Tất cả',
    'job' => 'Tin tuyển dụng',
    'employer' => 'Nhà tuyển dụng',
];
?>

<h3 class="mb-4 fw-bold text-success"><i class="fas fa-clipboard-list"></i> Nhật ký kiểm duyệt</h3>

<div class="mb-3">
    <?php foreach ($entityLabels as $key => $label): ?>
        <a href="moderation-log.php<?= $key !== '' ? '?type=' . urlencode($key) : '' ?>"
           class="btn btn-sm <?= ($filter === $key) ? 'btn-success' : 'btn-outline-secondary' ?> me-1 mb-1">
            <?= htmlspecialchars($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (count($logs) === 0): ?>
            <div class="p-4 text-muted">Chưa có bản ghi kiểm duyệt nào.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Thời gian</th>
                            <th>Admin</th>
                            <th>Loại</th>
                            <th>ID đối tượng</th>
                            <th>Hành động</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $row): ?>
                            <tr>
                                <td class="ps-4 small"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['admin_name'] ?? '') ?></td>
                                <td><?= moderation_entity_type_label($row['entity_type']) ?></td>
                                <td>
                                    <?php if ($row['entity_type'] === 'job'): ?>
                                        <a href="jobs.php" class="text-decoration-none">#<?= (int) $row['entity_id'] ?></a>
                                    <?php else: ?>
                                        <a href="users.php" class="text-decoration-none">#<?= (int) $row['entity_id'] ?></a>
                                    <?php endif; ?>
                                </td>
                                <td><?= moderation_action_badge_html($row['action']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($row['note'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
