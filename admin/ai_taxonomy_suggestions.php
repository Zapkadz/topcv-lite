<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_ai_taxonomy.php';
require_once __DIR__ . '/../includes/repositories/AiTaxonomyRepository.php';
require_once __DIR__ . '/../includes/services/AiTaxonomyService.php';
require_once __DIR__ . '/../includes/ai_taxonomy_config.php';
include 'includes/header.php';

$taxonomyPages = ['ai_taxonomy_suggestions.php', 'ai_taxonomy_suggestion_import.php', 'ai_taxonomy_suggestion_review.php'];

if (!ai_taxonomy_schema_ready($conn)) {
    echo ai_taxonomy_migration_hint_html();
    include 'includes/footer.php';
    exit;
}

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'created_at'));
$dir = trim((string) ($_GET['dir'] ?? 'DESC'));

$suggestions = AiTaxonomyRepository::listSuggestions($conn, [
    'status' => $statusFilter,
    'search' => $search,
    'sort' => $sort,
    'dir' => $dir,
]);

$statusCounts = [
    'pending_review' => AiTaxonomyRepository::countByStatus($conn, 'pending_review'),
    'approved_new_skill' => AiTaxonomyRepository::countByStatus($conn, 'approved_new_skill'),
    'approved_alias' => AiTaxonomyRepository::countByStatus($conn, 'approved_alias'),
    'merged' => AiTaxonomyRepository::countByStatus($conn, 'merged'),
    'rejected' => AiTaxonomyRepository::countByStatus($conn, 'rejected'),
];

function taxonomy_sort_link(string $field, string $label, string $currentSort, string $currentDir, string $status, string $search): string
{
    $nextDir = ($currentSort === $field && $currentDir === 'DESC') ? 'ASC' : 'DESC';
    $qs = http_build_query(array_filter([
        'status' => $status !== '' ? $status : null,
        'q' => $search !== '' ? $search : null,
        'sort' => $field,
        'dir' => $nextDir,
    ]));

    return '<a href="ai_taxonomy_suggestions.php?' . htmlspecialchars($qs) . '" class="text-decoration-none text-dark">'
        . htmlspecialchars($label)
        . ($currentSort === $field ? ' <i class="fas fa-sort-' . ($currentDir === 'ASC' ? 'up' : 'down') . ' small"></i>' : '')
        . '</a>';
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h3 class="mb-1 fw-bold text-success"><i class="fas fa-tags"></i> Taxonomy Suggestions</h3>
        <p class="text-muted small mb-0">Duyệt skill do AI đề xuất — rebuild <code>skills_merged.json</code> cho screening.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="ai_taxonomy_suggestion_import.php" class="btn btn-outline-success btn-sm fw-bold">
            <i class="fas fa-file-import"></i> Import JSON
        </a>
        <form method="POST" action="ai_taxonomy_export.php" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('admin_taxonomy_export_form')) ?>">
            <button type="submit" class="btn btn-success btn-sm fw-bold">
                <i class="fas fa-file-export"></i> Export merged taxonomy
            </button>
        </form>
    </div>
</div>

<div class="mb-3 d-flex flex-wrap gap-2">
    <a href="ai_taxonomy_suggestions.php" class="btn btn-sm <?= $statusFilter === '' ? 'btn-success' : 'btn-outline-secondary' ?>">Tất cả</a>
    <?php foreach (['pending_review', 'approved_new_skill', 'approved_alias', 'merged', 'rejected'] as $st): ?>
        <a href="ai_taxonomy_suggestions.php?status=<?= urlencode($st) ?>"
           class="btn btn-sm <?= $statusFilter === $st ? 'btn-success' : 'btn-outline-secondary' ?>">
            <?= htmlspecialchars(ai_taxonomy_status_label($st)) ?>
            <span class="badge bg-light text-dark ms-1"><?= (int) ($statusCounts[$st] ?? 0) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<form method="GET" class="row g-2 mb-4">
    <?php if ($statusFilter !== ''): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
    <?php endif; ?>
    <div class="col-md-8">
        <input type="text" name="q" class="form-control" placeholder="Tìm theo tên skill, alias, suggestion_id..."
            value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-4">
        <button type="submit" class="btn btn-outline-success w-100">Tìm kiếm</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th><?= taxonomy_sort_link('suggested_canonical_name', 'Skill đề xuất', $sort, $dir, $statusFilter, $search) ?></th>
                <th>Category</th>
                <th>Aliases</th>
                <th><?= taxonomy_sort_link('frequency', 'Freq', $sort, $dir, $statusFilter, $search) ?></th>
                <th><?= taxonomy_sort_link('confidence', 'Conf', $sort, $dir, $statusFilter, $search) ?></th>
                <th>Gần nhất</th>
                <th>Status</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($suggestions === []): ?>
                <tr><td colspan="8" class="text-muted text-center py-4">Chưa có suggestion. <a href="ai_taxonomy_suggestion_import.php">Import JSON</a></td></tr>
            <?php else: ?>
                <?php foreach ($suggestions as $row): ?>
                    <?php
                    $aliases = AiTaxonomyService::decodeJsonList($row['suggested_aliases_json'] ?? null);
                    $nearest = json_decode((string) ($row['nearest_existing_skills_json'] ?? '[]'), true);
                    $nearestText = '';
                    if (is_array($nearest) && $nearest !== []) {
                        $parts = [];
                        foreach (array_slice($nearest, 0, 2) as $n) {
                            if (is_array($n)) {
                                $parts[] = ($n['skill'] ?? '') . ' (' . round((float) ($n['similarity'] ?? 0) * 100) . '%)';
                            }
                        }
                        $nearestText = implode(', ', $parts);
                    }
                    $st = (string) ($row['status'] ?? '');
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars((string) ($row['suggested_canonical_name'] ?? '')) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars((string) ($row['suggestion_id'] ?? '')) ?></div>
                        </td>
                        <td class="small"><?= htmlspecialchars((string) ($row['suggested_category'] ?? '—')) ?></td>
                        <td class="small"><?= htmlspecialchars(implode(', ', array_slice($aliases, 0, 3))) ?><?= count($aliases) > 3 ? '…' : '' ?></td>
                        <td><?= (int) ($row['frequency'] ?? 0) ?></td>
                        <td><?= $row['confidence'] !== null ? round((float) $row['confidence'] * 100) . '%' : '—' ?></td>
                        <td class="small"><?= htmlspecialchars($nearestText ?: '—') ?></td>
                        <td><span class="badge <?= ai_taxonomy_status_badge_class($st) ?>"><?= htmlspecialchars(ai_taxonomy_status_label($st)) ?></span></td>
                        <td>
                            <a href="ai_taxonomy_suggestion_review.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
