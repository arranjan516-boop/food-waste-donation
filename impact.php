<?php
// impact.php
$pageTitle = 'Our Impact';
require_once __DIR__ . '/includes/header.php';

$s = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM food_donations) AS total_donations,
      (SELECT COUNT(*) FROM food_donations WHERE status='completed') AS completed,
      (SELECT COALESCE(SUM(people_served),0) FROM food_donations WHERE status='completed') AS people_served,
      (SELECT COALESCE(SUM(quantity),0) FROM food_donations) AS total_quantity,
      (SELECT COUNT(*) FROM users WHERE role='donor'     AND status='active') AS donors,
      (SELECT COUNT(*) FROM users WHERE role='collector' AND status='active') AS collectors,
      (SELECT COUNT(*) FROM users WHERE role='ngo'       AND status='active') AS ngos,
      (SELECT COUNT(*) FROM users WHERE role='recipient' AND status='active') AS recipients,
      (SELECT COUNT(*) FROM delivery_proofs) AS proofs,
      (SELECT COUNT(*) FROM collector_tasks WHERE status IN ('delivered','completed')) AS collector_deliveries
")->fetch();

$byMonth = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b') AS label,
           DATE_FORMAT(created_at, '%Y-%m') AS k,
           COUNT(*) AS total
    FROM food_donations
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY k, label ORDER BY k ASC
")->fetchAll();

$byMethod = $pdo->query("
    SELECT delivery_preference AS label, COUNT(*) AS total
    FROM food_donations GROUP BY delivery_preference ORDER BY total DESC
")->fetchAll();

$byCat = $pdo->query("
    SELECT food_category AS label, COUNT(*) AS total
    FROM food_donations WHERE food_category IS NOT NULL AND food_category<>''
    GROUP BY food_category ORDER BY total DESC LIMIT 8
")->fetchAll();

$methodLabels = ['self_delivery'=>'Self','collector'=>'Collector','ngo'=>'NGO','any'=>'Any'];
$colors = ['#2E7D32','#F57C00','#FBC02D','#1976D2','#7B1FA2','#D32F2F','#00897B','#5D4037'];
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Our Impact</h1>
        <p class="section-sub">Live numbers from real donations. Every meal counts.</p>

        <div class="impact-grid">
            <div class="impact-card"><div class="impact-num"><?= number_format((int)$s['people_served']) ?></div><div class="impact-label">People Served</div></div>
            <div class="impact-card"><div class="impact-num"><?= number_format((int)$s['total_donations']) ?></div><div class="impact-label">Total Donations</div></div>
            <div class="impact-card"><div class="impact-num"><?= number_format((int)$s['completed']) ?></div><div class="impact-label">Completed Deliveries</div></div>
            <div class="impact-card"><div class="impact-num"><?= number_format((int)$s['total_quantity']) ?></div><div class="impact-label">Quantity Donated</div></div>
            <div class="impact-card"><div class="impact-num"><?= (int)$s['donors'] ?></div><div class="impact-label">Donors</div></div>
            <div class="impact-card"><div class="impact-num"><?= (int)$s['collectors'] ?></div><div class="impact-label">Collectors</div></div>
            <div class="impact-card"><div class="impact-num"><?= (int)$s['ngos'] ?></div><div class="impact-label">NGOs</div></div>
            <div class="impact-card"><div class="impact-num"><?= (int)$s['proofs'] ?></div><div class="impact-label">Verified Proofs</div></div>
        </div>
    </div>
</section>

<section class="section" style="background:#fff">
    <div class="container">
        <h2 class="section-title">Monthly Donations</h2>
        <?php if (!$byMonth): ?>
            <p class="text-center text-muted">No data yet.</p>
        <?php else:
            $max = max(array_column($byMonth, 'total'));
        ?>
            <div class="public-bars">
                <?php foreach ($byMonth as $m): ?>
                    <div class="public-bar-col">
                        <div class="public-bar" style="height:<?= max(10, ($m['total'] / $max) * 200) ?>px">
                            <span><?= (int)$m['total'] ?></span>
                        </div>
                        <small><?= sanitize($m['label']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="impact-grid-2">
            <div class="card">
                <h3 class="card-title">Delivery Methods</h3>
                <?php if (!$byMethod): ?><p class="text-muted">No data.</p>
                <?php else:
                    $total = array_sum(array_column($byMethod, 'total'));
                ?>
                    <?php foreach ($byMethod as $i => $c): ?>
                        <div class="donut-row">
                            <span class="dot" style="background:<?= $colors[$i % count($colors)] ?>"></span>
                            <span class="label"><?= $methodLabels[$c['label']] ?? $c['label'] ?></span>
                            <span class="pct"><?= round(($c['total'] / $total) * 100) ?>%</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3 class="card-title">Top Food Categories</h3>
                <?php if (!$byCat): ?><p class="text-muted">No data.</p>
                <?php else:
                    $total = array_sum(array_column($byCat, 'total'));
                ?>
                    <?php foreach ($byCat as $i => $c): ?>
                        <div class="donut-row">
                            <span class="dot" style="background:<?= $colors[$i % count($colors)] ?>"></span>
                            <span class="label"><?= sanitize($c['label']) ?></span>
                            <span class="pct"><?= round(($c['total'] / $total) * 100) ?>%</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:var(--green-light)">
    <div class="container text-center">
        <h2 class="section-title">Join the Movement</h2>
        <p class="section-sub">Every donation prevents waste and feeds a neighbour.</p>
        <a href="<?= BASE_URL ?>register.php?role=donor" class="btn btn-primary btn-lg">Donate Food</a>
        <a href="<?= BASE_URL ?>register.php?role=collector" class="btn btn-accent btn-lg">Become a Collector</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
