<?php
// admin/ngo-requests/index.php
$pageTitle = 'NGO Requests';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$rows = $pdo->query("
    SELECT nr.*, d.food_name, u.name AS ngo_name
    FROM ngo_requests nr
    JOIN food_donations d ON d.donation_id = nr.donation_id
    JOIN users u ON u.user_id = nr.ngo_id
    ORDER BY nr.requested_at DESC LIMIT 200
")->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>NGO Requests (<?= count($rows) ?>)</h3></div>
    <table class="table">
        <thead><tr><th>Food</th><th>NGO</th><th>Status</th><th>Requested</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= sanitize($r['food_name']) ?></td>
                <td><?= sanitize($r['ngo_name']) ?></td>
                <td><?= status_badge($r['status']) ?></td>
                <td><?= time_ago($r['requested_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
