<?php
// admin/settings/index.php
$pageTitle = 'Settings';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$counts = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM users) AS u,
      (SELECT COUNT(*) FROM food_donations) AS d,
      (SELECT COUNT(*) FROM food_requests) AS r,
      (SELECT COUNT(*) FROM collector_tasks) AS ct,
      (SELECT COUNT(*) FROM ngo_requests) AS nr,
      (SELECT COUNT(*) FROM delivery_proofs) AS dp,
      (SELECT COUNT(*) FROM notifications) AS n
")->fetch();
?>

<div class="dash-grid-2">
    <div class="card">
        <h3 class="card-title">System Info</h3>
        <p><strong>App name:</strong> <?= SITE_NAME ?></p>
        <p><strong>Base URL:</strong> <?= BASE_URL ?></p>
        <p><strong>Match radius:</strong> <?= MATCH_RADIUS_KM ?> KM</p>
        <p><strong>PHP version:</strong> <?= PHP_VERSION ?></p>
        <p><strong>Server time:</strong> <?= date('d M Y H:i:s') ?></p>
    </div>

    <div class="card">
        <h3 class="card-title">Database Row Counts</h3>
        <p><strong>Users:</strong> <?= (int)$counts['u'] ?></p>
        <p><strong>Donations:</strong> <?= (int)$counts['d'] ?></p>
        <p><strong>Food requests:</strong> <?= (int)$counts['r'] ?></p>
        <p><strong>Collector tasks:</strong> <?= (int)$counts['ct'] ?></p>
        <p><strong>NGO requests:</strong> <?= (int)$counts['nr'] ?></p>
        <p><strong>Delivery proofs:</strong> <?= (int)$counts['dp'] ?></p>
        <p><strong>Notifications:</strong> <?= (int)$counts['n'] ?></p>
    </div>
</div>

<style>.dash-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;}@media(max-width:900px){.dash-grid-2{grid-template-columns:1fr;}}</style>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
