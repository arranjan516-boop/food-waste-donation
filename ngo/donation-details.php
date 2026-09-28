<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

$donation_id = intval($_GET["id"] ?? 0);

if ($donation_id <= 0) {
    header("Location: available-donations.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT
        fd.*,
        u.name AS donor_name,
        u.phone AS donor_phone,
        u.email AS donor_email
    FROM food_donations fd
    LEFT JOIN users u
        ON fd.donor_id = u.user_id
    WHERE fd.donation_id = ?
");

$stmt->bind_param("i", $donation_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Donation not found.");
}

$food = $result->fetch_assoc();

$ngo_page_title = "Donation Details";
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Donation Details</title>

    <link rel="stylesheet" href="../assets/css/ngo.css">

</head>

<body class="ngo-body">

<?php include "../includes/ngo-layout.php"; ?>

<main class="ngo-content">

    <div class="ngo-detail-grid">

        <!-- FOOD -->

        <section class="ngo-section">

            <?php if (!empty($food["food_photo"])): ?>

                <img
                    class="ngo-detail-image"
                    src="../uploads/food/<?php echo htmlspecialchars($food["food_photo"]); ?>"
                    alt="Food"
                >

            <?php else: ?>

                <div class="ngo-food-placeholder" style="height:420px;border-radius:15px;">
                    🍱
                </div>

            <?php endif; ?>

        </section>


        <!-- DETAILS -->

        <section class="ngo-section">

            <span class="ngo-category">
                <?php echo htmlspecialchars($food["food_category"]); ?>
            </span>

            <h1>
                <?php echo htmlspecialchars($food["food_name"]); ?>
            </h1>

            <p style="color:#6b7280;">
                <?php echo htmlspecialchars($food["description"]); ?>
            </p>


            <div class="ngo-detail-list">

                <div class="ngo-detail-row">
                    <span>Quantity</span>
                    <strong>
                        <?php echo htmlspecialchars($food["quantity"]); ?>
                        <?php echo htmlspecialchars($food["unit"]); ?>
                    </strong>
                </div>

                <div class="ngo-detail-row">
                    <span>City</span>
                    <strong>
                        <?php echo htmlspecialchars($food["city"]); ?>
                    </strong>
                </div>

                <div class="ngo-detail-row">
                    <span>Area</span>
                    <strong>
                        <?php echo htmlspecialchars($food["area"]); ?>
                    </strong>
                </div>

                <div class="ngo-detail-row">
                    <span>Delivery Preference</span>
                    <strong>
                        <?php echo htmlspecialchars($food["delivery_preference"]); ?>
                    </strong>
                </div>

            </div>


            <br>


            <h3>Donor Information</h3>

            <div class="ngo-detail-list">

                <div class="ngo-detail-row">
                    <span>Name</span>
                    <strong>
                        <?php echo htmlspecialchars($food["donor_name"]); ?>
                    </strong>
                </div>

                <div class="ngo-detail-row">
                    <span>Phone</span>
                    <strong>
                        <?php echo htmlspecialchars($food["donor_phone"]); ?>
                    </strong>
                </div>

                <div class="ngo-detail-row">
                    <span>Email</span>
                    <strong>
                        <?php echo htmlspecialchars($food["donor_email"]); ?>
                    </strong>
                </div>

            </div>


            <div class="ngo-action-box">

                <a
                    href="accept-donation.php?id=<?php echo $donation_id; ?>"
                    class="ngo-btn ngo-btn-primary"
                >
                    ✓ Accept Donation
                </a>

                <a
                    href="reject-donation.php?id=<?php echo $donation_id; ?>"
                    class="ngo-btn ngo-btn-danger"
                >
                    ✕ Reject
                </a>

                <a
                    href="available-donations.php"
                    class="ngo-btn ngo-btn-light"
                >
                    ← Back
                </a>

            </div>

        </section>

    </div>

</main>

</div>

</body>

</html>
