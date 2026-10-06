<?php
// admin/collector-tasks/index.php
$pageTitle = 'Collector Tasks';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$rows = $pdo->query("
    SELECT ct.*, d.food_name, u.name AS collector_name
    FROM collector_tasks ct
    JOIN food_donations d ON d.donation_id = ct.donation_id
    JOIN users u ON u.user_id = ct.collector_id
    ORDER BY ct.assigned_at DESC LIMIT 200
")->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>Collector Tasks (<?= count($rows) ?>)</h3></div>
    <table class="table">
        <thead><tr><th>Food</th><th>Collector</th><th>Status</th><th>Assigned</th><th>Delivered</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $t): ?>
            <tr>
                <td><?= sanitize($t['food_name']) ?></td>
                <td><?= sanitize($t['collector_name']) ?></td>
                <td><?= status_badge($t['status']) ?></td>
                <td><?= time_ago($t['assigned_at']) ?></td>
                <td><?= $t['delivery_time'] ? date('d M Y', strtotime($t['delivery_time'])) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
