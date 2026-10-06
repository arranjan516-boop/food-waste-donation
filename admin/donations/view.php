<?php
// admin/donations/view.php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/notification-functions.php';
require_login();
if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$id = int_get('id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post('action');

    if ($action === 'cancel') {
        $pdo->prepare("UPDATE food_donations SET status='cancelled' WHERE donation_id=:d")->execute([':d' => $id]);
        $pdo->prepare("INSERT INTO donation_history (donation_id, old_status, new_status, changed_by)
                       VALUES (:d, NULL, 'cancelled', :u)")->execute([':d' => $id, ':u' => current_user_id()]);
        set_flash('info', 'Donation cancelled.');
        redirect(BASE_URL . 'admin/donations/view.php?id=' . $id);
    }

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM food_donations WHERE donation_id=:d")->execute([':d' => $id]);
        set_flash('success', 'Donation deleted.');
        redirect(BASE_URL . 'admin/donations/index.php');
    }
}

$stmt = $pdo->prepare("SELECT d.*, u.name AS donor_name, u.email AS donor_email, u.phone AS donor_phone
                       FROM food_donations d JOIN users u ON u.user_id = d.donor_id
                       WHERE d.donation_id = :id");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch();
if (!$d) { set_flash('error', 'Not found.'); redirect(BASE_URL . 'admin/donations/index.php'); }

// Requests
$rq = $pdo->prepare("SELECT fr.*, u.name AS recipient_name FROM food_requests fr
                     JOIN users u ON u.user_id = fr.recipient_id
                     WHERE fr.donation_id = :d ORDER BY fr.requested_at DESC");
$rq->execute([':d' => $id]);
$requests = $rq->fetchAll();

// Collector tasks
$ct = $pdo->prepare("SELECT ct.*, u.name AS collector_name FROM collector_tasks ct
                     JOIN users u ON u.user_id = ct.collector_id
                     WHERE ct.donation_id = :d ORDER BY ct.assigned_at DESC");
$ct->execute([':d' => $id]);
$tasks = $ct->fetchAll();

// NGO requests
$nr = $pdo->prepare("SELECT nr.*, u.name AS ngo_name FROM ngo_requests nr
                     JOIN users u ON u.user_id = nr.ngo_id
                     WHERE nr.donation_id = :d ORDER BY nr.requested_at DESC");
$nr->execute([':d' => $id]);
$ngoRequests = $nr->fetchAll();

// Delivery proof
$dp = $pdo->prepare("SELECT dp.*, u.name AS uploader_name FROM delivery_proofs dp
                     JOIN users u ON u.user_id = dp.uploaded_by
                     WHERE dp.donation_id = :d ORDER BY dp.uploaded_at DESC LIMIT 1");
$dp->execute([':d' => $id]);
$proof = $dp->fetch();

// Problem reports
$pr = $pdo->prepare("SELECT pr.*, u.name AS reporter_name FROM problem_reports pr
                     JOIN users u ON u.user_id = pr.reported_by
                     WHERE pr.donation_id = :d ORDER BY pr.created_at DESC");
$pr->execute([':d' => $id]);
$problems = $pr->fetchAll();

$pageTitle = 'Donation #' . $id;
require_once __DIR__ . '/../../includes/dashboard-header.php';
?>

<div class="card mb-3">
    <div class="flex-between mb-2">
        <h2><?= sanitize($d['food_name']) ?></h2>
        <?= status_badge($d['status']) ?>
    </div>

    <div class="details-grid">
        <img src="<?= food_photo_url($d['food_photo']) ?>" alt="">
        <div>
            <p><strong>Donor:</strong> <?= sanitize($d['donor_name']) ?></p>
            <p><strong>Email:</strong> <?= sanitize($d['donor_email']) ?></p>
            <p><strong>Phone:</strong> <?= sanitize($d['donor_phone'] ?: '—') ?></p>
            <p><strong>Quantity:</strong> <?= (float)$d['quantity'] ?> <?= sanitize($d['unit']) ?></p>
            <p><strong>People served:</strong> <?= (int)$d['people_served'] ?></p>
            <p><strong>Category:</strong> <?= sanitize($d['food_category'] ?: '—') ?> · <?= sanitize($d['food_type'] ?: '—') ?></p>
            <p><strong>Best before:</strong> <?= $d['best_before'] ? date('d M Y, h:i A', strtotime($d['best_before'])) : '—' ?></p>
            <p><strong>Pickup:</strong> <?= sanitize($d['address'] ?: '—') ?>, <?= sanitize($d['area'] ?: '') ?> <?= sanitize($d['city'] ?: '') ?></p>
            <p><strong>Coordinates:</strong> <?= $d['latitude'] ? sanitize($d['latitude'].', '.$d['longitude']) : '—' ?></p>
            <p class="text-muted">Posted <?= time_ago($d['created_at']) ?></p>
        </div>
    </div>
</div>

<div class="card mb-3">
    <h3 class="card-title">Admin Actions</h3>
    <?php if (!in_array($d['status'], ['completed','cancelled','expired'])): ?>
        <form method="post" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="cancel">
            <button class="btn btn-danger btn-sm" data-confirm="Cancel this donation?">🚫 Cancel Donation</button>
        </form>
    <?php endif; ?>
    <form method="post" style="display:inline;margin-left:6px">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <button class="btn btn-outline btn-sm" data-confirm="Delete permanently?">🗑 Delete</button>
    </form>
    <a href="<?= BASE_URL ?>admin/donations/index.php" class="btn btn-outline btn-sm" style="margin-left:6px">← Back</a>
</div>

<?php if ($proof): ?>
<div class="card mb-3">
    <h3 class="card-title">📷 Delivery Proof</h3>
    <p><strong>Uploaded by:</strong> <?= sanitize($proof['uploader_name']) ?> (<?= sanitize($proof['uploaded_by_role']) ?>)</p>
    <p><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($proof['uploaded_at'])) ?></p>
    <?php if ($proof['delivery_note']): ?><p><strong>Note:</strong> <?= sanitize($proof['delivery_note']) ?></p><?php endif; ?>
    <img src="<?= PROOF_UPLOAD_URL . rawurlencode($proof['proof_image']) ?>" style="max-width:320px;border-radius:12px;margin-top:10px" alt="">
</div>
<?php endif; ?>

<?php if ($problems): ?>
<div class="card mb-3">
    <h3 class="card-title">⚠️ Problem Reports</h3>
    <?php foreach ($problems as $p): ?>
        <div style="padding:12px 0;border-bottom:1px solid var(--gray-100)">
            <p><strong><?= sanitize($p['reporter_name']) ?></strong> · <?= date('d M Y', strtotime($p['created_at'])) ?></p>
            <p><?= nl2br(sanitize($p['description'])) ?></p>
            <p>Status: <?= status_badge($p['status']) ?></p>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card mb-3">
    <h3 class="card-title">Requests (<?= count($requests) ?>)</h3>
    <?php if (!$requests): ?>
        <p class="text-muted">No requests.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Recipient</th><th>Qty</th><th>Status</th><th>Requested</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?= sanitize($r['recipient_name']) ?></td>
                    <td><?= (float)$r['quantity'] ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><?= time_ago($r['requested_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <h3 class="card-title">Collector Tasks (<?= count($tasks) ?>)</h3>
    <?php if (!$tasks): ?>
        <p class="text-muted">No collector tasks.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Collector</th><th>Status</th><th>Assigned</th></tr></thead>
            <tbody>
            <?php foreach ($tasks as $t): ?>
                <tr>
                    <td><?= sanitize($t['collector_name']) ?></td>
                    <td><?= status_badge($t['status']) ?></td>
                    <td><?= time_ago($t['assigned_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <h3 class="card-title">NGO Requests (<?= count($ngoRequests) ?>)</h3>
    <?php if (!$ngoRequests): ?>
        <p class="text-muted">No NGO requests.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>NGO</th><th>Status</th><th>Requested</th></tr></thead>
            <tbody>
            <?php foreach ($ngoRequests as $n): ?>
                <tr>
                    <td><?= sanitize($n['ngo_name']) ?></td>
                    <td><?= status_badge($n['status']) ?></td>
                    <td><?= time_ago($n['requested_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.details-grid { display:grid; grid-template-columns: 260px 1fr; gap:20px; }
.details-grid img { width:100%; border-radius:12px; }
@media (max-width:700px) { .details-grid { grid-template-columns: 1fr; } }
</style>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
