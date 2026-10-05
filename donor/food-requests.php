<?php
// donor/food-requests.php
$pageTitle = 'Food Requests';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();

$stmt = $pdo->prepare("
    SELECT fr.*, d.food_name, d.food_photo, d.unit, d.best_before,
           u.name AS recipient_name
    FROM food_requests fr
    JOIN food_donations d ON d.donation_id = fr.donation_id
    JOIN users u ON u.user_id = fr.recipient_id
    WHERE d.donor_id = :u
    ORDER BY fr.requested_at DESC
");
$stmt->execute([':u' => $uid]);
$requests = $stmt->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>All Requests (<?= count($requests) ?>)</h3></div>
    <?php if (!$requests): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No requests yet.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Food</th><th>Recipient</th><th>Qty</th><th>Status</th><th>When</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td class="flex gap-1" style="align-items:center">
                        <img class="thumb" src="<?= food_photo_url($r['food_photo']) ?>" alt="">
                        <?= sanitize($r['food_name']) ?>
                    </td>
                    <td><?= sanitize($r['recipient_name']) ?></td>
                    <td><?= (float)$r['quantity'] ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><?= time_ago($r['requested_at']) ?></td>
                    <td><a href="<?= BASE_URL ?>donor/request-details.php?id=<?= (int)$r['request_id'] ?>" class="btn btn-outline btn-sm">Open</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
