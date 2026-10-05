<?php
// donor/my-donations.php
$pageTitle = 'My Donations';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();
$statusFilter = get('status');

$sql = "SELECT * FROM food_donations WHERE donor_id = :u";
$params = [':u' => $uid];
if ($statusFilter) { $sql .= " AND status = :st"; $params[':st'] = $statusFilter; }
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donations = $stmt->fetchAll();
?>

<div class="table-card mb-3">
    <div class="table-card-header">
        <h3>All Donations (<?= count($donations) ?>)</h3>
        <div class="flex gap-1">
            <a href="?" class="btn btn-sm <?= !$statusFilter?'btn-primary':'btn-outline' ?>">All</a>
            <a href="?status=available" class="btn btn-sm <?= $statusFilter==='available'?'btn-primary':'btn-outline' ?>">Available</a>
            <a href="?status=requested" class="btn btn-sm <?= $statusFilter==='requested'?'btn-primary':'btn-outline' ?>">Requested</a>
            <a href="?status=completed" class="btn btn-sm <?= $statusFilter==='completed'?'btn-primary':'btn-outline' ?>">Completed</a>
            <a href="<?= BASE_URL ?>donor/donate-food.php" class="btn btn-accent btn-sm">+ New</a>
        </div>
    </div>

    <?php if (!$donations): ?>
        <div style="padding:40px;text-align:center">
            <p class="text-muted">No donations found.</p>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Photo</th><th>Food</th><th>Qty</th><th>People</th>
                    <th>Location</th><th>Best Before</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donations as $d): ?>
                    <tr>
                        <td><img class="thumb" src="<?= food_photo_url($d['food_photo']) ?>" alt=""></td>
                        <td>
                            <strong><?= sanitize($d['food_name']) ?></strong><br>
                            <small class="text-muted"><?= sanitize($d['food_type']) ?> · <?= sanitize($d['food_category']) ?></small>
                        </td>
                        <td><?= (float)$d['quantity'] ?> <?= sanitize($d['unit']) ?></td>
                        <td><?= (int)$d['people_served'] ?></td>
                        <td><?= sanitize($d['area'] ?: $d['city']) ?></td>
                        <td><?= $d['best_before'] ? date('d M, H:i', strtotime($d['best_before'])) : '—' ?></td>
                        <td><?= status_badge($d['status']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>donor/donation-details.php?id=<?= (int)$d['donation_id'] ?>"
                               class="btn btn-outline btn-sm">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
