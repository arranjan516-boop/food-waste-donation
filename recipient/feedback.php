<?php
// recipient/feedback.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification-functions.php';
require_login();
if (current_role() !== 'recipient' && current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$uid = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $donationId = int_post('donation_id');
    $toUser     = int_post('to_user');
    $rating     = max(1, min(5, int_post('rating')));
    $comment    = post('comment');

    $chk = $pdo->prepare("SELECT fr.request_id FROM food_requests fr
                          WHERE fr.donation_id = :d AND fr.recipient_id = :u AND fr.status = 'completed'");
    $chk->execute([':d' => $donationId, ':u' => $uid]);
    if ($chk->fetch()) {
        $dup = $pdo->prepare("SELECT feedback_id FROM feedback WHERE donation_id = :d AND from_user = :u AND to_user = :t");
        $dup->execute([':d' => $donationId, ':u' => $uid, ':t' => $toUser]);
        if (!$dup->fetch()) {
            $pdo->prepare("INSERT INTO feedback (donation_id, from_user, to_user, rating, comment)
                           VALUES (:d, :u, :t, :r, :c)")
                ->execute([':d' => $donationId, ':u' => $uid, ':t' => $toUser, ':r' => $rating, ':c' => $comment]);
            notify($pdo, $toUser, '⭐ New Feedback', 'You received a ' . $rating . '-star rating.', 'feedback', $donationId);
            set_flash('success', 'Thank you for your feedback!');
        } else {
            set_flash('info', 'You already gave feedback for this donation.');
        }
    } else {
        set_flash('error', 'Cannot submit feedback for this donation.');
    }
    redirect(BASE_URL . 'recipient/feedback.php');
}

$rows = $pdo->prepare("
    SELECT fr.donation_id, d.food_name, d.food_photo, d.updated_at,
           d.donor_id, du.name AS donor_name,
           ct.collector_id, cu.name AS collector_name,
           nr.ngo_id, nu.name AS ngo_name,
           (SELECT COUNT(*) FROM feedback f WHERE f.donation_id = d.donation_id AND f.from_user = :u1) AS given
    FROM food_requests fr
    JOIN food_donations d ON d.donation_id = fr.donation_id
    JOIN users du ON du.user_id = d.donor_id
    LEFT JOIN collector_tasks ct ON ct.donation_id = d.donation_id AND ct.status IN ('delivered','completed')
    LEFT JOIN users cu ON cu.user_id = ct.collector_id
    LEFT JOIN ngo_requests nr ON nr.donation_id = d.donation_id AND nr.status = 'completed'
    LEFT JOIN users nu ON nu.user_id = nr.ngo_id
    WHERE fr.recipient_id = :u2 AND fr.status = 'completed'
    ORDER BY d.updated_at DESC
");
$rows->execute([':u1' => $uid, ':u2' => $uid]);
$rows = $rows->fetchAll();

$pageTitle = 'Feedback';
require_once __DIR__ . '/../includes/dashboard-header.php';
?>

<div class="table-card">
    <div class="table-card-header"><h3>Rate Your Experience</h3></div>
    <?php if (!$rows): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No completed deliveries to review yet.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Food</th><th>Donor</th><th>Delivered By</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="flex gap-1" style="align-items:center">
                        <img class="thumb" src="<?= food_photo_url($r['food_photo']) ?>" alt="">
                        <?= sanitize($r['food_name']) ?>
                    </td>
                    <td><?= sanitize($r['donor_name']) ?></td>
                    <td>
                        <?php if ($r['collector_name']): ?>🚴 <?= sanitize($r['collector_name']) ?>
                        <?php elseif ($r['ngo_name']): ?>🏢 <?= sanitize($r['ngo_name']) ?>
                        <?php else: ?>🚗 Donor<?php endif; ?>
                    </td>
                    <td><?= $r['given'] > 0 ? '<span class="badge badge-green">Feedback Given</span>' : '<span class="badge badge-yellow">Pending</span>' ?></td>
                    <td>
                        <?php if ($r['given'] == 0): ?>
                            <button class="btn btn-primary btn-sm" onclick="document.getElementById('fb<?= (int)$r['donation_id'] ?>').style.display='block'">Give Feedback</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($r['given'] == 0): ?>
                    <tr id="fb<?= (int)$r['donation_id'] ?>" style="display:none;background:var(--gray-50)">
                        <td colspan="5">
                            <form method="post" class="feedback-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="donation_id" value="<?= (int)$r['donation_id'] ?>">

                                <div class="form-group">
                                    <label class="form-label">Who are you rating?</label>
                                    <select name="to_user" class="form-control" required>
                                        <option value="<?= (int)$r['donor_id'] ?>">🍱 Donor — <?= sanitize($r['donor_name']) ?></option>
                                        <?php if ($r['collector_id']): ?>
                                            <option value="<?= (int)$r['collector_id'] ?>">🚴 Collector — <?= sanitize($r['collector_name']) ?></option>
                                        <?php endif; ?>
                                        <?php if ($r['ngo_id']): ?>
                                            <option value="<?= (int)$r['ngo_id'] ?>">🏢 NGO — <?= sanitize($r['ngo_name']) ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Rating</label>
                                    <div class="star-picker">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <label><input type="radio" name="rating" value="<?= $i ?>" <?= $i===5?'checked':'' ?> required><span>⭐</span></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Comment (optional)</label>
                                    <textarea name="comment" class="form-control" rows="2"></textarea>
                                </div>

                                <button class="btn btn-primary btn-sm">Submit Feedback</button>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.star-picker { display:flex; gap:6px; font-size:22px; cursor:pointer; }
.star-picker label { cursor:pointer; }
.star-picker input { display:none; }
.star-picker label span { filter: grayscale(1); transition: filter .15s; }
.star-picker input:checked ~ span,
.star-picker label:hover span,
.star-picker label:hover ~ label span { filter: none; }
.feedback-form { padding:20px 0; max-width:640px; }
</style>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
