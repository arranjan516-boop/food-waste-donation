<?php
// recipient/request-food.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification-functions.php';

require_login();
$uid = current_user_id();
$donationId = int_get('id');
$errors = [];

// Load donation
$stmt = $pdo->prepare("
    SELECT d.*, u.name AS donor_name
    FROM food_donations d
    JOIN users u ON u.user_id = d.donor_id
    WHERE d.donation_id = :id
");
$stmt->execute([':id' => $donationId]);
$d = $stmt->fetch();

if (!$d) { set_flash('error', 'Donation not found.'); redirect(BASE_URL . 'recipient/available-food.php'); }

// --- Handle POST BEFORE output ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $qty = (float)post('quantity');
    $deliveryPref = post('delivery_preference', 'collector');
    $contact = post('contact_number');
    $address = post('delivery_address');
    $message = post('message');

    if ($qty <= 0) $errors[] = 'Quantity must be greater than 0.';
    if ($qty > (float)$d['quantity']) $errors[] = 'Quantity exceeds available.';
    if (!$d['allow_partial_request'] && abs($qty - (float)$d['quantity']) > 0.001) {
        $errors[] = 'This donation does not allow partial requests — request the full quantity.';
    }
    if ($contact === '') $errors[] = 'Contact number is required.';

    // Already requested?
    $check = $pdo->prepare("SELECT request_id FROM food_requests WHERE donation_id = :d AND recipient_id = :u LIMIT 1");
    $check->execute([':d' => $donationId, ':u' => $uid]);
    if ($check->fetch()) $errors[] = 'You have already requested this donation.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Re-lock the donation row
            $lock = $pdo->prepare("SELECT status, quantity FROM food_donations WHERE donation_id = :d FOR UPDATE");
            $lock->execute([':d' => $donationId]);
            $live = $lock->fetch();

            if (!$live || $live['status'] !== 'available') {
                throw new Exception('This donation is no longer available.');
            }
            if (!$d['allow_partial_request'] && (float)$live['quantity'] < (float)$d['quantity']) {
                throw new Exception('This donation is no longer available.');
            }
            if ($qty > (float)$live['quantity']) {
                throw new Exception('Quantity exceeds remaining available.');
            }

            // Insert request
            $ins = $pdo->prepare("
                INSERT INTO food_requests
                  (donation_id, recipient_id, quantity, message, delivery_preference, contact_number, delivery_address, status)
                VALUES
                  (:d, :u, :q, :m, :dp, :c, :a, 'pending')
            ");
            $ins->execute([
                ':d' => $donationId, ':u' => $uid, ':q' => $qty,
                ':m' => $message, ':dp' => $deliveryPref,
                ':c' => $contact, ':a' => $address,
            ]);
            $requestId = (int)$pdo->lastInsertId();

            // Update donation status
            $remaining = (float)$live['quantity'] - $qty;
            if ($d['allow_partial_request'] && $remaining > 0.001) {
                // still available for others
                $pdo->prepare("UPDATE food_donations SET quantity = :q WHERE donation_id = :d")
                    ->execute([':q' => $remaining, ':d' => $donationId]);
            } else {
                $pdo->prepare("UPDATE food_donations SET status = 'requested' WHERE donation_id = :d")
                    ->execute([':d' => $donationId]);
            }

            // History
            $pdo->prepare("INSERT INTO donation_history (donation_id, old_status, new_status, changed_by)
                           VALUES (:d, :old, :new, :u)")
                ->execute([
                    ':d' => $donationId,
                    ':old' => $live['status'],
                    ':new' => $d['allow_partial_request'] && $remaining > 0.001 ? 'available' : 'requested',
                    ':u' => $uid,
                ]);

            $pdo->commit();

            // Notifications
            notify($pdo, $d['donor_id'], '📨 New Food Request',
                current_user()['name'] . ' requested ' . $qty . ' ' . $d['unit'] . ' of "' . $d['food_name'] . '".',
                'new_request', $donationId);

            notify_admins($pdo, '📨 New Request',
                current_user()['name'] . ' requested "' . $d['food_name'] . '".',
                'new_request', $donationId);

            set_flash('success', 'Request submitted! The donor will review it shortly.');
            redirect(BASE_URL . 'recipient/my-requests.php');

        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = $ex->getMessage();
        }
    }
}

// ---- Now safe to output HTML ----
$pageTitle = 'Request Food';
require_once __DIR__ . '/../includes/dashboard-header.php';
?>

<?php if ($errors): ?>
    <div class="toast toast-error">
        <ul style="margin:0;padding-left:18px">
            <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card" style="max-width:780px">
    <div class="flex-between mb-2">
        <h2><?= sanitize($d['food_name']) ?></h2>
        <?= status_badge($d['status']) ?>
    </div>

    <div class="details-grid">
        <img src="<?= food_photo_url($d['food_photo']) ?>" alt="">
        <div>
            <p><strong>Donor:</strong> <?= sanitize($d['donor_name']) ?></p>
            <p><strong>Available:</strong> <?= (float)$d['quantity'] ?> <?= sanitize($d['unit']) ?></p>
            <p><strong>Type:</strong> <?= sanitize($d['food_type']) ?></p>
            <p><strong>Best before:</strong> <?= $d['best_before'] ? date('d M Y, h:i A', strtotime($d['best_before'])) : '—' ?></p>
            <p><strong>Pickup:</strong> <?= sanitize($d['area'] ?: $d['city']) ?></p>
        </div>
    </div>
</div>

<form method="post" class="card mt-3" style="max-width:780px" data-validate>
    <?= csrf_field() ?>

    <h3 class="card-title">Request Details</h3>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Required Quantity (<?= sanitize($d['unit']) ?>) *</label>
            <input type="number" step="0.1" min="0.1" max="<?= (float)$d['quantity'] ?>" name="quantity"
                   class="form-control" required
                   value="<?= $d['allow_partial_request'] ? '' : (float)$d['quantity'] ?>">
            <small class="text-muted">Max <?= (float)$d['quantity'] ?> <?= sanitize($d['unit']) ?></small>
        </div>
        <div class="form-group">
            <label class="form-label">Delivery Preference *</label>
            <select name="delivery_preference" class="form-control" required>
                <option value="collector">Collector Delivery</option>
                <option value="self_delivery">Self Pickup</option>
                <option value="ngo">NGO Distribution</option>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Contact Number *</label>
            <input type="text" name="contact_number" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Delivery Address</label>
            <input type="text" name="delivery_address" class="form-control">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Additional Message</label>
        <textarea name="message" class="form-control" rows="3"></textarea>
    </div>

    <button class="btn btn-primary btn-lg">🍱 Request Food</button>
    <a href="<?= BASE_URL ?>recipient/food-details.php?id=<?= (int)$d['donation_id'] ?>" class="btn btn-outline">Cancel</a>
</form>

<style>
.details-grid { display:grid; grid-template-columns: 220px 1fr; gap:20px; }
.details-grid img { width:100%; border-radius:12px; }
@media (max-width: 700px) { .details-grid { grid-template-columns: 1fr; } }
</style>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
