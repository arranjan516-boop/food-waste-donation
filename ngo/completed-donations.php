<?php
// ngo/completed-donations.php
$pageTitle = 'Completed Donations';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();
$stmt = $pdo->prepare("
    SELECT nr.*, d.food_name, d.people_served, d.unit, d.quantity,
           u.name AS donor_name,
           dp.proof_image, dp.uploaded_at
    FROM ngo_requests nr
    JOIN food_donations d ON d.donation_id = nr.donation_id
    JOIN users u ON u.user_id = d.donor_id
    LEFT JOIN delivery_proofs dp ON dp.donation_id = d.donation_id AND dp.uploaded_by_role = 'ngo'
    WHERE nr.ngo_id = :u AND d.status IN ('delivered','completed')
    ORDER BY d.updated_at DESC
");
$stmt->execute([':u' => $uid]);
$rows = $stmt->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>Completed Donations (<?= count($rows) ?>)</h3></div>
    <?php if (!$rows): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No completed donations yet.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Food</th><th>Donor</th><th>Quantity</th><th>People</th><th>Proof</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= sanitize($r['food_name']) ?></td>
                    <td><?= sanitize($r['donor_name']) ?></td>
                    <td><?= (float)$r['quantity'] ?> <?= sanitize($r['unit']) ?></td>
                    <td><?= (int)$r['people_served'] ?></td>
                    <td>
                        <?php if ($r['proof_image']): ?>
                            <a href="<?= PROOF_UPLOAD_URL . rawurlencode($r['proof_image']) ?>" target="_blank" class="btn btn-outline btn-sm">View</a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= status_badge($r['donation_status'] ?? 'delivered') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
