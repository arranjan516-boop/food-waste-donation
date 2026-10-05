<?php
// donor/request-details.php
$pageTitle = 'Request Details';
require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/notification-functions.php';

$uid = current_user_id();
$rid = int_get('id');

$stmt = $pdo->prepare("
    SELECT fr.*, d.donor_id, d.food_name, d.food_photo, d.unit, d.delivery_preference, d.status AS donation_status,
           u.name AS recipient_name, u.phone AS recipient_phone, u.email AS recipient_email, u.address AS recipient_address
    FROM food_requests fr
    JOIN food_donations d ON d.donation_id = fr.donation_id
    JOIN users u ON u.user_id = fr.recipient_id
    WHERE fr.request_id = :rid AND d.donor_id = :u
");
$stmt->execute([':rid' => $rid, ':u' => $uid]);
$r = $stmt->fetch();

if (!$r) { set_flash('error', 'Request not found.'); redirect(BASE_URL . 'donor/food-requests.php'); }

// ---- Handle actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post('action');

    if ($action === 'accept' && $r['status'] === 'pending') {
        $pdo->prepare("UPDATE food_requests SET status='accepted', accepted_at=NOW() WHERE request_id=:r")
            ->execute([':r' => $rid]);
        $pdo->prepare("UPDATE food_donations SET status='accepted' WHERE donation_id=:d")
            ->execute([':d' => $r['donation_id']]);

        // history
        $pdo->prepare("INSERT INTO donation_history (donation_id, old_status, new_status, changed_by) VALUES (:d,'requested','accepted',:u)")
            ->execute([':d' => $r['donation_id'], ':u' => $uid]);

        notify($pdo, $r['recipient_id'], '✅ Request Accepted',
               'Your request for "' . $r['food_name'] . '" has been accepted.', 'request_accepted', $r['donation_id']);
        notify_admins($pdo, '✅ Donation Accepted', 'Donor accepted request #' . $rid, 'donation', $r['donation_id']);

        set_flash('success', 'Request accepted.');
        redirect(BASE_URL . 'donor/request-details.php?id=' . $rid);
    }

    if ($action === 'reject' && $r['status'] === 'pending') {
        $pdo->prepare("UPDATE food_requests SET status='rejected' WHERE request_id=:r")
            ->execute([':r' => $rid]);

        notify($pdo, $r['recipient_id'], '❌ Request Declined',
               'Your request for "' . $r['food_name'] . '" was declined.', 'request_rejected', $r['donation_id']);

        set_flash('info', 'Request declined.');
        redirect(BASE_URL . 'donor/request-details.php?id=' . $rid);
    }

    if ($action === 'start_delivery') {
        // Only valid if donor chose self-delivery and request is accepted
        $pdo->prepare("UPDATE food_donations SET status='out_for_delivery' WHERE donation_id=:d")
            ->execute([':d' => $r['donation_id']]);

        notify($pdo, $r['recipient_id'], '🚚 Out for Delivery',
               'Your donation "' . $r['food_name'] . '" is on its way.', 'delivery', $r['donation_id']);

        set_flash('success', 'Marked out for delivery.');
        redirect(BASE_URL . 'donor/request-details.php?id=' . $rid);
    }

    if ($action === 'mark_delivered') {
        // Donor must upload delivery proof
        $proof = upload_image($_FILES['proof_image'] ?? null, PROOF_UPLOAD, PROOF_UPLOAD_URL);
        if (!$proof) {
            set_flash('error', 'Please upload a delivery proof photo.');
        } else {
            $pdo->prepare("INSERT INTO delivery_proofs
                (donation_id, request_id, uploaded_by, uploaded_by_role, proof_image, delivery_note)
                VALUES (:d,:r,:u,'donor',:img,:n)")
                ->execute([
                    ':d' => $r['donation_id'], ':r' => $rid, ':u' => $uid,
                    ':img' => $proof, ':n' => post('delivery_note'),
                ]);

            $pdo->prepare("UPDATE food_donations SET status='delivered' WHERE donation_id=:d")
                ->execute([':d' => $r['donation_id']]);
            $pdo->prepare("UPDATE food_requests SET status='completed' WHERE request_id=:r")
                ->execute([':r' => $rid]);

            notify($pdo, $r['recipient_id'], '🍱 Food Delivered',
                   'Food has been delivered. Please confirm receipt.', 'delivered', $r['donation_id']);
            notify_admins($pdo, '📷 Delivery Proof Uploaded',
                   'Donor uploaded proof for donation #' . $r['donation_id'], 'proof', $r['donation_id']);

            set_flash('success', 'Delivery recorded.');
            redirect(BASE_URL . 'donor/request-details.php?id=' . $rid);
        }
    }
}
?>

<div class="card mb-3">
    <div class="flex-between mb-2">
        <h2><?= sanitize($r['food_name']) ?></h2>
        <?= status_badge($r['status']) ?>
    </div>
    <div class="details-grid">
        <img src="<?= food_photo_url($r['food_photo']) ?>" alt="" style="width:100%;border-radius:12px">
        <div>
            <p><strong>Recipient:</strong> <?= sanitize($r['recipient_name']) ?></p>
            <p><strong>Phone:</strong> <?= sanitize($r['recipient_phone'] ?: '—') ?></p>
            <p><strong>Email:</strong> <?= sanitize($r['recipient_email']) ?></p>
            <p><strong>Requested qty:</strong> <?= (float)$r['quantity'] ?> <?= sanitize($r['unit']) ?></p>
            <p><strong>Message:</strong> <?= sanitize($r['message'] ?: '—') ?></p>
            <p class="text-muted">Requested <?= time_ago($r['requested_at']) ?></p>
        </div>
    </div>
</div>

<?php if ($r['status'] === 'pending'): ?>
    <div class="card">
        <h3 class="card-title">Actions</h3>
        <form method="post" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="accept">
            <button class="btn btn-primary">✅ Accept Request</button>
        </form>
        <form method="post" style="display:inline;margin-left:8px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reject">
            <button class="btn btn-danger" data-confirm="Reject this request?">❌ Decline</button>
        </form>
    </div>
<?php endif; ?>

<?php if ($r['status'] === 'accepted' && in_array($r['delivery_preference'], ['self_delivery','any'])): ?>
    <div class="card">
        <h3 class="card-title">Direct Delivery</h3>
        <p>You chose to deliver this yourself. When you start, tell the recipient.</p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="start_delivery">
            <button class="btn btn-primary">🚚 Start Delivery</button>
        </form>

        <hr style="margin:20px 0">

        <h4>Mark as Delivered</h4>
        <p class="text-muted">Upload a photo of the food delivered as proof.</p>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="mark_delivered">
            <div class="form-group">
                <input type="file" name="proof_image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
            </div>
            <div class="form-group">
                <textarea name="delivery_note" class="form-control" placeholder="Optional note"></textarea>
            </div>
            <button class="btn btn-primary">📷 Submit Delivery Proof</button>
        </form>
    </div>
<?php endif; ?>

<style>.details-grid { display:grid; grid-template-columns: 260px 1fr; gap:20px; } @media (max-width:700px){.details-grid{grid-template-columns:1fr}}</style>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
