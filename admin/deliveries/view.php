<?php
// admin/deliveries/view.php
$pageTitle = 'Delivery Proof Details';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$id = int_get('id');
$stmt = $pdo->prepare("
    SELECT dp.*, d.food_name, d.people_served, d.unit, d.quantity, d.donor_id,
           d.delivery_preference, d.status AS donation_status,
           u.name AS uploader_name,
           dr.name AS donor_name
    FROM delivery_proofs dp
    JOIN food_donations d ON d.donation_id = dp.donation_id
    JOIN users u ON u.user_id = dp.uploaded_by
    LEFT JOIN users dr ON dr.user_id = d.donor_id
    WHERE dp.proof_id = :id
");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();
if (!$p) { set_flash('error', 'Not found.'); redirect(BASE_URL . 'admin/deliveries/index.php'); }
?>

<div class="card mb-3">
    <h3 class="card-title">📷 Delivery Proof #<?= (int)$p['proof_id'] ?></h3>
    <div class="details-grid">
        <img src="<?= PROOF_UPLOAD_URL . rawurlencode($p['proof_image']) ?>" alt="">
        <div>
            <p><strong>Donation:</strong> <?= sanitize($p['food_name']) ?></p>
            <p><strong>Donor:</strong> <?= sanitize($p['donor_name'] ?: '—') ?></p>
            <p><strong>Quantity:</strong> <?= (float)$p['quantity'] ?> <?= sanitize($p['unit']) ?></p>
            <p><strong>People served:</strong> <?= (int)$p['people_served'] ?></p>
            <p><strong>Method:</strong> <?= ucwords(str_replace('_',' ',$p['delivery_preference'])) ?></p>
            <p><strong>Uploaded by:</strong> <?= sanitize($p['uploader_name']) ?> (<?= sanitize($p['uploaded_by_role']) ?>)</p>
            <p><strong>Uploaded at:</strong> <?= date('d M Y, h:i A', strtotime($p['uploaded_at'])) ?></p>
            <?php if ($p['delivery_note']): ?><p><strong>Note:</strong> <?= sanitize($p['delivery_note']) ?></p><?php endif; ?>
            <p><strong>Donation status:</strong> <?= status_badge($p['donation_status']) ?></p>
        </div>
    </div>
</div>

<a href="<?= BASE_URL ?>admin/deliveries/index.php" class="btn btn-outline">← Back</a>

<style>
.details-grid { display:grid; grid-template-columns: 320px 1fr; gap:20px; }
.details-grid img { width:100%; border-radius:12px; }
@media (max-width:700px) { .details-grid { grid-template-columns: 1fr; } }
</style>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
