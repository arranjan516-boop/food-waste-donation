<?php
// donor/delete-donation.php
// SOFT DELETE: sets donation status to 'cancelled'.
// Preserves all history. Notifies everyone involved.

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification-functions.php';

require_login();
if (current_role() !== 'donor' && current_role() !== 'admin') {
    set_flash('error', 'Access denied.');
    redirect(BASE_URL . 'index.php');
}

$uid = current_user_id();
$id  = int_get('id');

if (!$id) {
    set_flash('error', 'Invalid donation.');
    redirect(BASE_URL . 'donor/my-donations.php');
}

// Load donation — must belong to this donor (admins can act on any)
$sql = "SELECT d.*, u.name AS donor_name
        FROM food_donations d
        JOIN users u ON u.user_id = d.donor_id
        WHERE d.donation_id = :id";
if (current_role() !== 'admin') {
    $sql .= " AND d.donor_id = :u";
}
$stmt = $pdo->prepare($sql);
$params = [':id' => $id];
if (current_role() !== 'admin') $params[':u'] = $uid;
$stmt->execute($params);
$d = $stmt->fetch();

if (!$d) {
    set_flash('error', 'Donation not found.');
    redirect(BASE_URL . 'donor/my-donations.php');
}

// ---- Already completed/cancelled/expired? Nothing to do ----
if (in_array($d['status'], ['completed','cancelled','expired'], true)) {
    set_flash('info', 'This donation is already ' . $d['status'] . '.');
    redirect(BASE_URL . 'donor/my-donations.php');
}

// ---- Require POST + CSRF ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Please use the cancel button.');
    redirect(BASE_URL . 'donor/my-donations.php');
}
verify_csrf();

try {
    $pdo->beginTransaction();

    // 1. Soft delete: mark donation cancelled
    $pdo->prepare("UPDATE food_donations SET status = 'cancelled' WHERE donation_id = :id")
        ->execute([':id' => $id]);

    // 2. Log in history
    $pdo->prepare("INSERT INTO donation_history (donation_id, old_status, new_status, changed_by)
                   VALUES (:d, :old, 'cancelled', :u)")
        ->execute([':d' => $id, ':old' => $d['status'], ':u' => $uid]);

    // 3. Cancel any pending requests (soft)
    $pdo->prepare("UPDATE food_requests SET status = 'cancelled'
                   WHERE donation_id = :d AND status IN ('pending','accepted')")
        ->execute([':d' => $id]);

    // 4. Cancel any active collector tasks (soft)
    $pdo->prepare("UPDATE collector_tasks SET status = 'cancelled'
                   WHERE donation_id = :d AND status IN ('available','accepted','pickup_started','picked_up','delivering')")
        ->execute([':d' => $id]);

    // 5. Cancel any pending NGO requests (soft)
    $pdo->prepare("UPDATE ngo_requests SET status = 'cancelled'
                   WHERE donation_id = :d AND status IN ('pending','accepted')")
        ->execute([':d' => $id]);

    $pdo->commit();

    // ---- Notify everyone involved ----
    $msg = 'The donation "' . $d['food_name'] . '" has been cancelled by the donor.';

    // Recipients
    $recipients = $pdo->prepare("SELECT DISTINCT recipient_id FROM food_requests WHERE donation_id = :d");
    $recipients->execute([':d' => $id]);
    foreach ($recipients->fetchAll() as $r) {
        notify($pdo, (int)$r['recipient_id'], '❌ Donation Cancelled', $msg, 'cancelled', $id);
    }

    // Collectors
    $collectors = $pdo->prepare("SELECT DISTINCT collector_id FROM collector_tasks WHERE donation_id = :d");
    $collectors->execute([':d' => $id]);
    foreach ($collectors->fetchAll() as $c) {
        notify($pdo, (int)$c['collector_id'], '❌ Pickup Cancelled', $msg, 'cancelled', $id);
    }

    // NGOs
    $ngos = $pdo->prepare("SELECT DISTINCT ngo_id FROM ngo_requests WHERE donation_id = :d");
    $ngos->execute([':d' => $id]);
    foreach ($ngos->fetchAll() as $n) {
        notify($pdo, (int)$n['ngo_id'], '❌ Donation Cancelled', $msg, 'cancelled', $id);
    }

    // Admin
    notify_admins($pdo, '🚫 Donation Cancelled',
        ($d['donor_name'] ?? 'A donor') . ' cancelled "' . $d['food_name'] . '" (#' . $id . ').',
        'donation_cancelled', $id);

    set_flash('success', 'Donation cancelled. All involved users have been notified.');

} catch (Exception $ex) {
    $pdo->rollBack();
    set_flash('error', 'Could not cancel donation. Please try again.');
}

redirect(BASE_URL . 'donor/my-donations.php');
