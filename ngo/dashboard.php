<?php
// ngo/dashboard.php
$pageTitle = 'NGO Dashboard';
require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/location-functions.php';

if (current_role() !== 'ngo' && current_role() !== 'admin') {
    set_flash('error', 'Access denied.');
    redirect(BASE_URL . 'index.php');
}

$uid = current_user_id();

// ---- Stats ----
$stats = $pdo->prepare("
    SELECT
      (SELECT COUNT(*) FROM ngo_requests WHERE ngo_id = :u1 AND status='pending')  AS pending_requests,
      (SELECT COUNT(*) FROM ngo_requests WHERE ngo_id = :u2 AND status='accepted') AS accepted_requests,
      (SELECT COALESCE(SUM(d.people_served),0) FROM ngo_requests nr
        JOIN food_donations d ON d.donation_id = nr.donation_id
        WHERE nr.ngo_id = :u3 AND nr.status='accepted') AS food_collected,
      (SELECT COUNT(*) FROM ngo_requests WHERE ngo_id = :u4 AND status='completed') AS completed,
      (SELECT COUNT(*) FROM food_donations WHERE status IN ('requested','accepted')
        AND delivery_preference IN ('ngo','any')) AS available_donations
");
$stats->execute([':u1' => $uid, ':u2' => $uid, ':u3' => $uid, ':u4' => $uid]);
$s = $stats->fetch();

// ---- Nearby available donations (NGO-eligible) ----
$me = $pdo->prepare("SELECT latitude, longitude FROM users WHERE user_id = :u");
$me->execute([':u' => $uid]);
$me = $me->fetch();
$myLat = $me['latitude']  !== null ? (float)$me['latitude']  : null;
$myLng = $me['longitude'] !== null ? (float)$me['longitude'] : null;

$all = $pdo->query("
    SELECT d.*, u.name AS donor_name,
      (SELECT COUNT(*) FROM ngo_requests nr WHERE nr.donation_id = d.donation_id AND nr.status IN ('accepted','completed')) AS ngo_taken
    FROM food_donations d
    JOIN users u ON u.user_id = d.donor_id
    WHERE d.status IN ('available','requested','accepted')
      AND d.delivery_preference IN ('ngo','any')
      AND (d.best_before IS NULL OR d.best_before > NOW())
    ORDER BY d.created_at DESC
    LIMIT 40
")->fetchAll();

$nearby = [];
foreach ($all as $d) {
    if ((int)$d['ngo_taken'] > 0) continue;
    if ($myLat !== null && $d['latitude'] !== null) {
        $km = haversine_km($myLat, $myLng, (float)$d['latitude'], (float)$d['longitude']);
        if ($km === null || $km > MATCH_RADIUS_KM) continue;
        $d['distance_km'] = round($km, 2);
    }
    $nearby[] = $d;
}
usort($nearby, fn($a,$b) => ($a['distance_km'] ?? 0) <=> ($b['distance_km'] ?? 0));
$preview = array_slice($nearby, 0, 5);
?>

<div class="stats-grid">
    <div class="stat-card"><div class="icon-box green">🍱</div>
        <div><div class="stat-value"><?= (int)$s['available_donations'] ?></div><div class="stat-label">Available</div></div></div>
    <div class="stat-card"><div class="icon-box yellow">⏳</div>
        <div><div class="stat-value"><?= (int)$s['pending_requests'] ?></div><div class="stat-label">Pending</div></div></div>
    <div class="stat-card"><div class="icon-box blue">👍</div>
        <div><div class="stat-value"><?= (int)$s['accepted_requests'] ?></div><div class="stat-label">Accepted</div></div></div>
    <div class="stat-card"><div class="icon-box orange">👥</div>
        <div><div class="stat-value"><?= number_format((int)$s['food_collected']) ?></div><div class="stat-label">People Served</div></div></div>
    <div class="stat-card"><div class="icon-box purple">✅</div>
        <div><div class="stat-value"><?= (int)$s['completed'] ?></div><div class="stat-label">Completed</div></div></div>
</div>

<div class="flex-between mb-2">
    <h3>Nearby Donations <span class="text-muted" style="font-size:14px">(within <?= MATCH_RADIUS_KM ?> KM)</span></h3>
    <a href="<?= BASE_URL ?>ngo/available-donations.php" class="btn btn-outline btn-sm">View All</a>
</div>

<?php if (!$preview): ?>
    <div class="empty-state">
        <div class="empty-icon">🏢</div>
        <h3>No nearby donations</h3>
        <p>New NGO-eligible donations will appear here.</p>
    </div>
<?php else: ?>
    <div class="table-card">
        <table class="table">
            <thead><tr><th>Food</th><th>Donor</th><th>Location</th><th>Distance</th><th>People</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($preview as $d): ?>
                <tr>
                    <td class="flex gap-1" style="align-items:center">
                        <img class="thumb" src="<?= food_photo_url($d['food_photo']) ?>" alt="">
                        <div>
                            <strong><?= sanitize($d['food_name']) ?></strong><br>
                            <small class="text-muted"><?= sanitize($d['food_type']) ?></small>
                        </div>
                    </td>
                    <td><?= sanitize($d['donor_name']) ?></td>
                    <td><?= sanitize($d['area'] ?: $d['city']) ?></td>
                    <td><?= isset($d['distance_km']) ? number_format($d['distance_km'],1).' KM' : '—' ?></td>
                    <td><?= (int)$d['people_served'] ?></td>
                    <td><a href="<?= BASE_URL ?>ngo/donation-details.php?id=<?= (int)$d['donation_id'] ?>" class="btn btn-primary btn-sm">Open</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
