<?php
// ngo/pickup-schedule.php
$pageTitle = 'Pickup Schedule';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();
$stmt = $pdo->prepare("
    SELECT ps.*, d.food_name, u.name AS donor_name, u.city, u.area
    FROM pickup_schedules ps
    JOIN food_donations d ON d.donation_id = ps.donation_id
    JOIN users u ON u.user_id = d.donor_id
    WHERE ps.ngo_id = :u
    ORDER BY ps.pickup_date DESC, ps.pickup_time DESC
");
$stmt->execute([':u' => $uid]);
$rows = $stmt->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>Scheduled Pickups (<?= count($rows) ?>)</h3></div>
    <?php if (!$rows): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No pickups scheduled.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Food</th><th>Donor</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td><?= sanitize($p['food_name']) ?></td>
                    <td><?= sanitize($p['donor_name']) ?> — <?= sanitize($p['area'] ?: $p['city']) ?></td>
                    <td><?= date('d M Y', strtotime($p['pickup_date'])) ?></td>
                    <td><?= date('h:i A', strtotime($p['pickup_time'])) ?></td>
                    <td><?= status_badge($p['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
