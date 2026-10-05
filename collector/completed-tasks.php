<?php
// collector/completed-tasks.php
$pageTitle = 'Completed Tasks';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();
$stmt = $pdo->prepare("
    SELECT ct.*, d.food_name, d.food_photo, u.name AS donor_name
    FROM collector_tasks ct
    JOIN food_donations d ON d.donation_id = ct.donation_id
    JOIN users u ON u.user_id = d.donor_id
    WHERE ct.collector_id = :u
      AND ct.status IN ('delivered','completed')
    ORDER BY ct.delivery_time DESC
");
$stmt->execute([':u' => $uid]);
$rows = $stmt->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>Completed Tasks (<?= count($rows) ?>)</h3></div>
    <?php if (!$rows): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No completed tasks yet.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Food</th><th>Donor</th><th>Status</th><th>Delivered</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $t): ?>
                <tr>
                    <td><?= sanitize($t['food_name']) ?></td>
                    <td><?= sanitize($t['donor_name']) ?></td>
                    <td><?= status_badge($t['status']) ?></td>
                    <td><?= $t['delivery_time'] ? date('d M Y, h:i A', strtotime($t['delivery_time'])) : '—' ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>collector/active-delivery.php?id=<?= (int)$t['task_id'] ?>" class="btn btn-outline btn-sm">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
