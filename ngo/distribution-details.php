<?php
session_start();

require_once "../config/database.php";
require_once "../includes/ngo-layout.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$ngo_id = (int)$_SESSION['user_id'];
$delivery_id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT
        dl.*,
        fd.food_name,
        fd.food_category,
        fd.description,
        fd.quantity,
        fd.unit,
        fd.food_photo,
        fd.city,
        fd.area,
        fd.address
    FROM deliveries dl

    INNER JOIN food_donations fd
        ON fd.donation_id = dl.donation_id

    WHERE dl.delivery_id = ?
    AND dl.ngo_id = ?

    LIMIT 1
");

$stmt->bind_param("ii", $delivery_id, $ngo_id);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: distribution.php");
    exit;
}

ngo_header("Distribution Details");
?>

<div class="ngo-content">

    <div class="ngo-page-header">

        <div>
            <h1>Distribution Details</h1>
            <p>Complete information about this food donation.</p>
        </div>

        <a
            href="distribution.php"
            class="ngo-btn ngo-btn-outline">
            ← Back
        </a>

    </div>

    <section class="ngo-section">

        <div class="ngo-detail-grid">

            <div>

                <?php
                $photo = !empty($row['food_photo'])
                    ? "../uploads/food/" . htmlspecialchars($row['food_photo'])
                    : "../assets/images/no-food.png";
                ?>

                <img
                    src="<?= $photo ?>"
                    class="ngo-detail-image"
                    alt="Food">

            </div>

            <div>

                <h2><?= htmlspecialchars($row['food_name']) ?></h2>

                <span class="ngo-badge">
                    <?= htmlspecialchars($row['status']) ?>
                </span>

                <p>
                    <?= htmlspecialchars($row['description'] ?? '') ?>
                </p>

                <div class="ngo-info-list">

                    <div>
                        <strong>Category</strong>
                        <span><?= htmlspecialchars($row['food_category'] ?? '-') ?></span>
                    </div>

                    <div>
                        <strong>Quantity</strong>
                        <span>
                            <?= htmlspecialchars($row['quantity']) ?>
                            <?= htmlspecialchars($row['unit'] ?? '') ?>
                        </span>
                    </div>

                    <div>
                        <strong>Location</strong>
                        <span>
                            <?= htmlspecialchars($row['area'] ?? '') ?>,
                            <?= htmlspecialchars($row['city'] ?? '') ?>
                        </span>
                    </div>

                    <div>
                        <strong>Delivery Method</strong>
                        <span><?= htmlspecialchars($row['method']) ?></span>
                    </div>

                    <div>
                        <strong>Delivered At</strong>
                        <span><?= htmlspecialchars($row['delivered_at'] ?? 'Not delivered') ?></span>
                    </div>

                </div>

            </div>

        </div>

    </section>

</div>

<?php ngo_footer(); ?>
