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
        d.delivered_at,
        fd.food_name,
        fd.food_category,
        fd.quantity,
        fd.unit,
        fd.city,
        fd.area,
        u.name AS donor_name
    FROM deliveries d
    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id
    LEFT JOIN users u
        ON fd.donor_id = u.user_id
    WHERE d.ngo_id = ?
    AND d.status = 'delivered'
    ORDER BY d.delivered_at DESC
");

$stmt->bind_param("i", $ngo_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Completed Donations</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>Completed Donations</h1>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($row = $result->fetch_assoc()): ?>

            <div class="dashboard-card">

                <h2>
                    <?php echo htmlspecialchars($row["food_name"]); ?>
                </h2>

                <p>
                    Category:
                    <?php echo htmlspecialchars($row["food_category"]); ?>
                </p>

                <p>
                    Quantity:
                    <?php echo htmlspecialchars($row["quantity"]); ?>
                    <?php echo htmlspecialchars($row["unit"]); ?>
                </p>

                <p>
                    Donor:
                    <?php echo htmlspecialchars($row["donor_name"]); ?>
                </p>

                <p>
                    Location:
                    <?php echo htmlspecialchars($row["city"]); ?>,
                    <?php echo htmlspecialchars($row["area"]); ?>
                </p>

                <p>
                    Completed:
                    <?php echo htmlspecialchars($row["delivered_at"]); ?>
                </p>

                <p>
                    <strong>Status: Completed</strong>
                </p>

            </div>

            <br>

        <?php endwhile; ?>

    <?php else: ?>

        <p>No completed donations yet.</p>

    <?php endif; ?>

</div>

</body>
</html>
