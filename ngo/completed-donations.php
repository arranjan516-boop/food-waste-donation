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
        fd.food_name,
        fd.food_category,
        fd.quantity,
        fd.unit,
        fd.city,
        fd.area,
        dl.method,
        dl.status,
        dl.delivered_at

    FROM deliveries dl

    INNER JOIN food_donations fd
        ON fd.donation_id = dl.donation_id

    WHERE dl.ngo_id = ?
    AND dl.status = 'delivered'

    ORDER BY dl.delivered_at DESC
");

$stmt->bind_param("i", $ngo_id);
$stmt->execute();

$rows = $stmt->get_result();

ngo_header("Completed Donations");
?>

<div class="ngo-content">

    <div class="ngo-page-header">

        <div>
            <h1>Completed Donations</h1>
            <p>Successfully completed food donations.</p>
        </div>

    </div>

    <section class="ngo-section">

        <?php if ($rows->num_rows > 0): ?>

            <div class="ngo-table-wrapper">

                <table class="ngo-table">

                    <thead>
                        <tr>
                            <th>Food</th>
                            <th>Quantity</th>
                            <th>Location</th>
                            <th>Method</th>
                            <th>Completed</th>
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
                                    <?= htmlspecialchars($row['food_category'] ?? '') ?>
                                </small>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['quantity']) ?>
                                <?= htmlspecialchars($row['unit'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['area'] ?? '') ?>,
                                <?= htmlspecialchars($row['city'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['method']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['delivered_at'] ?? '-') ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="ngo-empty">
                <div>🏆</div>
                <h3>No completed donations</h3>
                <p>Your completed donations will appear here.</p>
            </div>

        <?php endif; ?>

    </section>

</div>

<?php
$stmt->close();
ngo_footer();
?>
