<?php
// ngo/my-donations.php
$pageTitle = 'My Donations';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();
$stmt = $pdo->prepare("
    SELECT nr.*, d.food_name, d.food_photo, d.unit, d.people_served, d.city, d.area,
           d.status AS donation_status, u.name AS donor_name
    FROM ngo_requests nr
    JOIN food_donations d ON d.donation_id = nr.donation_id
    JOIN users u ON u.user_id = d.donor_id
    WHERE nr.ngo_id = :u
    ORDER BY nr.requested_at DESC
");
$stmt->execute([':u' => $uid]);
$rows = $stmt->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>My Accepted Donations (<?= count($rows) ?>)</h3></div>
    <?php if (!$rows): ?>
        <div style="padding:40px;text-align:center">
            <p class="text-muted">No donations yet.</p>
            <a href="<?= BASE_URL ?>ngo/available-donations.php" class="btn btn-primary mt-2">Browse Donations</a>
        </div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Food</th><th>Donor</th><th>Location</th><th>People</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="flex gap-1" style="align-items:center">
                        <img class="thumb" src="<?= food_photo_url($r['food_photo']) ?>" alt="">
                        <?= sanitize($r['food_name']) ?>
                    </td>
                    <td><?= sanitize($r['donor_name']) ?></td>
                    <td><?= sanitize($r['area'] ?: $r['city']) ?></td>
                    <td><?= (int)$r['people_served'] ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td>
                        <?php if ($r['status'] === 'accepted'): ?>
                            <a href="<?= BASE_URL ?>ngo/collection-status.php?id=<?= (int)$r['ngo_request_id'] ?>" class="btn btn-primary btn-sm">Collection</a>
                        <?php elseif ($r['status'] === 'completed'): ?>
                            <a href="<?= BASE_URL ?>ngo/distribution.php?id=<?= (int)$r['ngo_request_id'] ?>" class="btn btn-accent btn-sm">Distribute</a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>ngo/completed-donations.php" class="btn btn-outline btn-sm">View</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
