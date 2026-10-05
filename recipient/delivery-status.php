<?php
// recipient/delivery-status.php
$pageTitle = 'Delivery Status';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();

$stmt = $pdo->prepare("
    SELECT fr.*, d.food_name, d.unit, d.city, d.area, u.name AS donor_name,
           c.name AS collector_name, n.name AS ngo_name
    FROM food_requests fr
    JOIN food_donations d ON d.donation_id = fr.donation_id
    JOIN users u ON u.user_id = d.donor_id
    LEFT JOIN collector_tasks ct ON ct.request_id = fr.request_id
    LEFT JOIN users c ON c.user_id = ct.collector_id
    LEFT JOIN ngo_requests nr ON nr.donation_id = d.donation_id
    LEFT JOIN users n ON n.user_id = nr.ngo_id
    WHERE fr.recipient_id = :u
      AND fr.status IN ('accepted','completed')
    ORDER BY fr.accepted_at DESC
");
$stmt->execute([':u' => $uid]);
$rows = $stmt->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3>Active &amp; Recent Deliveries</h3></div>
    <?php if (!$rows): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No active deliveries.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Food</th><th>Donor</th><th>Assigned To</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= sanitize($r['food_name']) ?></td>
                    <td><?= sanitize($r['donor_name']) ?></td>
                    <td>
                        <?php
                            if ($r['collector_name']) echo '🚴 ' . sanitize($r['collector_name']);
                            elseif ($r['ngo_name'])   echo '🏢 ' . sanitize($r['ngo_name']);
                            else                       echo '🚗 Donor';
                        ?>
                    </td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><a href="<?= BASE_URL ?>recipient/request-details.php?id=<?= (int)$r['request_id'] ?>" class="btn btn-outline btn-sm">Open</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
