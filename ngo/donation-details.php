<?php
session_start();

require_once "../config/database.php";
require_once "../includes/ngo-layout.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$donation_id = (int)($_GET['id'] ?? 0);

if ($donation_id <= 0) {
    header("Location: available-donations.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT
        d.*,
        u.name AS donor_name,
        u.phone AS donor_phone,
        u.email AS donor_email
    FROM food_donations d
    LEFT JOIN users u
        ON u.user_id = d.donor_id
    WHERE d.donation_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $donation_id);
$stmt->execute();

$food = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$food) {
    header("Location: available-donations.php");
    exit;
}

ngo_header("Donation Details");
?>

<div class="ngo-content">

    <div class="ngo-page-header">
        <div>
            <h1>Donation Details</h1>
            <p>Review the food before accepting it.</p>
        </div>

        <a href="available-donations.php" class="ngo-btn ngo-btn-outline">
            ← Back
        </a>
    </div>

    <section class="ngo-section">

        <div class="ngo-detail-grid">

            <div>

                <?php
                $photo = !empty($food['food_photo'])
                    ? "../uploads/food/" . htmlspecialchars($food['food_photo'])
                    : "../assets/images/no-food.png";
                ?>

                <img
                    src="<?= $photo ?>"
                    alt="Food"
                    class="ngo-detail-image">

            </div>

            <div>

                <span class="ngo-badge">
                    <?= htmlspecialchars($food['status'] ?? 'Available') ?>
                </span>

                <h2><?= htmlspecialchars($food['food_name']) ?></h2>

                <p>
                    <?= htmlspecialchars($food['description'] ?? 'No description provided.') ?>
                </p>

                <div class="ngo-info-list">

                    <div>
                        <strong>Category</strong>
                        <span><?= htmlspecialchars($food['food_category'] ?? '-') ?></span>
                    </div>

                    <div>
                        <strong>Food Type</strong>
                        <span><?= htmlspecialchars($food['food_type'] ?? '-') ?></span>
                    </div>

                    <div>
                        <strong>Quantity</strong>
                        <span>
                            <?= htmlspecialchars($food['quantity']) ?>
                            <?= htmlspecialchars($food['unit'] ?? '') ?>
                        </span>
                    </div>

                    <div>
                        <strong>Location</strong>
                        <span>
                            <?= htmlspecialchars($food['area'] ?? '') ?>,
                            <?= htmlspecialchars($food['city'] ?? '') ?>
                        </span>
                    </div>

                    <div>
                        <strong>Donor</strong>
                        <span><?= htmlspecialchars($food['donor_name'] ?? 'Donor') ?></span>
                    </div>

                </div>

                <?php if (($food['status'] ?? '') === 'available'): ?>

                    <div class="ngo-action-row">

                        <a
                            href="accept-donation.php?id=<?= $donation_id ?>"
                            class="ngo-btn ngo-btn-primary">
                            ✓ Accept Donation
                        </a>

                        <a
                            href="reject-donation.php?id=<?= $donation_id ?>"
                            class="ngo-btn ngo-btn-danger">
                            ✕ Reject
                        </a>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>

</div>

<?php ngo_footer(); ?>
