<?php
// ngo/feedback.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification-functions.php';
require_login();
if (current_role() !== 'ngo' && current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$uid = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $donationId = int_post('donation_id');
    $toUser     = int_post('to_user');
    $rating     = max(1, min(5, int_post('rating')));
    $comment    = post('comment');

    $chk = $pdo->prepare("SELECT ngo_request_id FROM ngo_requests WHERE donation_id = :d AND ngo_id = :u AND status = 'completed'");
    $chk->execute([':d' => $donationId, ':u' => $uid]);
    if ($chk->fetch()) {
        $dup = $pdo->prepare("SELECT feedback_id FROM feedback WHERE donation_id = :d AND from_user = :u AND to_user = :t");
        $dup->execute([':d' => $donationId, ':u' => $uid, ':t' => $toUser]);
        if (!$dup->fetch()) {
            $pdo->prepare("INSERT INTO feedback (donation_id, from_user, to_user, rating, comment)
                           VALUES (:d, :u, :t, :r, :c)")
                ->execute([':d' => $donationId, ':u' => $uid, ':t' => $toUser, ':r' => $rating, ':c' => $comment]);
            notify($pdo, $toUser, '⭐ New Feedback', 'You received a ' . $rating . '-star rating.', 'feedback', $donationId);
            set_flash('success', 'Thank you.');
        } else {
            set_flash('info', 'Already submitted.');
        }
    }
    redirect(BASE_URL . 'ngo/feedback.php');
}

$rows = $pdo->prepare("
    SELECT nr.donation_id, d.food_name, d.food_photo, d.updated_at,
           d.donor_id, du.name AS donor_name,
           (SELECT COUNT(*) FROM feedback f WHERE f.donation_id = d.donation_id AND f.from_user = :u) AS given
    FROM ngo_requests nr
    JOIN food_donations d ON d.donation_id = nr.donation_id
    JOIN users du ON du.user_id = d.donor_id
    WHERE nr.ngo_id = :u AND d.status IN ('delivered','completed')
    ORDER BY d.updated_at DESC
");
$rows->execute([':u' => $uid]);
$rows = $rows->fetchAll();

$pageTitle = 'Feedback';
require_once __DIR__ . '/../includes/dashboard-header.php';
?>

<div class="table-card">
    <div class="table-card-header"><h3>Feedback on Handled Donations</h3></div>
    <?php if (!$rows): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No completed donations yet.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Food</th><th>Donor</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="flex gap-1" style="align-items:center">
                        <img class="thumb" src="<?= food_photo_url($r['food_photo']) ?>" alt="">
                        <?= sanitize($r['food_name']) ?>
                    </td>
                    <td><?= sanitize($r['donor_name']) ?></td>
                    <td><?= $r['given'] > 0 ? '<span class="badge badge-green">Given</span>' : '<span class="badge badge-yellow">Pending</span>' ?></td>
                    <td>
                        <?php if ($r['given'] == 0): ?>
                            <button class="btn btn-primary btn-sm" onclick="document.getElementById('fb<?= (int)$r['donation_id'] ?>').style.display='block'">Give Feedback</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($r['given'] == 0): ?>
                    <tr id="fb<?= (int)$r['donation_id'] ?>" style="display:none;background:var(--gray-50)">
                        <td colspan="4">
                            <form method="post" style="padding:20px;max-width:640px">
                                <?= csrf_field() ?>
                                <input type="hidden" name="donation_id" value="<?= (int)$r['donation_id'] ?>">
                                <input type="hidden" name="to_user" value="<?= (int)$r['donor_id'] ?>">
                                <div class="form-group">
                                    <label class="form-label">Rating for 🍱 <?= sanitize($r['donor_name']) ?></label>
                                    <div style="display:flex;gap:6px;font-size:22px">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <label style="cursor:pointer">
                                                <input type="radio" name="rating" value="<?= $i ?>" <?= $i===5?'checked':'' ?> required style="display:none">
                                                <span>⭐</span>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Comment</label>
                                    <textarea name="comment" class="form-control" rows="2"></textarea>
                                </div>
                                <button class="btn btn-primary btn-sm">Submit</button>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
