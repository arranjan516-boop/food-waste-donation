<?php
// ngo/donation-details.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification-functions.php';

require_login();
if (current_role() !== 'ngo' && current_role() !== 'admin') {
    set_flash('error', 'Access denied.');
    redirect(BASE_URL . 'index.php');
}

$uid = current_user_id();
$donationId = int_get('id');

$stmt = $pdo->prepare("
    SELECT d.*, u.name AS donor_name, u.phone AS donor_phone
    FROM food_donations d
    JOIN users u ON u.user_id = d.donor_id
    WHERE d.donation_id = :id
");
$stmt->execute([':id' => $donationId]);
$d = $stmt->fetch();
if (!$d) { set_flash('error', 'Donation not found.'); redirect(BASE_URL . 'ngo/available-donations.php'); }

// Existing NGO assignment?
$existing = $pdo->prepare("
    SELECT nr.*, u.name AS ngo_name
    FROM ngo_requests nr
    JOIN users u ON u.user_id = nr.ngo_id
    WHERE nr.donation_id = :d AND nr.status IN ('accepted','completed')
    LIMIT 1
");
$existing->execute([':d' => $donationId]);
$existingNgo = $existing->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post('action');

    if ($action === 'accept') {
        if ($existingNgo) {
            $errors[] = 'This donation was already accepted by ' . $existingNgo['ngo_name'] . '.';
        } else {
            try {
                $pdo->beginTransaction();

                $lock = $pdo->prepare("SELECT status FROM food_donations WHERE donation_id = :d FOR UPDATE");
                $lock->execute([':d' => $donationId]);
                $live = $lock->fetch();
                if (!$live || !in_array($live['status'], ['available','requested','accepted'])) {
                    throw new Exception('This donation is no longer available.');
                }

                $pdo->prepare("INSERT INTO ngo_requests (donation_id, ngo_id, status, accepted_at)
                               VALUES (:d, :n, 'accepted', NOW())")
                    ->execute([':d' => $donationId, ':n' => $uid]);
                $ngoReqId = (int)$pdo->lastInsertId();

                $pdo->prepare("UPDATE food_donations SET status='ngo_assigned' WHERE donation_id=:d")
                    ->execute([':d' => $donationId]);

                $pdo->prepare("INSERT INTO donation_history (donation_id, old_status, new_status, changed_by)
                               VALUES (:d, :old, 'ngo_assigned', :u)")
                    ->execute([':d' => $donationId, ':old' => $live['status'], ':u' => $uid]);

                $pdo->commit();

                notify($pdo, $d['donor_id'], '🏢 NGO Accepted Donation',
                    current_user()['name'] . ' will handle "' . $d['food_name'] . '".',
                    'ngo', $donationId);
                notify_admins($pdo, '🏢 NGO Accepted Donation',
                    current_user()['name'] . ' accepted donation #' . $donationId,
                    'ngo', $donationId);

                set_flash('success', 'Donation accepted!');
                redirect(BASE_URL . 'ngo/collection-status.php?id=' . $ngoReqId);

            } catch (Exception $ex) {
                $pdo->rollBack();
                $errors[] = $ex->getMessage();
            }
        }
    }

    if ($action === 'reject') {
        set_flash('info', 'Skipped.');
        redirect(BASE_URL . 'ngo/available-donations.php');
    }
}

$pageTitle = 'Donation Details';
require_once __DIR__ . '/../includes/dashboard-header.php';
?>

<?php if ($errors): ?>
    <div class="toast toast-error"><?php foreach ($errors as $e): ?><?= sanitize($e) ?><br><?php endforeach; ?></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="flex-between mb-2">
        <h2><?= sanitize($d['food_name']) ?></h2>
        <?= status_badge($d['status']) ?>
    </div>

    <div class="details-grid">
        <img src="<?= food_photo_url($d['food_photo']) ?>" alt="">
        <div>
            <p><strong>Donor:</strong> <?= sanitize($d['donor_name']) ?></p>
            <p><strong>Contact:</strong> <?= sanitize($d['donor_phone'] ?: '—') ?></p>
            <p><strong>Pickup:</strong> <?= sanitize($d['address']) ?>, <?= sanitize($d['area']) ?>, <?= sanitize($d['city']) ?></p>
            <p><strong>Category / Type:</strong> <?= sanitize($d['food_category']) ?> · <?= sanitize($d['food_type']) ?></p>
            <p><strong>Quantity:</strong> <?= (float)$d['quantity'] ?> <?= sanitize($d['unit']) ?></p>
            <p><strong>People served:</strong> <?= (int)$d['people_served'] ?></p>
            <p><strong>Best before:</strong> <?= $d['best_before'] ? date('d M Y, h:i A', strtotime($d['best_before'])) : '—' ?></p>
        </div>
    </div>
</div>

<?php if ($existingNgo && $existingNgo['ngo_id'] != $uid): ?>
    <div class="card">
        <div class="toast toast-info">
            🔒 This donation was already accepted by <?= sanitize($existingNgo['ngo_name']) ?>.
        </div>
        <a href="<?= BASE_URL ?>ngo/available-donations.php" class="btn btn-outline">← Back</a>
    </div>

<?php elseif ($existingNgo && $existingNgo['ngo_id'] == $uid): ?>
    <div class="card">
        <div class="toast toast-success">✅ Your NGO has accepted this donation.</div>
        <a href="<?= BASE_URL ?>ngo/collection-status.php?id=<?= (int)$existingNgo['ngo_request_id'] ?>" class="btn btn-primary">Continue to Collection</a>
    </div>

<?php else: ?>
    <div class="card">
        <h3 class="card-title">Actions</h3>
        <form method="post" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="accept">
            <button class="btn btn-primary btn-lg" data-confirm="Accept this donation?">✅ Accept Donation</button>
        </form>
        <form method="post" style="display:inline;margin-left:8px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reject">
            <button class="btn btn-outline btn-lg">Skip</button>
        </form>
    </div>
<?php endif; ?>

<style>
.details-grid { display:grid; grid-template-columns: 260px 1fr; gap:20px; }
.details-grid img { width:100%; border-radius:12px; }
@media (max-width: 700px) { .details-grid { grid-template-columns: 1fr; } }
</style>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
