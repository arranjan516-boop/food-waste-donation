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

if ($delivery_id <= 0) {
    header("Location: my-donations.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT
        dl.*,
        fd.food_name,
        fd.food_category,
        fd.quantity,
        fd.unit,
        fd.city,
        fd.area,
        fd.address,
        fd.food_photo
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
    header("Location: my-donations.php");
    exit;
}

ngo_header("Collection Status");
?>

<div class="ngo-content">

    <div class="ngo-page-header">

        <div>
            <h1>Collection Status</h1>
            <p>Track this donation from pickup to delivery.</p>
        </div>

        <a
            href="my-donations.php"
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

                <span class="ngo-badge">
                    <?= htmlspecialchars($row['status']) ?>
                </span>

                <h2>
                    <?= htmlspecialchars($row['food_name']) ?>
                </h2>

                <div class="ngo-info-list">

                    <div>
                        <strong>Quantity</strong>
                        <span>
                            <?= htmlspecialchars($row['quantity']) ?>
                            <?= htmlspecialchars($row['unit'] ?? '') ?>
                        </span>
                    </div>

                    <div>
                        <strong>Pickup Location</strong>
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
                        <strong>Created</strong>
                        <span><?= htmlspecialchars($row['created_at']) ?></span>
                    </div>

                    <div>
                        <strong>Confirmed</strong>
                        <span><?= htmlspecialchars($row['confirmed_at'] ?? 'Not confirmed') ?></span>
                    </div>

                    <div>
                        <strong>Delivered</strong>
                        <span><?= htmlspecialchars($row['delivered_at'] ?? 'Not delivered') ?></span>
                    </div>

                </div>

                <?php if (!empty($row['notes'])): ?>

                    <div class="ngo-note">
                        <strong>Notes</strong>
                        <p><?= nl2br(htmlspecialchars($row['notes'])) ?></p>
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>

</div>

<?php ngo_footer(); ?>
