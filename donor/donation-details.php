<?php
// donor/donation-details.php
$pageTitle = 'Donation Details';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();
$id  = int_get('id');

$stmt = $pdo->prepare("SELECT * FROM food_donations WHERE donation_id = :id AND donor_id = :u");
$stmt->execute([':id' => $id, ':u' => $uid]);
$d = $stmt->fetch();

if (!$d) { set_flash('error', 'Donation not found.'); redirect(BASE_URL . 'donor/my-donations.php'); }

// Requests for this donation
$rq = $pdo->prepare("
    SELECT fr.*, u.name AS recipient_name, u.phone AS recipient_phone, u.city, u.area
    FROM food_requests fr
    JOIN users u ON u.user_id = fr.recipient_id
    WHERE fr.donation_id = :id
    ORDER BY fr.requested_at DESC
");
$rq->execute([':id' => $id]);
$requests = $rq->fetchAll();

// Delivery proof (if any)
$pf = $pdo->prepare("SELECT dp.*, u.name AS uploader_name FROM delivery_proofs dp
                     JOIN users u ON u.user_id = dp.uploaded_by
                     WHERE dp.donation_id = :id ORDER BY dp.uploaded_at DESC LIMIT 1");
$pf->execute([':id' => $id]);
$proof = $pf->fetch();
?>

<div class="card mb-3">
    <div class="flex-between mb-2">
        <h2><?= sanitize($d['food_name']) ?></h2>
        <?= status_badge($d['status']) ?>
    </div>

    <div class="details-grid">
        <img src="<?= food_photo_url($d['food_photo']) ?>" alt="" style="width:100%;border-radius:12px">

        <div>
            <p><strong>Category:</strong> <?= sanitize($d['food_category']) ?></p>
            <p><strong>Type:</strong> <?= sanitize($d['food_type']) ?></p>
            <p><strong>Quantity:</strong> <?= (float)$d['quantity'] ?> <?= sanitize($d['unit']) ?></p>
            <p><strong>People served:</strong> <?= (int)$d['people_served'] ?></p>
            <p><strong>Best before:</strong> <?= date('d M Y, h:i A', strtotime($d['best_before'])) ?></p>
            <p><strong>Urgency:</strong> <?= ucfirst(str_replace('_',' ',$d['urgency'])) ?></p>
            <p><strong>Delivery:</strong> <?= ucwords(str_replace('_',' ',$d['delivery_preference'])) ?></p>
            <p><strong>Partial requests:</strong> <?= $d['allow_partial_request'] ? 'Allowed' : 'Not allowed' ?></p>
            <p><strong>Pickup:</strong> <?= sanitize($d['address']) ?>, <?= sanitize($d['area']) ?>, <?= sanitize($d['city']) ?> - <?= sanitize($d['pincode']) ?></p>
            <p class="text-muted">Posted <?= time_ago($d['created_at']) ?></p>
        </div>
    </div>
</div>

<h3 class="mb-2">Requests (<?= count($requests) ?>)</h3>
<div class="table-card">
    <?php if (!$requests): ?>
        <div style="padding:24px;text-align:center"><p class="text-muted">No requests yet.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Recipient</th><th>Qty</th><th>Message</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td>
                        <strong><?= sanitize($r['recipient_name']) ?></strong><br>
                        <small class="text-muted"><?= sanitize($r['recipient_phone']) ?> · <?= sanitize($r['area']) ?></small>
                    </td>
                    <td><?= (float)$r['quantity'] ?></td>
                    <td><?= sanitize($r['message']) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td>
                        <?php if ($r['status'] === 'pending'): ?>
                            <a href="<?= BASE_URL ?>donor/request-details.php?id=<?= (int)$r['request_id'] ?>" class="btn btn-primary btn-sm">Review</a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>donor/request-details.php?id=<?= (int)$r['request_id'] ?>" class="btn btn-outline btn-sm">View</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if ($proof): ?>
<div class="card mt-3">
    <h3 class="card-title">Delivery Proof</h3>
    <p><strong>Uploaded by:</strong> <?= sanitize($proof['uploader_name']) ?> (<?= sanitize($proof['uploaded_by_role']) ?>)</p>
    <p><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($proof['uploaded_at'])) ?></p>
    <?php if ($proof['delivery_note']): ?><p><strong>Note:</strong> <?= sanitize($proof['delivery_note']) ?></p><?php endif; ?>
    <img src="<?= PROOF_UPLOAD_URL . rawurlencode($proof['proof_image']) ?>" style="max-width:280px;border-radius:12px" alt="">
</div>
<?php endif; ?>

<style>
.details-grid { display: grid; grid-template-columns: 320px 1fr; gap: 24px; }
@media (max-width: 700px) { .details-grid { grid-template-columns: 1fr; } }
</style>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
