<?php
// admin/collectors/index.php
$pageTitle = 'Collectors';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$kpi = $pdo->query("
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS active,
      (SELECT COUNT(*) FROM collector_tasks) AS tasks,
      (SELECT COUNT(*) FROM collector_tasks WHERE status IN ('delivered','completed')) AS completed
    FROM users WHERE role='collector'
")->fetch();

$rows = $pdo->query("
    SELECT u.*,
      (SELECT COUNT(*) FROM collector_tasks ct WHERE ct.collector_id = u.user_id) AS task_count,
      (SELECT COUNT(*) FROM collector_tasks ct WHERE ct.collector_id = u.user_id AND ct.status IN ('delivered','completed')) AS completed_count,
      (SELECT COUNT(*) FROM collector_tasks ct WHERE ct.collector_id = u.user_id AND ct.status IN ('accepted','pickup_started','picked_up','delivering')) AS active_count
    FROM users u
    WHERE u.role = 'collector'
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card"><div class="icon-box green">🚴</div>
        <div><div class="stat-value"><?= (int)$kpi['total'] ?></div><div class="stat-label">Total Collectors</div></div></div>
    <div class="stat-card"><div class="icon-box blue">✅</div>
        <div><div class="stat-value"><?= (int)$kpi['active'] ?></div><div class="stat-label">Active</div></div></div>
    <div class="stat-card"><div class="icon-box orange">📋</div>
        <div><div class="stat-value"><?= (int)$kpi['tasks'] ?></div><div class="stat-label">Total Tasks</div></div></div>
    <div class="stat-card"><div class="icon-box purple">🏁</div>
        <div><div class="stat-value"><?= (int)$kpi['completed'] ?></div><div class="stat-label">Completed</div></div></div>
</div>

<div class="table-card">
    <div class="table-card-header"><h3>Collectors (<?= count($rows) ?>)</h3></div>
    <table class="table">
        <thead><tr><th>Photo</th><th>Name</th><th>Contact</th><th>Location</th><th>Total Tasks</th><th>Active</th><th>Completed</th><th>Status</th><th></th></tr></thead>
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
                <td><strong><?= (int)$u['task_count'] ?></strong></td>
                <td><?= (int)$u['active_count'] ?></td>
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
