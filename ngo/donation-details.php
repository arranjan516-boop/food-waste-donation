<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

$donation_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($donation_id <= 0) {
    die("Invalid donation ID.");
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Donation Details</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/food.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>Donation Details</h1>

    <?php if (!empty($food["food_photo"])): ?>

        <img
            src="../uploads/food/<?php echo htmlspecialchars($food["food_photo"]); ?>"
            class="food-details-image"
            alt="Food Image"
        >

    <?php endif; ?>

    <h2>
        <?php echo htmlspecialchars($food["food_name"]); ?>
    </h2>

    <p>
        <strong>Category:</strong>
        <?php echo htmlspecialchars($food["food_category"]); ?>
    </p>

    <p>
        <strong>Description:</strong>
        <?php echo htmlspecialchars($food["description"]); ?>
    </p>

    <p>
        <strong>Quantity:</strong>
        <?php echo htmlspecialchars($food["quantity"]); ?>
        <?php echo htmlspecialchars($food["unit"]); ?>
    </p>

    <p>
        <strong>Location:</strong>
        <?php echo htmlspecialchars($food["city"]); ?>,
        <?php echo htmlspecialchars($food["area"]); ?>
    </p>

    <p>
        <strong>Delivery Preference:</strong>
        <?php echo htmlspecialchars($food["delivery_preference"]); ?>
    </p>

    <hr>

    <h2>Donor Details</h2>

    <p>
        <strong>Name:</strong>
        <?php echo htmlspecialchars($food["donor_name"]); ?>
    </p>

    <p>
        <strong>Phone:</strong>
        <?php echo htmlspecialchars($food["donor_phone"]); ?>
    </p>

    <p>
        <strong>Email:</strong>
        <?php echo htmlspecialchars($food["donor_email"]); ?>
    </p>

    <br>

    <a href="accept-donation.php?id=<?php echo $donation_id; ?>">
        Accept Donation
    </a>

    &nbsp;

    <a href="reject-donation.php?id=<?php echo $donation_id; ?>">
        Reject
    </a>

</div>

</body>
</html>
