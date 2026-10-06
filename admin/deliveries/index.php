<?php
// admin/deliveries/index.php
$pageTitle = 'Deliveries & Proofs';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$rows = $pdo->query("
    SELECT dp.*, d.food_name, d.city, d.area,
           u.name AS uploader_name,
           dr.name AS donor_name
    FROM delivery_proofs dp
    JOIN food_donations d ON d.donation_id = dp.donation_id
    JOIN users u ON u.user_id = dp.uploaded_by
    LEFT JOIN users dr ON dr.user_id = d.donor_id
    ORDER BY dp.uploaded_at DESC
    LIMIT 200
")->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>Delivery Proofs (<?= count($rows) ?>)</h3></div>
    <?php if (!$rows): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No delivery proofs uploaded yet.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Proof</th><th>Donation</th><th>Donor</th><th>Location</th><th>Uploaded By</th><th>When</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td><img class="thumb" src="<?= PROOF_UPLOAD_URL . rawurlencode($p['proof_image']) ?>" alt=""></td>
                    <td><?= sanitize($p['food_name']) ?></td>
                    <td><?= sanitize($p['donor_name'] ?: '—') ?></td>
                    <td><?= sanitize($p['area'] ?: $p['city']) ?></td>
                    <td><?= sanitize($p['uploader_name']) ?> <span class="badge badge-blue"><?= sanitize($p['uploaded_by_role']) ?></span></td>
                    <td><?= time_ago($p['uploaded_at']) ?></td>
                    <td><a href="view.php?id=<?= (int)$p['proof_id'] ?>" class="btn btn-outline btn-sm">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
