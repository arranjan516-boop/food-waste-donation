<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

$ngo_id = $_SESSION["user_id"];

function get_count($conn, $sql, $types = "", $params = [])
{
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return intval($row["total"] ?? 0);
}

/* Available donations */

$available = get_count(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM food_donations fd
    LEFT JOIN deliveries d
        ON fd.donation_id = d.donation_id
        AND (d.ngo_id IS NOT NULL OR d.collector_id IS NOT NULL)
    WHERE d.delivery_id IS NULL
    "
);

/* My donations */

$my_donations = get_count(
    $conn,
    "SELECT COUNT(*) AS total FROM deliveries WHERE ngo_id = ?",
    "i",
    [$ngo_id]
);

/* Active */

$active = get_count(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE ngo_id = ?
    AND status <> 'delivered'
    ",
    "i",
    [$ngo_id]
);

/* Completed */

$completed = get_count(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE ngo_id = ?
    AND status = 'delivered'
    ",
    "i",
    [$ngo_id]
);

/* Recent donations */

$stmt = $conn->prepare("
    SELECT
        d.delivery_id,
        d.status,
        fd.food_name,
        fd.food_category,
        fd.quantity,
        fd.unit,
        fd.food_photo
    FROM deliveries d
    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id
    WHERE d.ngo_id = ?
    ORDER BY d.delivery_id DESC
    LIMIT 5
");

$stmt->bind_param("i", $ngo_id);
$stmt->execute();

$recent = $stmt->get_result();

$ngo_page_title = "Dashboard";
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>NGO Dashboard</title>

    <link rel="stylesheet" href="../assets/css/ngo.css">

</head>

<body class="ngo-body">

<?php include "../includes/ngo-layout.php"; ?>

<main class="ngo-content">

    <section class="ngo-welcome">

        <h2>
            Welcome back, <?php echo htmlspecialchars($_SESSION["name"]); ?> 👋
        </h2>

        <p>
            Manage food donations and help distribute surplus food to people in need.
        </p>

    </section>


    <!-- STATISTICS -->

    <section class="ngo-stats">

        <div class="ngo-stat">

            <div class="ngo-stat-top">

                <div>
                    <small>Available</small>
                    <h3><?php echo $available; ?></h3>
                    <p>Food donations</p>
                </div>

                <div class="ngo-stat-icon">
                    🍱
                </div>

            </div>

        </div>


        <div class="ngo-stat">

            <div class="ngo-stat-top">

                <div>
                    <small>My Donations</small>
                    <h3><?php echo $my_donations; ?></h3>
                    <p>Accepted donations</p>
                </div>

                <div class="ngo-stat-icon">
                    📦
                </div>

            </div>

        </div>


        <div class="ngo-stat">

            <div class="ngo-stat-top">

                <div>
                    <small>Active</small>
                    <h3><?php echo $active; ?></h3>
                    <p>Ongoing work</p>
                </div>

                <div class="ngo-stat-icon">
                    🚚
                </div>

            </div>

        </div>


        <div class="ngo-stat">

            <div class="ngo-stat-top">

                <div>
                    <small>Completed</small>
                    <h3><?php echo $completed; ?></h3>
                    <p>Successfully completed</p>
                </div>

                <div class="ngo-stat-icon">
                    ✓
                </div>

            </div>

        </div>

    </section>


    <!-- QUICK ACTIONS -->

    <section class="ngo-section">

        <div class="ngo-section-header">

            <h2>Quick Actions</h2>

        </div>

        <div class="ngo-action-box">

            <a
                href="available-donations.php"
                class="ngo-btn ngo-btn-primary"
            >
                🍱 View Donations
            </a>

            <a
                href="my-donations.php"
                class="ngo-btn ngo-btn-light"
            >
                📦 My Donations
            </a>

            <a
                href="distribution.php"
                class="ngo-btn ngo-btn-info"
            >
                🤝 Distribution
            </a>

            <a
                href="notifications.php"
                class="ngo-btn ngo-btn-warning"
            >
                🔔 Notifications
            </a>

        </div>

    </section>


    <!-- RECENT -->

    <section class="ngo-section">

        <div class="ngo-section-header">

            <h2>Recent Donations</h2>

            <a href="my-donations.php">
                View All →
            </a>

        </div>


        <?php if ($recent->num_rows > 0): ?>

            <div class="ngo-donation-grid">

                <?php while ($row = $recent->fetch_assoc()): ?>

                    <div class="ngo-donation-card">

                        <?php if (!empty($row["food_photo"])): ?>

                            <img
                                class="ngo-food-image"
                                src="../uploads/food/<?php echo htmlspecialchars($row["food_photo"]); ?>"
                                alt="Food"
                            >

                        <?php else: ?>

                            <div class="ngo-food-placeholder">
                                🍱
                            </div>

                        <?php endif; ?>


                        <div class="ngo-donation-body">

                            <span class="ngo-category">
                                <?php echo htmlspecialchars($row["food_category"]); ?>
                            </span>

                            <h3>
                                <?php echo htmlspecialchars($row["food_name"]); ?>
                            </h3>

                            <div class="ngo-food-info">

                                <div class="ngo-info-item">

                                    <small>Quantity</small>

                                    <strong>
                                        <?php echo htmlspecialchars($row["quantity"]); ?>
                                        <?php echo htmlspecialchars($row["unit"]); ?>
                                    </strong>

                                </div>

                                <div class="ngo-info-item">

                                    <small>Status</small>

                                    <strong>
                                        <?php echo htmlspecialchars($row["status"]); ?>
                                    </strong>

                                </div>

                            </div>

                            <a
                                href="donation-details.php?id=<?php echo $row["delivery_id"]; ?>"
                                class="ngo-btn ngo-btn-light"
                                style="width:100%;"
                            >
                                View Donation →
                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="ngo-empty">

                <div class="ngo-empty-icon">
                    📦
                </div>

                <h3>No Donations Yet</h3>

                <p>
                    Donations accepted by your NGO will appear here.
                </p>

            </div>

        <?php endif; ?>

    </section>

</main>

</div>

</body>

</html>
