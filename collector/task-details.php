<?php
// collector/task-details.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification-functions.php';

require_login();
if (current_role() !== 'collector' && current_role() !== 'admin') {
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
if (!$d) { set_flash('error', 'Donation not found.'); redirect(BASE_URL . 'collector/available-tasks.php'); }

// Was a collector already assigned?
$existing = $pdo->prepare("SELECT ct.*, u.name AS collector_name FROM collector_tasks ct
                           JOIN users u ON u.user_id = ct.collector_id
                           WHERE ct.donation_id = :d AND ct.status IN ('accepted','pickup_started','picked_up','delivering','completed')
                           LIMIT 1");
$existing->execute([':d' => $donationId]);
$existingTask = $existing->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post('action');

    if ($action === 'accept') {
        if ($existingTask) {
            $errors[] = 'This pickup has already been accepted by ' . $existingTask['collector_name'] . '.';
        } else {
            try {
                $pdo->beginTransaction();

                // Lock the donation row + check no one accepted in the meantime
                $lock = $pdo->prepare("SELECT status FROM food_donations WHERE donation_id = :d FOR UPDATE");
                $lock->execute([':d' => $donationId]);
                $live = $lock->fetch();
                if (!$live || !in_array($live['status'], ['requested','accepted','available'])) {
                    throw new Exception('This task is no longer available.');
                }

                // Find the accepted request for this donation, if any
                $reqStmt = $pdo->prepare("SELECT request_id FROM food_requests WHERE donation_id = :d AND status='accepted' ORDER BY accepted_at DESC LIMIT 1");
                $reqStmt->execute([':d' => $donationId]);
                $req = $reqStmt->fetch();
                $requestId = $req ? (int)$req['request_id'] : null;

                // Insert collector task
                $ins = $pdo->prepare("
                    INSERT INTO collector_tasks (request_id, donation_id, collector_id, status)
                    VALUES (:r, :d, :c, 'accepted')
                ");
                $ins->execute([':r' => $requestId, ':d' => $donationId, ':c' => $uid]);
                $taskId = (int)$pdo->lastInsertId();

                // Update donation status
                $pdo->prepare("UPDATE food_donations SET status='collector_assigned' WHERE donation_id=:d")
                    ->execute([':d' => $donationId]);

                $pdo->prepare("INSERT INTO donation_history (donation_id, old_status, new_status, changed_by)
                               VALUES (:d, :old, 'collector_assigned', :u)")
                    ->execute([':d' => $donationId, ':old' => $live['status'], ':u' => $uid]);

                $pdo->commit();

                // Notifications
                notify($pdo, $d['donor_id'], '🚴 Collector Assigned',
                    current_user()['name'] . ' will collect "' . $d['food_name'] . '".',
                    'collector', $donationId);

                if ($requestId) {
                    $rr = $pdo->prepare("SELECT recipient_id FROM food_requests WHERE request_id=:r");
                    $rr->execute([':r' => $requestId]);
                    $rid = (int)$rr->fetchColumn();
                    if ($rid) {
                        notify($pdo, $rid, '🚴 Collector Assigned',
                            'A collector will bring your food soon.', 'collector', $donationId);
                    }
                }

                notify_admins($pdo, '🚴 Collector Assigned',
                    current_user()['name'] . ' accepted pickup for donation #' . $donationId,
                    'collector', $donationId);

                set_flash('success', 'Pickup accepted!');
                redirect(BASE_URL . 'collector/active-delivery.php?id=' . $taskId);

            } catch (Exception $ex) {
                $pdo->rollBack();
                $errors[] = $ex->getMessage();
            }
        }
    }

    if ($action === 'reject') {
        set_flash('info', 'Task skipped.');
        redirect(BASE_URL . 'collector/available-tasks.php');
    }
}

// ---- NOW safe to output HTML ----
$pageTitle = 'Task Details';
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
            <p><strong>Pickup:</strong> <?= sanitize($d['address']) ?>, <?= sanitize($d['area']) ?>, <?= sanitize($d['city']) ?> - <?= sanitize($d['pincode']) ?></p>
            <p><strong>Quantity:</strong> <?= (float)$d['quantity'] ?> <?= sanitize($d['unit']) ?></p>
            <p><strong>People served:</strong> <?= (int)$d['people_served'] ?></p>
            <p><strong>Urgency:</strong> <?= ucfirst(str_replace('_',' ',$d['urgency'])) ?></p>
            <p><strong>Best before:</strong> <?= $d['best_before'] ? date('d M Y, h:i A', strtotime($d['best_before'])) : '—' ?></p>
        </div>
    </div>
</div>

<?php if ($existingTask && $existingTask['collector_id'] != $uid): ?>
    <div class="card">
        <div class="toast toast-info">
            🔒 This pickup has already been accepted by <?= sanitize($existingTask['collector_name']) ?>.
        </div>
        <a href="<?= BASE_URL ?>collector/available-tasks.php" class="btn btn-outline">← Back</a>
    </div>
<?php elseif ($existingTask && $existingTask['collector_id'] == $uid): ?>
    <div class="card">
        <div class="toast toast-success">✅ You have accepted this pickup.</div>
        <a href="<?= BASE_URL ?>collector/active-delivery.php?id=<?= (int)$existingTask['task_id'] ?>" class="btn btn-primary">Continue to Active Delivery</a>
    </div>
<?php else: ?>
    <div class="card">
        <h3 class="card-title">Actions</h3>
        <form method="post" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="accept">
            <button class="btn btn-primary btn-lg" data-confirm="Accept this pickup task?">✅ Accept Pickup</button>
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
