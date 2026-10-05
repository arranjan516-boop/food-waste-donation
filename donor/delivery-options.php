<?php
// donor/delivery-options.php
$pageTitle = 'Delivery Options';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();
$stmt = $pdo->prepare("SELECT d.*, fr.status AS req_status FROM food_donations d
                       LEFT JOIN food_requests fr ON fr.donation_id = d.donation_id
                       WHERE d.donor_id = :u ORDER BY d.created_at DESC LIMIT 30");
$stmt->execute([':u' => $uid]);
$rows = $stmt->fetchAll();
?>

<div class="card mb-3">
    <h3 class="card-title">How Delivery Works</h3>
    <div class="helpers-grid">
        <div class="helper-card">
            <div class="helper-icon">🚗</div>
            <h4>Self Delivery</h4>
            <p>You deliver to the recipient. You upload the delivery proof.</p>
        </div>
        <div class="helper-card">
            <div class="helper-icon">🚴</div>
            <h4>Collector Delivery</h4>
            <p>A nearby volunteer picks up from you and delivers. They upload proof.</p>
        </div>
        <div class="helper-card">
            <div class="helper-icon">🏢</div>
            <h4>NGO Handling</h4>
            <p>An NGO collects and distributes. They upload proof per delivery.</p>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header"><h3>Your Donations &amp; Delivery Options</h3></div>
    <table class="table">
        <thead><tr><th>Food</th><th>Preference</th><th>Request</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $d): ?>
            <tr>
                <td><?= sanitize($d['food_name']) ?></td>
                <td><?= ucwords(str_replace('_',' ', $d['delivery_preference'])) ?></td>
                <td><?= $d['req_status'] ? status_badge($d['req_status']) : '—' ?></td>
                <td><?= status_badge($d['status']) ?></td>
                <td><a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>donor/donation-details.php?id=<?= (int)$d['donation_id'] ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
