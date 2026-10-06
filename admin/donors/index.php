<?php
// admin/donors/index.php
$pageTitle = 'Donors';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

// KPI cards
$kpi = $pdo->query("
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS active,
      (SELECT COUNT(*) FROM food_donations) AS donations,
      (SELECT COALESCE(SUM(people_served),0) FROM food_donations WHERE status='completed') AS served
    FROM users WHERE role='donor'
")->fetch();

// List with per-donor stats
$rows = $pdo->query("
    SELECT u.*,
      (SELECT COUNT(*) FROM food_donations d WHERE d.donor_id = u.user_id) AS donation_count,
      (SELECT COUNT(*) FROM food_donations d WHERE d.donor_id = u.user_id AND d.status='completed') AS completed_count,
      (SELECT COALESCE(SUM(people_served),0) FROM food_donations d WHERE d.donor_id = u.user_id) AS people_served
    FROM users u
    WHERE u.role = 'donor'
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card"><div class="icon-box green">🍱</div>
        <div><div class="stat-value"><?= (int)$kpi['total'] ?></div><div class="stat-label">Total Donors</div></div></div>
    <div class="stat-card"><div class="icon-box blue">✅</div>
        <div><div class="stat-value"><?= (int)$kpi['active'] ?></div><div class="stat-label">Active</div></div></div>
    <div class="stat-card"><div class="icon-box orange">📦</div>
        <div><div class="stat-value"><?= (int)$kpi['donations'] ?></div><div class="stat-label">Total Donations</div></div></div>
    <div class="stat-card"><div class="icon-box purple">👥</div>
        <div><div class="stat-value"><?= number_format((int)$kpi['served']) ?></div><div class="stat-label">People Served</div></div></div>
</div>

<div class="table-card">
    <div class="table-card-header"><h3>Donors (<?= count($rows) ?>)</h3></div>
    <table class="table">
        <thead><tr><th>Photo</th><th>Name</th><th>Contact</th><th>Location</th><th>Donations</th><th>Completed</th><th>People Served</th><th>Status</th><th></th></tr></thead>
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
                <td><strong><?= (int)$u['donation_count'] ?></strong></td>
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
