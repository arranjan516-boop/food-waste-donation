<?php
// donor/dashboard.php
$pageTitle = 'Donor Dashboard';
require_once __DIR__ . '/../includes/dashboard-header.php';

// Make sure this user is a donor (admin can view too)
if (current_role() !== 'donor' && current_role() !== 'admin') {
    set_flash('error', 'Access denied.');
    redirect(BASE_URL . 'index.php');
}

$uid = current_user_id();

// ---- Stat cards ----
$stats = $pdo->prepare("
    SELECT
      COUNT(*) AS total_donations,
      SUM(CASE WHEN status IN ('available','requested','accepted','collector_assigned','ngo_assigned','pickup_scheduled','picked_up','out_for_delivery','awaiting_proof','awaiting_confirmation') THEN 1 ELSE 0 END) AS active_donations,
      COALESCE(SUM(quantity),0) AS total_quantity,
      COALESCE(SUM(CASE WHEN status='completed' THEN people_served ELSE 0 END),0) AS people_served,
      SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed_donations
    FROM food_donations
    WHERE donor_id = :u
");
$stats->execute([':u' => $uid]);
$s = $stats->fetch();

$pr = $pdo->prepare("
    SELECT COUNT(*) FROM food_requests fr
    JOIN food_donations d ON d.donation_id = fr.donation_id
    WHERE d.donor_id = :u AND fr.status = 'pending'
");
$pr->execute([':u' => $uid]);
$s['pending_requests'] = (int)$pr->fetchColumn();
// ---- Recent donations (last 5) ----
$recent = $pdo->prepare("
    SELECT * FROM food_donations
    WHERE donor_id = :u
    ORDER BY created_at DESC
    LIMIT 5
");
$recent->execute([':u' => $uid]);
$recentDonations = $recent->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon-box green">📦</div>
        <div><div class="stat-value"><?= (int)$s['total_donations'] ?></div><div class="stat-label">Total Donations</div></div>
    </div>
    <div class="stat-card">
        <div class="icon-box blue">🎯</div>
        <div><div class="stat-value"><?= (int)$s['active_donations'] ?></div><div class="stat-label">Active</div></div>
    </div>
    <div class="stat-card">
        <div class="icon-box orange">🍱</div>
        <div><div class="stat-value"><?= (int)$s['total_quantity'] ?></div><div class="stat-label">Quantity Donated</div></div>
    </div>
    <div class="stat-card">
        <div class="icon-box purple">👥</div>
        <div><div class="stat-value"><?= number_format((int)$s['people_served']) ?></div><div class="stat-label">People Served</div></div>
    </div>
    <div class="stat-card">
        <div class="icon-box yellow">📨</div>
        <div><div class="stat-value"><?= (int)$s['pending_requests'] ?></div><div class="stat-label">Pending Requests</div></div>
    </div>
    <div class="stat-card">
        <div class="icon-box green">✅</div>
        <div><div class="stat-value"><?= (int)$s['completed_donations'] ?></div><div class="stat-label">Completed</div></div>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <h3>Recent Donations</h3>
        <a href="<?= BASE_URL ?>donor/my-donations.php" class="btn btn-outline btn-sm">View All</a>
    </div>

    <?php if (!$recentDonations): ?>
        <div style="padding:40px;text-align:center">
            <p class="text-muted">You haven't made any donations yet.</p>
            <a href="<?= BASE_URL ?>donor/donate-food.php" class="btn btn-primary mt-2">Donate Food</a>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Food Photo</th>
                    <th>Food Name</th>
                    <th>Quantity</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentDonations as $d): ?>
                    <tr>
                        <td><img class="thumb" src="<?= food_photo_url($d['food_photo']) ?>" alt=""></td>
                        <td><?= sanitize($d['food_name']) ?></td>
                        <td><?= (int)$d['quantity'] ?> <?= sanitize($d['unit']) ?></td>
                        <td><?= sanitize($d['area'] ?: $d['city']) ?></td>
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
