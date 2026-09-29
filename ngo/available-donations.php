<?php
session_start();

require_once "../config/database.php";
require_once "../includes/ngo-layout.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$sql = "
    SELECT
        donation_id,
        food_name,
        food_category,
        food_type,
        description,
        quantity,
        unit,
        food_photo,
        city,
        area,
        pincode,
        best_before,
        urgency,
        status
    FROM food_donations
    WHERE status = 'available'
";

$params = [];
$types = "";

if ($search !== '') {
    $sql .= "
        AND (
            food_name LIKE ?
            OR food_category LIKE ?
            OR city LIKE ?
            OR area LIKE ?
        )
    ";

    $like = "%" . $search . "%";

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;

    $types .= "ssss";
}

if ($category !== '') {
    $sql .= " AND food_category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " ORDER BY donation_id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$donations = $stmt->get_result();

ngo_header("Available Donations");
?>

<div class="ngo-content">

    <div class="ngo-page-header">
        <div>
            <h1>Available Donations</h1>
            <p>Find food donations available for collection.</p>
        </div>
    </div>

    <section class="ngo-section">

        <form method="GET" class="ngo-filter-bar">

            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Search food, category or location...">

            <input
                type="text"
                name="category"
                value="<?= htmlspecialchars($category) ?>"
                placeholder="Food category">

            <button type="submit" class="ngo-btn ngo-btn-primary">
                Search
            </button>

            <a href="available-donations.php" class="ngo-btn ngo-btn-outline">
                Reset
            </a>

        </form>

        <?php if ($donations->num_rows > 0): ?>

            <div class="ngo-donation-grid">

                <?php while ($food = $donations->fetch_assoc()): ?>

                    <?php
                    $photo = !empty($food['food_photo'])
                        ? "../uploads/food/" . htmlspecialchars($food['food_photo'])
                        : "../assets/images/no-food.png";
                    ?>

                    <div class="ngo-donation-card">

                        <img
                            src="<?= $photo ?>"
                            class="ngo-food-image"
                            alt="Food">

                        <div class="ngo-card-body">

                            <div class="ngo-card-title-row">
                                <h3><?= htmlspecialchars($food['food_name']) ?></h3>
                                <span class="ngo-badge ngo-badge-success">
                                    Available
                                </span>
                            </div>

                            <p>
                                <?= htmlspecialchars($food['food_category'] ?? 'Food') ?>
                            </p>

                            <div class="ngo-food-meta">
                                <span>
                                    📦 <?= htmlspecialchars($food['quantity']) ?>
                                    <?= htmlspecialchars($food['unit'] ?? '') ?>
                                </span>

                                <span>
                                    📍 <?= htmlspecialchars($food['area'] ?? $food['city'] ?? '') ?>
                                </span>
                            </div>

                            <?php if (!empty($food['best_before'])): ?>
                                <small>
                                    Best before:
                                    <?= htmlspecialchars($food['best_before']) ?>
                                </small>
                            <?php endif; ?>

                            <a
                                href="donation-details.php?id=<?= (int)$food['donation_id'] ?>"
                                class="ngo-btn ngo-btn-primary ngo-btn-full">
                                View Donation
                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="ngo-empty">
                <div>🔎</div>
                <h3>No donations found</h3>
                <p>Try another search.</p>
            </div>

        <?php endif; ?>

    </section>

</div>

<?php
$stmt->close();
ngo_footer();
?>
