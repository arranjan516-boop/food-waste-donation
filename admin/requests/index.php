<?php
// admin/requests/index.php
$pageTitle = 'Food Requests';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$rows = $pdo->query("
    SELECT fr.*, d.food_name, u.name AS recipient_name, dr.name AS donor_name
    FROM food_requests fr
    JOIN food_donations d ON d.donation_id = fr.donation_id
    JOIN users u ON u.user_id = fr.recipient_id
    JOIN users dr ON dr.user_id = d.donor_id
    ORDER BY fr.requested_at DESC LIMIT 200
")->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>Requests (<?= count($rows) ?>)</h3></div>
    <table class="table">
        <thead><tr><th>Food</th><th>Recipient</th><th>Donor</th><th>Qty</th><th>Status</th><th>When</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= sanitize($r['food_name']) ?></td>
                <td><?= sanitize($r['recipient_name']) ?></td>
                <td><?= sanitize($r['donor_name']) ?></td>
                <td><?= (float)$r['quantity'] ?></td>
                <td><?= status_badge($r['status']) ?></td>
                <td><?= time_ago($r['requested_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
