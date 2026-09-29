<?php

session_start();

require_once "../config/database.php";
require_once "../includes/ngo-layout.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$ngo_id = (int)$_SESSION['user_id'];


/* =========================================================
   STATISTICS
========================================================= */

$total_available = 0;
$total_pending = 0;
$total_accepted = 0;
$total_completed = 0;


/* Available */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM food_donations
    WHERE status = 'available'
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_available = (int)$row['total'];
}


/* Pending */

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


/* Accepted / In progress */

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


/* =========================================================
   RECENT DONATIONS
========================================================= */

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
        status
    FROM food_donations
    WHERE status = 'available'
    ORDER BY donation_id DESC
    LIMIT 6
");


ngo_header("NGO Dashboard");

?>

<!-- =======================================================
     WELCOME
======================================================== -->

<div class="ngo-page-header">

    <div>

        <h1>NGO Dashboard</h1>

        <p>
            Welcome back! Manage donations and help reduce food waste.
        </p>

    </div>

    <a href="available-donations.php"
       class="ngo-btn ngo-btn-primary">

        <i class="fa-solid fa-plus"></i>

        Find Donations

    </a>

</div>


<!-- =======================================================
     STATISTICS
======================================================== -->

<div class="ngo-stats">


    <!-- Available -->

    <div class="ngo-stat">

        <div>

            <span class="ngo-stat-label">
                Available Donations
            </span>

            <strong>
                <?= $total_available ?>
            </strong>

        </div>

        <div class="ngo-stat-icon">
            🍱
        </div>

    </div>


    <!-- Pending -->

    <div class="ngo-stat">

        <div>

            <span class="ngo-stat-label">
                Pending Requests
            </span>

            <strong>
                <?= $total_pending ?>
            </strong>

        </div>

        <div class="ngo-stat-icon">
            ⏳
        </div>

    </div>


    <!-- Accepted -->

    <div class="ngo-stat">

        <div>

            <span class="ngo-stat-label">
                Accepted Donations
            </span>

            <strong>
                <?= $total_accepted ?>
            </strong>

        </div>

        <div class="ngo-stat-icon">
            🚚
        </div>

    </div>


    <!-- Completed -->

    <div class="ngo-stat">

        <div>

            <span class="ngo-stat-label">
                Food Delivered
            </span>

            <strong>
                <?= $total_completed ?>
            </strong>

        </div>

        <div class="ngo-stat-icon">
            ❤️
        </div>

    </div>

</div>


<!-- =======================================================
     RECENT DONATIONS
======================================================== -->

<section class="ngo-section">

    <div class="ngo-section-header">

        <div>

            <h2>
                Nearby Donations
            </h2>

            <p>
                Recently posted food available for your NGO.
            </p>

        </div>

        <a href="available-donations.php"
           class="ngo-btn ngo-btn-outline">

            View All

            <i class="fa-solid fa-arrow-right"></i>

        </a>

    </div>


    <?php if ($recent && $recent->num_rows > 0): ?>

        <div class="ngo-donation-grid">

            <?php while ($food = $recent->fetch_assoc()): ?>


                <?php

                if (!empty($food['food_photo'])) {

                    $photo =
                        "../uploads/food/" .
                        htmlspecialchars($food['food_photo']);

                } else {

                    $photo =
                        "../assets/images/no-food.png";

                }

                ?>


                <div class="ngo-donation-card">


                    <img
                        src="<?= $photo ?>"
                        class="ngo-food-image"
                        alt="Food Donation">


                    <div class="ngo-card-body">


                        <div class="ngo-card-title-row">

                            <h3>
                                <?= htmlspecialchars(
                                    $food['food_name']
                                ) ?>
                            </h3>

                            <span class="ngo-badge">

                                Available

                            </span>

                        </div>


                        <p>

                            <?= htmlspecialchars(
                                $food['food_category'] ??
                                'Food'
                            ) ?>

                        </p>


                        <div class="ngo-food-meta">

                            <span>

                                <i class="fa-solid fa-box"></i>

                                <?= htmlspecialchars(
                                    $food['quantity']
                                ) ?>

                                <?= htmlspecialchars(
                                    $food['unit'] ?? ''
                                ) ?>

                            </span>


                            <span>

                                <i class="fa-solid fa-location-dot"></i>

                                <?= htmlspecialchars(
                                    $food['area'] ??
                                    $food['city'] ??
                                    'Nearby'
                                ) ?>

                            </span>

                        </div>


                        <a
                            href="donation-details.php?id=<?= (int)$food['donation_id'] ?>"
                            class="ngo-btn ngo-btn-primary ngo-btn-full">

                            View Donation

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>

                    </div>

                </div>


            <?php endwhile; ?>

        </div>


    <?php else: ?>


        <div class="ngo-empty">

            <div>
                🍽️
            </div>

            <h3>
                No donations available
            </h3>

            <p>
                New food donations will appear here.
            </p>

        </div>


    <?php endif; ?>

</section>


<!-- =======================================================
     QUICK ACTIONS
======================================================== -->

<section class="ngo-section">

    <div class="ngo-section-header">

        <div>

            <h2>
                Quick Actions
            </h2>

            <p>
                Quickly access your most used NGO features.
            </p>

        </div>

    </div>


    <div class="ngo-donation-grid">


        <a
            href="available-donations.php"
            class="ngo-donation-card"
            style="text-decoration:none;color:inherit;">

            <div class="ngo-card-body"
                 style="text-align:center;padding:30px;">

                <div class="ngo-stat-icon"
                     style="margin:auto auto 15px;">

                    🍱

                </div>

                <h3>
                    Find Food
                </h3>

                <p>
                    View available food donations.
                </p>

            </div>

        </a>


        <a
            href="my-donations.php"
            class="ngo-donation-card"
            style="text-decoration:none;color:inherit;">

            <div class="ngo-card-body"
                 style="text-align:center;padding:30px;">

                <div class="ngo-stat-icon"
                     style="margin:auto auto 15px;">

                    📦

                </div>

                <h3>
                    My Donations
                </h3>

                <p>
                    Track your accepted donations.
                </p>

            </div>

        </a>


        <a
            href="distribution.php"
            class="ngo-donation-card"
            style="text-decoration:none;color:inherit;">

            <div class="ngo-card-body"
                 style="text-align:center;padding:30px;">

                <div class="ngo-stat-icon"
                     style="margin:auto auto 15px;">

                    ❤️

                </div>

                <h3>
                    Distribution
                </h3>

                <p>
                    Manage food distribution.
                </p>

            </div>

        </a>


    </div>

</section>


<?php

ngo_footer();

?>
