<?php
// donor/notifications.php
$pageTitle = 'Notifications';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();

// Mark one read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'read') {
    verify_csrf();
    mark_notification_read($pdo, int_post('id'), $uid);
    redirect(BASE_URL . 'donor/notifications.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'read_all') {
    verify_csrf();
    mark_all_notifications_read($pdo, $uid);
    redirect(BASE_URL . 'donor/notifications.php');
}

$notifs = get_notifications($pdo, $uid, 100);
?>

<div class="flex-between mb-2">
    <h3>Notifications (<?= count($notifs) ?>)</h3>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="read_all">
        <button class="btn btn-outline btn-sm">Mark all as read</button>
    </form>
</div>

<div class="table-card">
    <?php if (!$notifs): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No notifications yet.</p></div>
    <?php else: ?>
        <?php foreach ($notifs as $n): ?>
            <div style="padding:14px 20px;border-bottom:1px solid var(--gray-100);<?= !$n['is_read']?'background:#FFFDF5;':'' ?>">
                <div class="flex-between">
                    <div>
                        <strong><?= sanitize($n['title']) ?></strong>
                        <p style="margin:4px 0;color:var(--gray-700)"><?= sanitize($n['message']) ?></p>
                        <small class="text-muted"><?= time_ago($n['created_at']) ?></small>
                    </div>
                    <?php if (!$n['is_read']): ?>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="read">
                            <input type="hidden" name="id" value="<?= (int)$n['notification_id'] ?>">
                            <button class="btn btn-outline btn-sm">Mark read</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
