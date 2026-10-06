<?php
// admin/reports/problems.php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/notification-functions.php';
require_login();
if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

// Handle action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $rid    = int_post('report_id');
    $status = post('status');
    $note   = post('admin_note');

    $pdo->prepare("UPDATE problem_reports SET status=:s, admin_note=:n, resolved_at = IF(:s2='resolved', NOW(), NULL) WHERE report_id=:r")
        ->execute([':s' => $status, ':s2' => $status, ':n' => $note, ':r' => $rid]);

    set_flash('success', 'Report updated.');
    redirect(BASE_URL . 'admin/reports/problems.php');
}

$rows = $pdo->query("
    SELECT pr.*, u.name AS reporter_name, d.food_name
    FROM problem_reports pr
    JOIN users u ON u.user_id = pr.reported_by
    LEFT JOIN food_donations d ON d.donation_id = pr.donation_id
    ORDER BY pr.created_at DESC
")->fetchAll();

$pageTitle = 'Problem Reports';
require_once __DIR__ . '/../../includes/dashboard-header.php';
?>

<div class="table-card">
    <div class="table-card-header"><h3>Problem Reports (<?= count($rows) ?>)</h3></div>
    <?php if (!$rows): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No reports.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Reporter</th><th>Food</th><th>Description</th><th>Evidence</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= sanitize($r['reporter_name']) ?><br><small class="text-muted"><?= time_ago($r['created_at']) ?></small></td>
                    <td><?= sanitize($r['food_name'] ?: '—') ?></td>
                    <td style="max-width:300px"><?= sanitize($r['description']) ?></td>
                    <td>
                        <?php if ($r['evidence_image']): ?>
                            <a href="<?= PROOF_UPLOAD_URL . rawurlencode($r['evidence_image']) ?>" target="_blank" class="btn btn-outline btn-sm">View</a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td>
                        <button class="btn btn-outline btn-sm" onclick="document.getElementById('f<?= (int)$r['report_id'] ?>').style.display='block'">Resolve</button>
                        <div id="f<?= (int)$r['report_id'] ?>" style="display:none;margin-top:10px">
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
                                <select name="status" class="form-control mb-1">
                                    <option value="investigating">Investigating</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                <textarea name="admin_note" class="form-control mb-1" placeholder="Admin note"></textarea>
                                <button class="btn btn-primary btn-sm">Save</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
