<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

$ngo_id = $_SESSION["user_id"];

$stmt = $conn->prepare("
    SELECT
        d.delivery_id,
        d.donation_id,
        d.status,
        d.method,
        fd.food_name,
        fd.food_category,
        fd.quantity,
        fd.unit,
        fd.food_photo,
        fd.city,
        fd.area,
        u.name AS donor_name
    FROM deliveries d
    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id
    LEFT JOIN users u
        ON fd.donor_id = u.user_id
    WHERE d.ngo_id = ?
    ORDER BY d.delivery_id DESC
");

$stmt->bind_param("i", $ngo_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Donations</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/food.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>My Donations</h1>

    <?php if ($result->num_rows > 0): ?>

        <div class="food-grid">

            <?php while ($row = $result->fetch_assoc()): ?>

                <div class="food-card">

                    <?php if (!empty($row["food_photo"])): ?>

                        <img
                            src="../uploads/food/<?php echo htmlspecialchars($row["food_photo"]); ?>"
                            class="food-image"
                            alt="Food"
                        >

                    <?php endif; ?>

                    <h2>
                        <?php echo htmlspecialchars($row["food_name"]); ?>
                    </h2>

                    <p>
                        <strong>Quantity:</strong>
                        <?php echo htmlspecialchars($row["quantity"]); ?>
                        <?php echo htmlspecialchars($row["unit"]); ?>
                    </p>

                    <p>
                        <strong>Donor:</strong>
                        <?php echo htmlspecialchars($row["donor_name"]); ?>
                    </p>

                    <p>
                        <strong>Status:</strong>
                        <?php echo htmlspecialchars($row["status"]); ?>
                    </p>

                    <a href="pickup-schedule.php?id=<?php echo $row["delivery_id"]; ?>">
                        Pickup Schedule
                    </a>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <p>You have not accepted any donations yet.</p>

    <?php endif; ?>

</div>

</body>
</html>
