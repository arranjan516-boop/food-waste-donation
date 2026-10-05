<?php
// recipient/food-details.php
$pageTitle = 'Food Details';
require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/location-functions.php';

$uid = current_user_id();
$id  = int_get('id');

$stmt = $pdo->prepare("
    SELECT d.*, u.name AS donor_name, u.phone AS donor_phone
    FROM food_donations d
    JOIN users u ON u.user_id = d.donor_id
    WHERE d.donation_id = :id
");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch();

if (!$d) { set_flash('error', 'Donation not found.'); redirect(BASE_URL . 'recipient/available-food.php'); }

// Recipient location
$me = $pdo->prepare("SELECT latitude, longitude FROM users WHERE user_id = :u");
$me->execute([':u' => $uid]);
$me = $me->fetch();

$distKm = null;
if ($me['latitude'] !== null && $d['latitude'] !== null) {
    $distKm = haversine_km((float)$me['latitude'], (float)$me['longitude'], (float)$d['latitude'], (float)$d['longitude']);
}

// Have I already requested this?
$mine = $pdo->prepare("SELECT request_id, status FROM food_requests WHERE donation_id = :d AND recipient_id = :u LIMIT 1");
$mine->execute([':d' => $id, ':u' => $uid]);
$myRequest = $mine->fetch();

// Is it still available? (status-based lock)
$isAvailable = $d['status'] === 'available'
    && (!$d['best_before'] || strtotime($d['best_before']) > time());

$lockReason = '';
if (!$isAvailable) {
    $lockReason = match ($d['status']) {
        'requested'             => 'This food has already been requested.',
        'accepted'              => 'The donor has accepted a request for this food.',
        'collector_assigned',
        'ngo_assigned',
        'pickup_scheduled',
        'picked_up',
        'out_for_delivery'      => 'This donation is already in progress.',
        'delivered',
        'completed'             => 'This donation has been completed.',
        'cancelled'             => 'This donation was cancelled.',
        'expired'               => 'This donation has expired.',
        default                 => 'This donation is no longer available.',
    };
    if ($isAvailable === false && $d['status'] === 'available' && $d['best_before'] && strtotime($d['best_before']) <= time()) {
        $lockReason = 'This donation has expired.';
    }
}
?>

<div class="card mb-3">
    <div class="flex-between mb-2">
        <h2><?= sanitize($d['food_name']) ?></h2>
        <?= status_badge($d['status']) ?>
    </div>

    <div class="details-grid">
        <img src="<?= food_photo_url($d['food_photo']) ?>" alt="">

        <div>
            <p><strong>Category:</strong> <?= sanitize($d['food_category'] ?: '—') ?></p>
            <p><strong>Type:</strong> <?= sanitize($d['food_type'] ?: '—') ?></p>
            <p><strong>Quantity:</strong> <?= (float)$d['quantity'] ?> <?= sanitize($d['unit']) ?></p>
            <p><strong>People served:</strong> <?= (int)$d['people_served'] ?></p>
            <p><strong>Best before:</strong> <?= $d['best_before'] ? date('d M Y, h:i A', strtotime($d['best_before'])) : '—' ?></p>
            <p><strong>Urgency:</strong> <?= ucfirst(str_replace('_',' ',$d['urgency'])) ?></p>
            <p><strong>Delivery:</strong> <?= ucwords(str_replace('_',' ',$d['delivery_preference'])) ?></p>
            <p><strong>Partial requests:</strong> <?= $d['allow_partial_request'] ? 'Allowed' : 'Not allowed' ?></p>
            <p><strong>Location:</strong> <?= sanitize($d['area'] ?: $d['city']) ?>
                <?php if ($distKm !== null): ?>
                    <span class="badge badge-blue">📍 <?= number_format($distKm, 1) ?> KM away</span>
                <?php endif; ?>
            </p>
            <p><strong>Donor:</strong> <?= sanitize($d['donor_name']) ?></p>
            <p class="text-muted">Posted <?= time_ago($d['created_at']) ?></p>
        </div>
    </div>
</div>

<?php if ($d['description']): ?>
    <div class="card mb-3">
        <h3 class="card-title">Description</h3>
        <p><?= nl2br(sanitize($d['description'])) ?></p>
    </div>
<?php endif; ?>

<?php if ($myRequest): ?>
    <div class="card">
        <h3 class="card-title">Your Request</h3>
        <p>Status: <?= status_badge($myRequest['status']) ?></p>
        <a href="<?= BASE_URL ?>recipient/request-details.php?id=<?= (int)$myRequest['request_id'] ?>"
           class="btn btn-outline">View Request</a>
    </div>

<?php elseif (!$isAvailable): ?>
    <div class="card">
        <div class="toast toast-info">
            🔒 <?= sanitize($lockReason ?: 'This food is no longer available.') ?>
        </div>
        <a href="<?= BASE_URL ?>recipient/available-food.php" class="btn btn-outline">← Back to Available Food</a>
    </div>

<?php else: ?>
    <div class="card">
        <h3 class="card-title">Request This Food</h3>
        <a href="<?= BASE_URL ?>recipient/request-food.php?id=<?= (int)$d['donation_id'] ?>"
           class="btn btn-primary btn-lg">🍱 Request Food</a>
    </div>
<?php endif; ?>

<style>
.details-grid { display:grid; grid-template-columns: 320px 1fr; gap:24px; }
.details-grid img { width:100%; border-radius:12px; }
@media (max-width: 700px) { .details-grid { grid-template-columns: 1fr; } }
</style>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
