<?php
// collector/history.php
$pageTitle = 'History';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();
$stmt = $pdo->prepare("
    SELECT ct.*, d.food_name, u.name AS donor_name,
           r.name AS recipient_name
    FROM collector_tasks ct
    JOIN food_donations d ON d.donation_id = ct.donation_id
    JOIN users u ON u.user_id = d.donor_id
    LEFT JOIN food_requests fr ON fr.request_id = ct.request_id
    LEFT JOIN users r ON r.user_id = fr.recipient_id
    WHERE ct.collector_id = :u
    ORDER BY ct.assigned_at DESC
");
$stmt->execute([':u' => $uid]);
$rows = $stmt->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>Full History (<?= count($rows) ?>)</h3></div>
    <table class="table">
        <thead><tr><th>Food</th><th>Donor</th><th>Recipient</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $t): ?>
            <tr>
                <td><?= sanitize($t['food_name']) ?></td>
                <td><?= sanitize($t['donor_name']) ?></td>
                <td><?= sanitize($t['recipient_name'] ?: '—') ?></td>
                <td><?= status_badge($t['status']) ?></td>
                <td><?= date('d M Y', strtotime($t['assigned_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
