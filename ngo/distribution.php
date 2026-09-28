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
        fd.food_name,
        fd.quantity AS donated_quantity,
        fd.unit,
        fr.request_id,
        fr.quantity AS requested_quantity,
        fr.message,
        fr.status AS request_status,
        u.name AS recipient_name,
        u.phone AS recipient_phone
    FROM deliveries d
    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id
    INNER JOIN food_requests fr
        ON d.donation_id = fr.donation_id
    INNER JOIN users u
        ON fr.recipient_id = u.user_id
    WHERE d.ngo_id = ?
    AND fr.status = 'accepted'
    ORDER BY fr.request_id DESC
");

$stmt->bind_param("i", $ngo_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Distribution</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>Food Distribution</h1>

    <p>
        Manage food distribution to recipients.
    </p>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($row = $result->fetch_assoc()): ?>

            <div class="dashboard-card">

                <h2>
                    <?php echo htmlspecialchars($row["food_name"]); ?>
                </h2>

                <p>
                    <strong>Recipient:</strong>
                    <?php echo htmlspecialchars($row["recipient_name"]); ?>
                </p>

                <p>
                    <strong>Phone:</strong>
                    <?php echo htmlspecialchars($row["recipient_phone"]); ?>
                </p>

                <p>
                    <strong>Requested Quantity:</strong>
                    <?php echo htmlspecialchars($row["requested_quantity"]); ?>
                    <?php echo htmlspecialchars($row["unit"]); ?>
                </p>

                <p>
                    <strong>Delivery Status:</strong>
                    <?php echo htmlspecialchars($row["status"]); ?>
                </p>

                <a href="distribution-details.php?id=<?php echo $row["request_id"]; ?>">
                    View Distribution
                </a>

            </div>

            <br>

        <?php endwhile; ?>

    <?php else: ?>

        <p>No food distribution requests available.</p>

    <?php endif; ?>

</div>

</body>
</html>
