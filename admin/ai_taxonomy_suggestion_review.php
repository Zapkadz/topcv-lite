<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_ai_taxonomy.php';
require_once __DIR__ . '/../includes/ai_taxonomy_config.php';
require_once __DIR__ . '/../includes/repositories/AiTaxonomyRepository.php';
require_once __DIR__ . '/../includes/services/AiTaxonomyService.php';
include 'includes/header.php';

if (!ai_taxonomy_schema_ready($conn)) {
    echo ai_taxonomy_migration_hint_html();
    include 'includes/footer.php';
    exit;
}

$adminId = (int) ($_SESSION['user_id'] ?? 0);
$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate('admin_taxonomy_review_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: ai_taxonomy_suggestion_review.php?id=' . (int) ($_POST['id'] ?? 0));
        exit();
    }

    $postId = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['decision_action'] ?? '');
    $note = trim((string) ($_POST['decision_note'] ?? ''));

    if ($action === 'approve_new_skill') {
        $result = AiTaxonomyService::approveNewSkill(
            $conn,
            $postId,
            $adminId,
            (string) ($_POST['canonical_name'] ?? ''),
            (string) ($_POST['category'] ?? ''),
            ai_taxonomy_parse_aliases_text((string) ($_POST['aliases_text'] ?? '')),
            ai_taxonomy_parse_aliases_text((string) ($_POST['related_text'] ?? '')),
            ai_taxonomy_parse_aliases_text((string) ($_POST['transferable_text'] ?? ''))
        );
    } elseif ($action === 'add_alias_to_existing') {
        $result = AiTaxonomyService::addAliasesToExisting(
            $conn,
            $postId,
            $adminId,
            (string) ($_POST['target_skill_name'] ?? ''),
            ai_taxonomy_parse_aliases_text((string) ($_POST['aliases_text'] ?? '')),
            'add_alias_to_existing',
            'approved_alias'
        );
    } elseif ($action === 'merge_to_existing') {
        $result = AiTaxonomyService::addAliasesToExisting(
            $conn,
            $postId,
            $adminId,
            (string) ($_POST['target_skill_name'] ?? ''),
            ai_taxonomy_parse_aliases_text((string) ($_POST['aliases_text'] ?? '')),
            'merge_to_existing',
            'merged'
        );
    } elseif ($action === 'reject') {
        $result = AiTaxonomyService::rejectSuggestion($conn, $postId, $adminId, $note);
    } else {
        $result = ['ok' => false, 'message' => 'Hành động không hợp lệ.'];
    }

    $_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
    $_SESSION['swal_title'] = $result['message'];
    header('Location: ' . ($result['ok'] ? 'ai_taxonomy_suggestions.php' : 'ai_taxonomy_suggestion_review.php?id=' . $postId));
    exit();
}

$suggestion = $id > 0 ? AiTaxonomyRepository::findSuggestionById($conn, $id) : null;
if (!$suggestion) {
    echo '<div class="alert alert-warning">Không tìm thấy suggestion.</div>';
    echo '<a href="ai_taxonomy_suggestions.php">← Quay lại danh sách</a>';
    include 'includes/footer.php';
    exit;
}

$aliases = AiTaxonomyService::decodeJsonList($suggestion['suggested_aliases_json'] ?? null);
$contexts = AiTaxonomyService::decodeJsonList($suggestion['example_contexts_json'] ?? null);
$evidence = AiTaxonomyService::decodeJsonList($suggestion['example_evidence_json'] ?? null);
$nearest = json_decode((string) ($suggestion['nearest_existing_skills_json'] ?? '[]'), true);
$nearest = is_array($nearest) ? $nearest : [];
$skillOptions = AiTaxonomyService::listSkillNamesForSelect($conn);
$isPending = (string) ($suggestion['status'] ?? '') === 'pending_review';
$aliasesText = implode("\n", $aliases);
$st = (string) ($suggestion['status'] ?? '');
?>

