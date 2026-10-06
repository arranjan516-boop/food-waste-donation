<?php
// admin/feedback/index.php
$pageTitle = 'Feedback';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$rows = $pdo->query("
    SELECT f.*, d.food_name, uf.name AS from_name, ut.name AS to_name
    FROM feedback f
    JOIN food_donations d ON d.donation_id = f.donation_id
    JOIN users uf ON uf.user_id = f.from_user
    JOIN users ut ON ut.user_id = f.to_user
    ORDER BY f.created_at DESC LIMIT 200
")->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>Feedback (<?= count($rows) ?>)</h3></div>
    <?php if (!$rows): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No feedback yet.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>From</th><th>To</th><th>Food</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $f): ?>
                <tr>
                    <td><?= sanitize($f['from_name']) ?></td>
                    <td><?= sanitize($f['to_name']) ?></td>
                    <td><?= sanitize($f['food_name']) ?></td>
                    <td><?= str_repeat('⭐', (int)$f['rating']) ?></td>
                    <td><?= sanitize($f['comment']) ?></td>
                    <td><?= time_ago($f['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
