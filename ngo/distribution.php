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
        dl.status,
        dl.method,
        dl.delivered_at,

        fd.food_name,
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

$rows = $stmt->get_result();

ngo_header("Distribution");
?>

<div class="ngo-content">

    <div class="ngo-page-header">
        <div>
            <h1>Distribution</h1>
            <p>Manage food distribution to recipients.</p>
        </div>
    </div>

    <section class="ngo-section">

        <?php if ($rows->num_rows > 0): ?>

            <div class="ngo-donation-grid">

                <?php while ($row = $rows->fetch_assoc()): ?>

                    <?php
                    $photo = !empty($row['food_photo'])
                        ? "../uploads/food/" . htmlspecialchars($row['food_photo'])
                        : "../assets/images/no-food.png";
                    ?>

                    <div class="ngo-donation-card">

                        <img
                            src="<?= $photo ?>"
                            class="ngo-food-image"
                            alt="Food">

                        <div class="ngo-card-body">

                            <div class="ngo-card-title-row">

                                <h3>
                                    <?= htmlspecialchars($row['food_name']) ?>
                                </h3>

                                <span class="ngo-badge">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>

                            </div>

                            <div class="ngo-food-meta">

                                <span>
                                    📦
                                    <?= htmlspecialchars($row['quantity']) ?>
                                    <?= htmlspecialchars($row['unit'] ?? '') ?>
                                </span>

                                <span>
                                    📍
                                    <?= htmlspecialchars($row['area'] ?? '') ?>
                                </span>

                            </div>

                            <a
                                href="distribution-details.php?id=<?= (int)$row['delivery_id'] ?>"
                                class="ngo-btn ngo-btn-primary ngo-btn-full">
                                Manage Distribution
                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="ngo-empty">
                <div>🤝</div>
                <h3>No distributions</h3>
                <p>Distribution records will appear here.</p>
            </div>

        <?php endif; ?>

    </section>

</div>

<?php
$stmt->close();
ngo_footer();
?>
