<?php
session_start();

require_once "../config/database.php";
require_once "../includes/ngo-layout.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$ngo_id = (int)$_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$total_available = 0;
$total_pending = 0;
$total_accepted = 0;
$total_completed = 0;

/* Available donations */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM food_donations
    WHERE status = 'available'
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total_available = (int)$row['total'];
    }
    $stmt->close();
}

/* Pending deliveries */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE ngo_id = ?
    AND status = 'pending'
");
if ($stmt) {
    $stmt->bind_param("i", $ngo_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $total_pending = (int)$row['total'];
    }

    $stmt->close();
}

/* Accepted deliveries */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE ngo_id = ?
    AND status <> 'pending'
    AND status <> 'delivered'
");
if ($stmt) {
    $stmt->bind_param("i", $ngo_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $total_accepted = (int)$row['total'];
    }

    $stmt->close();
}

/* Completed */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE ngo_id = ?
    AND status = 'delivered'
");
if ($stmt) {
    $stmt->bind_param("i", $ngo_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $total_completed = (int)$row['total'];
    }

    $stmt->close();
}

/* Recent donations */
$recent = $conn->query("
    SELECT
        donation_id,
        food_name,
        food_category,
        quantity,
        unit,
        food_photo,
        city,
        area,
        status,
        created_at
    FROM food_donations
    ORDER BY donation_id DESC
    LIMIT 6
");

ngo_header("NGO Dashboard");
?>

<div class="ngo-content">

    <div class="ngo-page-header">
        <div>
            <h1>NGO Dashboard</h1>
            <p>Manage food donations, collections and distributions.</p>
        </div>

        <a href="available-donations.php" class="ngo-btn ngo-btn-primary">
            + Find Donations
        </a>
    </div>

    <!-- Statistics -->

    <div class="ngo-stats">

        <div class="ngo-stat">
            <div>
                <span class="ngo-stat-label">Available Donations</span>
                <strong><?= $total_available ?></strong>
            </div>
            <div class="ngo-stat-icon">🍱</div>
        </div>

        <div class="ngo-stat">
            <div>
                <span class="ngo-stat-label">Pending</span>
                <strong><?= $total_pending ?></strong>
            </div>
            <div class="ngo-stat-icon">⏳</div>
        </div>

        <div class="ngo-stat">
            <div>
                <span class="ngo-stat-label">In Progress</span>
                <strong><?= $total_accepted ?></strong>
            </div>
            <div class="ngo-stat-icon">🚚</div>
        </div>

        <div class="ngo-stat">
            <div>
                <span class="ngo-stat-label">Completed</span>
                <strong><?= $total_completed ?></strong>
            </div>
            <div class="ngo-stat-icon">✅</div>
        </div>

    </div>

    <!-- Recent Donations -->

    <section class="ngo-section">

        <div class="ngo-section-header">
            <div>
                <h2>Nearby Donations</h2>
                <p>Recently posted food donations.</p>
            </div>

            <a href="available-donations.php" class="ngo-btn ngo-btn-outline">
                View All
            </a>
        </div>

        <?php if ($recent && $recent->num_rows > 0): ?>

            <div class="ngo-donation-grid">

                <?php while ($food = $recent->fetch_assoc()): ?>

                    <div class="ngo-donation-card">

                        <?php
                        $photo = !empty($food['food_photo'])
                            ? "../uploads/food/" . htmlspecialchars($food['food_photo'])
                            : "../assets/images/no-food.png";
                        ?>

                        <img
                            src="<?= $photo ?>"
                            class="ngo-food-image"
                            alt="Food">

                        <div class="ngo-card-body">

                            <div class="ngo-card-title-row">
                                <h3><?= htmlspecialchars($food['food_name']) ?></h3>

                                <span class="ngo-badge">
                                    <?= htmlspecialchars($food['status']) ?>
                                </span>
                            </div>

                            <p class="ngo-category">
                                <?= htmlspecialchars($food['food_category'] ?? 'Food') ?>
                            </p>

                            <div class="ngo-food-meta">
                                <span>📦 <?= htmlspecialchars($food['quantity']) ?> <?= htmlspecialchars($food['unit'] ?? '') ?></span>
                                <span>📍 <?= htmlspecialchars($food['area'] ?? $food['city'] ?? 'Location unavailable') ?></span>
                            </div>

                            <a
                                href="donation-details.php?id=<?= (int)$food['donation_id'] ?>"
                                class="ngo-btn ngo-btn-primary ngo-btn-full">
                                View Details
                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="ngo-empty">
                <div>🍽️</div>
                <h3>No donations available</h3>
                <p>New food donations will appear here.</p>
            </div>

        <?php endif; ?>

    </section>

</div>

<?php ngo_footer(); ?>
