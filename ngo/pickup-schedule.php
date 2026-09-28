<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

$ngo_id = $_SESSION["user_id"];

$delivery_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($delivery_id <= 0) {
    die("Invalid delivery ID.");
}

$stmt = $conn->prepare("
    SELECT
        d.*,
        fd.food_name,
        fd.food_category,
        fd.quantity,
        fd.unit,
        fd.city,
        fd.area,
        fd.food_photo,
        u.name AS donor_name,
        u.phone AS donor_phone
    FROM deliveries d
    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id
    LEFT JOIN users u
        ON fd.donor_id = u.user_id
    WHERE d.delivery_id = ?
    AND d.ngo_id = ?
");

$stmt->bind_param("ii", $delivery_id, $ngo_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Donation not found.");
}

$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pickup Schedule</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>Pickup Schedule</h1>

    <h2>
        <?php echo htmlspecialchars($data["food_name"]); ?>
    </h2>

    <p>
        <strong>Quantity:</strong>
        <?php echo htmlspecialchars($data["quantity"]); ?>
        <?php echo htmlspecialchars($data["unit"]); ?>
    </p>

    <p>
        <strong>Pickup Location:</strong>
        <?php echo htmlspecialchars($data["city"]); ?>,
        <?php echo htmlspecialchars($data["area"]); ?>
    </p>

    <h3>Donor</h3>

    <p>
        <?php echo htmlspecialchars($data["donor_name"]); ?>
    </p>

    <p>
        Phone:
        <?php echo htmlspecialchars($data["donor_phone"]); ?>
    </p>

    <p>
        <strong>Current Status:</strong>
        <?php echo htmlspecialchars($data["status"]); ?>
    </p>

    <br>

    <a href="collection-status.php?id=<?php echo $delivery_id; ?>">
        Update Collection Status
    </a>

</div>

</body>
</html>
