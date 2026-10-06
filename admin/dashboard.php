<?php
// admin/dashboard.php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/dashboard-header.php';

if (current_role() !== 'admin') {
    set_flash('error', 'Access denied.');
    redirect(BASE_URL . 'index.php');
}

// ---- Global stats ----
$s = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM users) AS total_users,
      (SELECT COUNT(*) FROM users WHERE role='donor'     AND status='active') AS donors,
      (SELECT COUNT(*) FROM users WHERE role='recipient' AND status='active') AS recipients,
      (SELECT COUNT(*) FROM users WHERE role='collector' AND status='active') AS collectors,
      (SELECT COUNT(*) FROM users WHERE role='ngo'       AND status='active') AS ngos,
      (SELECT COUNT(*) FROM food_donations) AS total_donations,
      (SELECT COUNT(*) FROM food_donations WHERE status NOT IN ('completed','cancelled','expired')) AS active_donations,
      (SELECT COUNT(*) FROM food_requests WHERE status='pending') AS pending_requests,
      (SELECT COUNT(*) FROM collector_tasks WHERE status IN ('accepted','pickup_started','picked_up','delivering','awaiting_proof')) AS active_deliveries,
      (SELECT COUNT(*) FROM food_donations WHERE status='completed') AS completed_donations,
      (SELECT COALESCE(SUM(quantity),0) FROM food_donations) AS total_quantity,
      (SELECT COALESCE(SUM(people_served),0) FROM food_donations WHERE status='completed') AS people_served,
      (SELECT COUNT(*) FROM problem_reports WHERE status='open') AS open_reports
")->fetch();

// ---- Monthly donations (last 6) ----
$monthly = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS label,
           DATE_FORMAT(created_at, '%Y-%m') AS sort_key,
           COUNT(*) AS total
    FROM food_donations
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY sort_key, label
    ORDER BY sort_key ASC
")->fetchAll();