<div class="mb-3">
    <a href="ai_taxonomy_suggestions.php" class="text-decoration-none small"><i class="fas fa-arrow-left"></i> Danh sách</a>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
        <h3 class="fw-bold text-success mb-1"><?= htmlspecialchars((string) ($suggestion['suggested_canonical_name'] ?? '')) ?></h3>
        <span class="badge <?= ai_taxonomy_status_badge_class($st) ?>"><?= htmlspecialchars(ai_taxonomy_status_label($st)) ?></span>
        <span class="small text-muted ms-2"><?= htmlspecialchars((string) ($suggestion['suggestion_id'] ?? '')) ?></span>
    </div>
    <?php if (!$isPending): ?>
        <div class="small text-muted">
            Đã xử lý
            <?php if (!empty($suggestion['reviewed_at'])): ?>
                · <?= htmlspecialchars((string) $suggestion['reviewed_at']) ?>
            <?php endif; ?>
            <?php if (!empty($suggestion['target_skill_name'])): ?>
                · Target: <strong><?= htmlspecialchars((string) $suggestion['target_skill_name']) ?></strong>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Suggestion</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-sm-4">Category</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string) ($suggestion['suggested_category'] ?? '—')) ?></dd>
                    <dt class="col-sm-4">Aliases</dt>
                    <dd class="col-sm-8"><?= $aliases !== [] ? htmlspecialchars(implode(', ', $aliases)) : '—' ?></dd>
                    <dt class="col-sm-4">Frequency</dt>
                    <dd class="col-sm-8"><?= (int) ($suggestion['frequency'] ?? 0) ?></dd>
                    <dt class="col-sm-4">Confidence</dt>
                    <dd class="col-sm-8"><?= $suggestion['confidence'] !== null ? round((float) $suggestion['confidence'] * 100) . '%' : '—' ?></dd>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Evidence &amp; Context</div>
            <div class="card-body small">
                <?php if ($contexts !== []): ?>
                    <p class="fw-bold mb-1">Contexts</p>
                    <ul class="mb-3"><?php foreach ($contexts as $c): ?><li><?= htmlspecialchars($c) ?></li><?php endforeach; ?></ul>
                <?php endif; ?>
                <?php if ($evidence !== []): ?>
                    <p class="fw-bold mb-1">Evidence</p>
                    <ul class="mb-0"><?php foreach ($evidence as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                <?php endif; ?>
                <?php if ($contexts === [] && $evidence === []): ?>
                    <span class="text-muted">Không có context/evidence.</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Nearest Existing Skills</div>
            <div class="card-body small">
                <?php if ($nearest === []): ?>
                    <span class="text-muted">—</span>
                <?php else: ?>
                    <ul class="mb-0">
                        <?php foreach ($nearest as $n): ?>
                            <?php if (!is_array($n)) {
                                continue;
                            } ?>
                            <li><?= htmlspecialchars((string) ($n['skill'] ?? '')) ?>
                                <span class="text-muted">(<?= round((float) ($n['similarity'] ?? 0) * 100) ?>%)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <details class="card border-0 shadow-sm">
            <summary class="card-header bg-white fw-bold" style="cursor:pointer">Raw JSON (debug)</summary>
            <div class="card-body">
                <pre class="small bg-light p-3 rounded mb-0" style="max-height:240px;overflow:auto"><?=
                    htmlspecialchars((string) ($suggestion['raw_json'] ?? '{}'))
                ?></pre>
            </div>
        </details>
    </div>

    <div class="col-lg-5">
        <?php if ($isPending): ?>
        <div class="card border-0 shadow-sm sticky-top" style="top:1rem">
            <div class="card-header bg-success text-white fw-bold">Decision</div>
            <div class="card-body">
                <form method="POST" id="taxonomyReviewForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('admin_taxonomy_review_form')) ?>">
                    <input type="hidden" name="id" value="<?= (int) $suggestion['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hành động</label>
                        <select name="decision_action" id="decisionAction" class="form-select" required>
                            <option value="approve_new_skill">Approve as new skill</option>
                            <option value="add_alias_to_existing">Add aliases to existing skill</option>
                            <option value="merge_to_existing">Merge to existing skill</option>
                            <option value="reject">Reject</option>
                        </select>
                    </div>

                    <div id="blockNewSkill">
                        <div class="mb-3">
                            <label class="form-label">Canonical skill name</label>
                            <input type="text" name="canonical_name" class="form-control"
                                value="<?= htmlspecialchars((string) ($suggestion['suggested_canonical_name'] ?? '')) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control"
                                value="<?= htmlspecialchars((string) ($suggestion['suggested_category'] ?? 'Pending Classification')) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Related skills (mỗi dòng 1 skill, tùy chọn)</label>
                            <textarea name="related_text" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transferable skills (tùy chọn)</label>
                            <textarea name="transferable_text" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <div id="blockTargetSkill" class="mb-3" style="display:none">
                        <label class="form-label">Target existing skill</label>
                        <select name="target_skill_name" class="form-select">
                            <option value="">— Chọn skill —</option>
                            <?php foreach ($skillOptions as $name): ?>
                                <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="blockAliases">
                        <label class="form-label">Aliases (mỗi dòng 1 alias)</label>
                        <textarea name="aliases_text" class="form-control" rows="4"><?= htmlspecialchars($aliasesText) ?></textarea>
                    </div>

                    <div class="mb-3" id="blockNote" style="display:none">
                        <label class="form-label">Ghi chú từ chối</label>
                        <textarea name="decision_note" class="form-control" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold">Lưu quyết định</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body small">
                <p><strong>Decision:</strong> <?= htmlspecialchars((string) ($suggestion['decision_type'] ?? '—')) ?></p>
                <?php if (!empty($suggestion['decision_note'])): ?>
                    <p><strong>Note:</strong> <?= htmlspecialchars((string) $suggestion['decision_note']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isPending): ?>
<script>
(function () {
    const action = document.getElementById('decisionAction');
    const blockNew = document.getElementById('blockNewSkill');
    const blockTarget = document.getElementById('blockTargetSkill');
    const blockAliases = document.getElementById('blockAliases');
    const blockNote = document.getElementById('blockNote');

    function sync() {
        const v = action.value;
        blockNew.style.display = v === 'approve_new_skill' ? '' : 'none';
        blockTarget.style.display = (v === 'add_alias_to_existing' || v === 'merge_to_existing') ? '' : 'none';
        blockAliases.style.display = v === 'reject' ? 'none' : '';
        blockNote.style.display = v === 'reject' ? '' : 'none';
    }
    action.addEventListener('change', sync);
    sync();
})();
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
