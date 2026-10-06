<?php
// ngo/distribution.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification-functions.php';

require_login();
if (current_role() !== 'ngo' && current_role() !== 'admin') {
    set_flash('error', 'Access denied.');
    redirect(BASE_URL . 'index.php');
}

$uid  = current_user_id();
$nrId = int_get('id');

$stmt = $pdo->prepare("
    SELECT nr.*,
           d.donation_id, d.food_name, d.unit, d.quantity, d.people_served,
           d.area, d.city, d.status AS donation_status, d.donor_id,
           u.name AS donor_name
    FROM ngo_requests nr
    JOIN food_donations d ON d.donation_id = nr.donation_id
    JOIN users u ON u.user_id = d.donor_id
    WHERE nr.ngo_request_id = :n AND nr.ngo_id = :u
");
$stmt->execute([':n' => $nrId, ':u' => $uid]);
$r = $stmt->fetch();
if (!$r) { set_flash('error', 'Record not found.'); redirect(BASE_URL . 'ngo/my-donations.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post('action');

    if ($action === 'distribute') {
        $proof = null;
        if (!empty($_FILES['proof_image']['name'])) {
            $proof = upload_image($_FILES['proof_image'], PROOF_UPLOAD, PROOF_UPLOAD_URL);
        }
        if (!$proof) {
            $errors[] = 'Please upload a distribution proof photo.';
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Find any accepted request for this donation (for linking)
                $req = $pdo->prepare("
                    SELECT request_id, recipient_id
                    FROM food_requests
                    WHERE donation_id = :d AND status IN ('accepted','completed')
                    ORDER BY accepted_at DESC LIMIT 1
                ");
                $req->execute([':d' => $r['donation_id']]);
                $reqRow      = $req->fetch();
                $requestId   = $reqRow ? (int)$reqRow['request_id'] : null;
                $recipientId = $reqRow ? (int)$reqRow['recipient_id'] : null;

                // 2. Create a deliveries row FIRST (needed for delivery_id FK)
                $insDel = $pdo->prepare("
                    INSERT INTO deliveries (donation_id, request_id, method, ngo_id, status, delivered_at, notes)
                    VALUES (:d, :r, 'ngo', :n, 'delivered', NOW(), :note)
                ");
                $insDel->execute([
                    ':d'    => $r['donation_id'],
                    ':r'    => $requestId,
                    ':n'    => $uid,
                    ':note' => post('delivery_note'),
                ]);
                $deliveryId = (int)$pdo->lastInsertId();

                // 3. Insert delivery proof linked to that delivery_id
                $pdo->prepare("
                    INSERT INTO delivery_proofs
                      (donation_id, request_id, delivery_id, uploaded_by, uploaded_by_role, proof_image, delivery_note)
                    VALUES (:d, :r, :del, :u, 'ngo', :img, :note)
                ")->execute([
                    ':d'   => $r['donation_id'],
                    ':r'   => $requestId,
                    ':del' => $deliveryId,
                    ':u'   => $uid,
                    ':img' => $proof,
                    ':note'=> post('delivery_note'),
                ]);

                // 4. Update donation + history
                $pdo->prepare("UPDATE food_donations SET status='delivered' WHERE donation_id=:d")
                    ->execute([':d' => $r['donation_id']]);

                $pdo->prepare("INSERT INTO donation_history (donation_id, old_status, new_status, changed_by)
                               VALUES (:d, 'picked_up', 'delivered', :u)")
                    ->execute([':d' => $r['donation_id'], ':u' => $uid]);

                $pdo->commit();

                // 5. Notifications
                notify($pdo, $r['donor_id'], '✅ Food Distributed',
                    'Your donation "' . $r['food_name'] . '" has been distributed.',
                    'distributed', $r['donation_id']);

                if ($recipientId) {
                    notify($pdo, $recipientId, '🍱 Food Delivered',
                        'Food has been delivered. Please confirm receipt.',
                        'delivered', $r['donation_id']);
                }

                notify_admins($pdo, '📷 NGO Uploaded Proof',
                    'NGO ' . current_user()['name'] . ' uploaded delivery proof for donation #' . $r['donation_id'],
                    'proof', $r['donation_id']);

                set_flash('success', 'Distribution recorded.');
                redirect(BASE_URL . 'ngo/completed-donations.php');

            } catch (Exception $ex) {
                $pdo->rollBack();
                $errors[] = 'Error: ' . $ex->getMessage();
            }
        }
    }
}

$pageTitle = 'Distribution';
require_once __DIR__ . '/../includes/dashboard-header.php';
?>

<?php if ($errors): ?>
    <div class="toast toast-error"><?php foreach ($errors as $e): ?><?= sanitize($e) ?><br><?php endforeach; ?></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="flex-between mb-2">
        <h2><?= sanitize($r['food_name']) ?></h2>
        <?= status_badge($r['donation_status']) ?>
    </div>
    <p class="text-muted">From <?= sanitize($r['donor_name']) ?> · <?= sanitize($r['area'] ?: $r['city']) ?></p>
    <p><strong>Quantity:</strong> <?= (float)$r['quantity'] ?> <?= sanitize($r['unit'] ?: 'units') ?></p>
    <p><strong>People served:</strong> <?= (int)$r['people_served'] ?></p>
</div>

<div class="card">
    <h3 class="card-title">📷 Upload Distribution Proof</h3>
    <p class="text-muted mb-2">Required — take a photo of the food being distributed.</p>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="distribute">

        <div class="form-group">
            <label class="form-label">Distribution Photo *</label>
            <input type="file" name="proof_image" class="form-control" required accept="image/jpeg,image/png,image/webp"
                   onchange="previewImage(this, document.getElementById('proof-preview'))">
            <img id="proof-preview" style="display:none;max-width:260px;margin-top:10px;border-radius:10px">
        </div>

        <div class="form-group">
            <label class="form-label">Note (optional)</label>
            <textarea name="delivery_note" class="form-control" rows="2" placeholder="e.g. Distributed to 50 people at the shelter."></textarea>
        </div>

        <button class="btn btn-primary btn-lg">Submit Distribution</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
