<?php
session_start();

require_once "../config/database.php";
require_once "../includes/ngo-layout.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$ngo_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT
        dl.delivery_id,
        dl.status,
        dl.method,
        dl.created_at,
        dl.confirmed_at,

        fd.food_name,
        fd.quantity,
        fd.unit,
        fd.city,
        fd.area,
        fd.address,

        u.name AS donor_name,
        u.phone AS donor_phone

    FROM deliveries dl

    INNER JOIN food_donations fd
        ON fd.donation_id = dl.donation_id

    LEFT JOIN users u
        ON u.user_id = fd.donor_id

    WHERE dl.ngo_id = ?

    ORDER BY dl.delivery_id DESC
");

$stmt->bind_param("i", $ngo_id);
$stmt->execute();

$rows = $stmt->get_result();

ngo_header("Pickup Schedule");
?>

<div class="ngo-content">

    <div class="ngo-page-header">
        <div>
            <h1>Pickup Schedule</h1>
            <p>Manage your upcoming food collections.</p>
        </div>
    </div>

    <section class="ngo-section">

        <?php if ($rows->num_rows > 0): ?>

            <div class="ngo-table-wrapper">

                <table class="ngo-table">

                    <thead>
                        <tr>
                            <th>Food</th>
                            <th>Donor</th>
                            <th>Pickup Location</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php while ($row = $rows->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($row['food_name']) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars($row['quantity']) ?>
                                    <?= htmlspecialchars($row['unit'] ?? '') ?>
                                </small>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['donor_name'] ?? 'Donor') ?>

                                <?php if (!empty($row['donor_phone'])): ?>
                                    <small>
                                        <?= htmlspecialchars($row['donor_phone']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['area'] ?? '') ?>,
                                <?= htmlspecialchars($row['city'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['method']) ?>
                            </td>

                            <td>
                                <span class="ngo-badge">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['created_at']) ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="ngo-empty">
                <div>🚚</div>
                <h3>No pickup schedule</h3>
                <p>Accepted donations will appear here.</p>
            </div>

        <?php endif; ?>

    </section>

</div>

<?php
$stmt->close();
ngo_footer();
?>
