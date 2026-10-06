<?php
// admin/analytics/index.php
$pageTitle = 'Analytics';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$byRole = $pdo->query("SELECT role AS label, COUNT(*) AS total FROM users GROUP BY role")->fetchAll();
$byStatus = $pdo->query("SELECT status AS label, COUNT(*) AS total FROM food_donations GROUP BY status")->fetchAll();
$topDonors = $pdo->query("
    SELECT u.name, COUNT(*) AS total, COALESCE(SUM(d.people_served),0) AS served
    FROM food_donations d JOIN users u ON u.user_id = d.donor_id
    GROUP BY u.user_id ORDER BY total DESC LIMIT 10
")->fetchAll();
$topCollectors = $pdo->query("
    SELECT u.name, COUNT(*) AS total
    FROM collector_tasks ct JOIN users u ON u.user_id = ct.collector_id
    GROUP BY u.user_id ORDER BY total DESC LIMIT 10
")->fetchAll();
?>

<div class="dash-grid-2">
    <div class="table-card">
        <div class="table-card-header"><h3>Users by Role</h3></div>
        <table class="table">
            <thead><tr><th>Role</th><th>Count</th></tr></thead>
            <tbody>
            <?php foreach ($byRole as $r): ?>
                <tr><td><?= sanitize(ucfirst($r['label'])) ?></td><td><?= (int)$r['total'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="table-card">
        <div class="table-card-header"><h3>Donations by Status</h3></div>
        <table class="table">
            <thead><tr><th>Status</th><th>Count</th></tr></thead>
            <tbody>
            <?php foreach ($byStatus as $r): ?>
                <tr><td><?= status_badge($r['label']) ?></td><td><?= (int)$r['total'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="table-card">
        <div class="table-card-header"><h3>Top Donors</h3></div>
        <table class="table">
            <thead><tr><th>Donor</th><th>Donations</th><th>People Served</th></tr></thead>
            <tbody>
            <?php foreach ($topDonors as $r): ?>
                <tr><td><?= sanitize($r['name']) ?></td><td><?= (int)$r['total'] ?></td><td><?= (int)$r['served'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="table-card">
        <div class="table-card-header"><h3>Top Collectors</h3></div>
        <table class="table">
            <thead><tr><th>Collector</th><th>Tasks</th></tr></thead>
            <tbody>
            <?php foreach ($topCollectors as $r): ?>
                <tr><td><?= sanitize($r['name']) ?></td><td><?= (int)$r['total'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>.dash-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;}@media(max-width:900px){.dash-grid-2{grid-template-columns:1fr;}}</style>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
