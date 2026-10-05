<?php
// ngo/history.php
$pageTitle = 'History';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();
$stmt = $pdo->prepare("
    SELECT nr.*, d.food_name, d.people_served, u.name AS donor_name
    FROM ngo_requests nr
    JOIN food_donations d ON d.donation_id = nr.donation_id
    JOIN users u ON u.user_id = d.donor_id
    WHERE nr.ngo_id = :u
    ORDER BY nr.requested_at DESC
");
$stmt->execute([':u' => $uid]);
$rows = $stmt->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>Full History (<?= count($rows) ?>)</h3></div>
    <table class="table">
        <thead><tr><th>Food</th><th>Donor</th><th>People</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= sanitize($r['food_name']) ?></td>
                <td><?= sanitize($r['donor_name']) ?></td>
                <td><?= (int)$r['people_served'] ?></td>
                <td><?= status_badge($r['status']) ?></td>
                <td><?= date('d M Y', strtotime($r['requested_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
