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
        dl.donation_id,
        dl.request_id,
        dl.method,
        dl.status,
        dl.confirmed_at,
        dl.delivered_at,
        dl.created_at,

        fd.food_name,
        fd.food_category,
        fd.quantity,
        fd.unit,
        fd.food_photo,
        fd.city,
        fd.area

    FROM deliveries dl

    INNER JOIN food_donations fd
        ON fd.donation_id = dl.donation_id

    WHERE dl.ngo_id = ?

    ORDER BY dl.delivery_id DESC
");

$stmt->bind_param("i", $ngo_id);
$stmt->execute();

$donations = $stmt->get_result();

ngo_header("My Donations");
?>

<div class="ngo-content">

    <div class="ngo-page-header">

        <div>
            <h1>My Donations</h1>
            <p>Donations accepted and managed by your NGO.</p>
        </div>

        <a
            href="available-donations.php"
            class="ngo-btn ngo-btn-primary">
            + Find More
        </a>

    </div>

    <?php if (isset($_SESSION['ngo_success'])): ?>

        <div class="ngo-alert ngo-alert-success">
            <?= htmlspecialchars($_SESSION['ngo_success']) ?>
        </div>

        <?php unset($_SESSION['ngo_success']); ?>

    <?php endif; ?>

    <?php if (isset($_SESSION['ngo_error'])): ?>

        <div class="ngo-alert ngo-alert-danger">
            <?= htmlspecialchars($_SESSION['ngo_error']) ?>
        </div>

        <?php unset($_SESSION['ngo_error']); ?>

    <?php endif; ?>

    <section class="ngo-section">

        <?php if ($donations->num_rows > 0): ?>

            <div class="ngo-table-wrapper">

                <table class="ngo-table">

                    <thead>
                        <tr>
                            <th>Food</th>
                            <th>Quantity</th>
                            <th>Location</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php while ($row = $donations->fetch_assoc()): ?>

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
                                📍
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

                                <a
                                    href="collection-status.php?id=<?= (int)$row['delivery_id'] ?>"
                                    class="ngo-btn ngo-btn-small">
                                    Track
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="ngo-empty">
                <div>📦</div>
                <h3>No accepted donations</h3>
                <p>Accept a donation to see it here.</p>

                <a
                    href="available-donations.php"
                    class="ngo-btn ngo-btn-primary">
                    Browse Donations
                </a>
            </div>

        <?php endif; ?>

    </section>

</div>

<?php
$stmt->close();
ngo_footer();
?>
