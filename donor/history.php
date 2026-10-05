<?php
// donor/history.php
$pageTitle = 'Donation History';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();

$stmt = $pdo->prepare("
    SELECT d.*, u.name AS recipient_name
    FROM food_donations d
    LEFT JOIN food_requests fr ON fr.donation_id = d.donation_id AND fr.status IN ('accepted','completed')
    LEFT JOIN users u ON u.user_id = fr.recipient_id
    WHERE d.donor_id = :u AND d.status IN ('completed','cancelled','expired','delivered')
    ORDER BY d.updated_at DESC
");
$stmt->execute([':u' => $uid]);
$rows = $stmt->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>History (<?= count($rows) ?>)</h3></div>
    <?php if (!$rows): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No history yet.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Food</th><th>Quantity</th><th>Recipient</th><th>People</th><th>Status</th><th>When</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $h): ?>
                <tr>
                    <td><?= sanitize($h['food_name']) ?></td>
                    <td><?= (float)$h['quantity'] ?> <?= sanitize($h['unit']) ?></td>
                    <td><?= sanitize($h['recipient_name'] ?: '—') ?></td>
                    <td><?= (int)$h['people_served'] ?></td>
                    <td><?= status_badge($h['status']) ?></td>
                    <td><?= date('d M Y', strtotime($h['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
