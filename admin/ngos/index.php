<?php
// admin/ngos/index.php
$pageTitle = 'NGOs';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$kpi = $pdo->query("
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS active,
      (SELECT COUNT(*) FROM ngo_requests) AS requests,
      (SELECT COALESCE(SUM(d.people_served),0)
        FROM ngo_requests nr JOIN food_donations d ON d.donation_id = nr.donation_id
        WHERE nr.status='completed') AS served
    FROM users WHERE role='ngo'
")->fetch();

$rows = $pdo->query("
    SELECT u.*,
      (SELECT COUNT(*) FROM ngo_requests nr WHERE nr.ngo_id = u.user_id) AS request_count,
      (SELECT COUNT(*) FROM ngo_requests nr WHERE nr.ngo_id = u.user_id AND nr.status='accepted') AS active_count,
      (SELECT COUNT(*) FROM ngo_requests nr WHERE nr.ngo_id = u.user_id AND nr.status='completed') AS completed_count,
      (SELECT COALESCE(SUM(d.people_served),0)
        FROM ngo_requests nr JOIN food_donations d ON d.donation_id = nr.donation_id
        WHERE nr.ngo_id = u.user_id AND nr.status='completed') AS people_served
    FROM users u
    WHERE u.role = 'ngo'
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card"><div class="icon-box green">🏢</div>
        <div><div class="stat-value"><?= (int)$kpi['total'] ?></div><div class="stat-label">Total NGOs</div></div></div>
    <div class="stat-card"><div class="icon-box blue">✅</div>
        <div><div class="stat-value"><?= (int)$kpi['active'] ?></div><div class="stat-label">Active</div></div></div>
    <div class="stat-card"><div class="icon-box orange">📨</div>
        <div><div class="stat-value"><?= (int)$kpi['requests'] ?></div><div class="stat-label">Donation Requests</div></div></div>
    <div class="stat-card"><div class="icon-box purple">👥</div>
        <div><div class="stat-value"><?= number_format((int)$kpi['served']) ?></div><div class="stat-label">People Served</div></div></div>
</div>

<div class="table-card">
    <div class="table-card-header"><h3>NGOs (<?= count($rows) ?>)</h3></div>
    <table class="table">
        <thead><tr><th>Logo</th><th>Organisation</th><th>Contact</th><th>Location</th><th>Requests</th><th>Active</th><th>Completed</th><th>People Served</th><th>Status</th><th></th></tr></thead>
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
                <td><?= (int)$u['active_count'] ?></td>
                <td><?= (int)$u['completed_count'] ?></td>
                <td><?= (int)$u['people_served'] ?></td>
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