// ---- Category distribution ----
$byCat = $pdo->query("
    SELECT food_category AS label, COUNT(*) AS total
    FROM food_donations
    WHERE food_category IS NOT NULL AND food_category <> ''
    GROUP BY food_category ORDER BY total DESC LIMIT 8
")->fetchAll();

// ---- Delivery method distribution ----
$byMethod = $pdo->query("
    SELECT delivery_preference AS label, COUNT(*) AS total
    FROM food_donations
    GROUP BY delivery_preference ORDER BY total DESC
")->fetchAll();
$methodLabels = ['self_delivery'=>'Self','collector'=>'Collector','ngo'=>'NGO','any'=>'Any'];
?>

<div class="stats-grid">
    <div class="stat-card"><div class="icon-box green">👥</div>
        <div><div class="stat-value"><?= (int)$s['total_users'] ?></div><div class="stat-label">Total Users</div></div></div>
    <div class="stat-card"><div class="icon-box blue">🍱</div>
        <div><div class="stat-value"><?= (int)$s['donors'] ?></div><div class="stat-label">Donors</div></div></div>
    <div class="stat-card"><div class="icon-box yellow">🙋</div>
        <div><div class="stat-value"><?= (int)$s['recipients'] ?></div><div class="stat-label">Recipients</div></div></div>
    <div class="stat-card"><div class="icon-box purple">🚴</div>
        <div><div class="stat-value"><?= (int)$s['collectors'] ?></div><div class="stat-label">Collectors</div></div></div>
    <div class="stat-card"><div class="icon-box orange">🏢</div>
        <div><div class="stat-value"><?= (int)$s['ngos'] ?></div><div class="stat-label">NGOs</div></div></div>
    <div class="stat-card"><div class="icon-box green">📦</div>
        <div><div class="stat-value"><?= (int)$s['total_donations'] ?></div><div class="stat-label">Total Donations</div></div></div>
    <div class="stat-card"><div class="icon-box blue">🎯</div>
        <div><div class="stat-value"><?= (int)$s['active_donations'] ?></div><div class="stat-label">Active Donations</div></div></div>
    <div class="stat-card"><div class="icon-box yellow">📨</div>
        <div><div class="stat-value"><?= (int)$s['pending_requests'] ?></div><div class="stat-label">Pending Requests</div></div></div>
    <div class="stat-card"><div class="icon-box purple">🚚</div>
        <div><div class="stat-value"><?= (int)$s['active_deliveries'] ?></div><div class="stat-label">Active Deliveries</div></div></div>
    <div class="stat-card"><div class="icon-box green">✅</div>
        <div><div class="stat-value"><?= (int)$s['completed_donations'] ?></div><div class="stat-label">Completed</div></div></div>
    <div class="stat-card"><div class="icon-box orange">🍱</div>
        <div><div class="stat-value"><?= (int)$s['total_quantity'] ?></div><div class="stat-label">Quantity Donated</div></div></div>
    <div class="stat-card"><div class="icon-box purple">👥</div>
        <div><div class="stat-value"><?= number_format((int)$s['people_served']) ?></div><div class="stat-label">People Served</div></div></div>
</div>

<?php if ((int)$s['open_reports'] > 0): ?>
<div class="toast toast-error mb-2">
    ⚠️ <?= (int)$s['open_reports'] ?> open problem report(s) —
    <a href="<?= BASE_URL ?>admin/reports/problems.php">Review now</a>
</div>
<?php endif; ?>

<div class="dash-grid-2">
    <div class="table-card">
        <div class="table-card-header"><h3>Monthly Donations</h3></div>
        <div style="padding:20px">
            <?php if (!$monthly): ?>
                <p class="text-muted">No data yet.</p>
            <?php else:
                $max = max(array_column($monthly, 'total'));
            ?>
                <div class="bar-chart">
                    <?php foreach ($monthly as $m): ?>
                        <div class="bar-col">
                            <div class="bar" style="height:<?= max(6, ($m['total'] / $max) * 140) ?>px">
                                <span><?= (int)$m['total'] ?></span>
                            </div>
                            <small><?= sanitize($m['label']) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header"><h3>Food Categories</h3></div>
        <div style="padding:20px">
            <?php if (!$byCat): ?>
                <p class="text-muted">No data yet.</p>
            <?php else:
                $total = array_sum(array_column($byCat, 'total'));
                $colors = ['#2E7D32','#F57C00','#FBC02D','#1976D2','#7B1FA2','#D32F2F','#00897B','#5D4037'];
            ?>
                <div class="donut-list">
                    <?php foreach ($byCat as $i => $c): ?>
                        <div class="donut-row">
                            <span class="dot" style="background:<?= $colors[$i % count($colors)] ?>"></span>
                            <span class="label"><?= sanitize($c['label']) ?></span>
                            <span class="pct"><?= round(($c['total'] / $total) * 100) ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header"><h3>Delivery Method</h3></div>
        <div style="padding:20px">
            <?php if (!$byMethod): ?>
                <p class="text-muted">No data yet.</p>
            <?php else:
                $total = array_sum(array_column($byMethod, 'total'));
                $colors = ['#2E7D32','#F57C00','#1976D2','#7B1FA2'];
            ?>
                <div class="donut-list">
                    <?php foreach ($byMethod as $i => $c): ?>
                        <div class="donut-row">
                            <span class="dot" style="background:<?= $colors[$i % count($colors)] ?>"></span>
                            <span class="label"><?= $methodLabels[$c['label']] ?? $c['label'] ?></span>
                            <span class="pct"><?= round(($c['total'] / $total) * 100) ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header"><h3>Quick Actions</h3></div>
        <div style="padding:20px;display:flex;flex-direction:column;gap:10px">
            <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-outline">👥 Manage Users</a>
            <a href="<?= BASE_URL ?>admin/donations/index.php" class="btn btn-outline">📦 Manage Donations</a>
            <a href="<?= BASE_URL ?>admin/deliveries/index.php" class="btn btn-outline">🚚 View Deliveries & Proofs</a>
            <a href="<?= BASE_URL ?>admin/reports/problems.php" class="btn btn-outline">⚠️ Problem Reports</a>
            <a href="<?= BASE_URL ?>admin/analytics/index.php" class="btn btn-outline">📈 Analytics</a>
        </div>
    </div>
</div>

<style>
.dash-grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:20px; }
@media (max-width:900px) { .dash-grid-2 { grid-template-columns: 1fr; } }

.bar-chart { display:flex; align-items:flex-end; gap:14px; height:180px; padding:10px 0; }
.bar-col { flex:1; text-align:center; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; gap:6px; }
.bar {
    width:100%; background: linear-gradient(180deg, var(--green) 0%, var(--green-dark) 100%);
    border-radius:8px 8px 0 0; position:relative; transition: all .2s;
}
.bar span { position:absolute; top:-20px; left:50%; transform:translateX(-50%);
    font-size:12px; font-weight:600; color:var(--green-dark); }
.bar-col small { font-size:11px; color:var(--gray-500); }

.donut-list { display:flex; flex-direction:column; gap:10px; }
.donut-row { display:flex; align-items:center; gap:10px; font-size:14px; }
.donut-row .dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
.donut-row .label { flex:1; color:var(--gray-700); }
.donut-row .pct { font-weight:600; color:var(--green-dark); }
</style>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
