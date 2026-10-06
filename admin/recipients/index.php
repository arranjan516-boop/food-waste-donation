<?php
// admin/recipients/index.php
$pageTitle = 'Recipients';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$kpi = $pdo->query("
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS active,
      (SELECT COUNT(*) FROM food_requests) AS requests,
      (SELECT COUNT(*) FROM food_requests WHERE status='completed') AS completed
    FROM users WHERE role='recipient'
")->fetch();

$rows = $pdo->query("
    SELECT u.*,
      (SELECT COUNT(*) FROM food_requests r WHERE r.recipient_id = u.user_id) AS request_count,
      (SELECT COUNT(*) FROM food_requests r WHERE r.recipient_id = u.user_id AND r.status='completed') AS completed_count,
      (SELECT COUNT(*) FROM food_requests r WHERE r.recipient_id = u.user_id AND r.status='pending') AS pending_count
    FROM users u
    WHERE u.role = 'recipient'
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card"><div class="icon-box green">🙋</div>
        <div><div class="stat-value"><?= (int)$kpi['total'] ?></div><div class="stat-label">Total Recipients</div></div></div>
    <div class="stat-card"><div class="icon-box blue">✅</div>
        <div><div class="stat-value"><?= (int)$kpi['active'] ?></div><div class="stat-label">Active</div></div></div>
    <div class="stat-card"><div class="icon-box orange">📨</div>
        <div><div class="stat-value"><?= (int)$kpi['requests'] ?></div><div class="stat-label">Total Requests</div></div></div>
    <div class="stat-card"><div class="icon-box purple">🏁</div>
        <div><div class="stat-value"><?= (int)$kpi['completed'] ?></div><div class="stat-label">Completed</div></div></div>
</div>

<div class="table-card">
    <div class="table-card-header"><h3>Recipients (<?= count($rows) ?>)</h3></div>
    <table class="table">
        <thead><tr><th>Photo</th><th>Name</th><th>Contact</th><th>Location</th><th>Requests</th><th>Pending</th><th>Completed</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $u): ?>
            <tr>
                <td>
                    <?php if ($u['profile_photo']): ?>
                        <img class="thumb" src="<?= PROFILE_UPLOAD_URL . rawurlencode($u['profile_photo']) ?>" alt="">
                    <?php else: ?>
                        <div class="avatar-mini"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= sanitize($u['name']) ?></td>
                <td>
                    <?= sanitize($u['email']) ?><br>
                    <small class="text-muted"><?= sanitize($u['phone'] ?: '—') ?></small>
                </td>
                <td><?= sanitize($u['area'] ?: $u['city'] ?: '—') ?></td>
                <td><strong><?= (int)$u['request_count'] ?></strong></td>
                <td><?= (int)$u['pending_count'] ?></td>
                <td><?= (int)$u['completed_count'] ?></td>
                <td><?= status_badge($u['status']) ?></td>
                <td><a href="<?= BASE_URL ?>admin/users/view.php?id=<?= (int)$u['user_id'] ?>" class="btn btn-outline btn-sm">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>.avatar-mini{width:44px;height:44px;border-radius:8px;background:var(--green);color:#fff;
    display:flex;align-items:center;justify-content:center;font-weight:600;}</style>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
