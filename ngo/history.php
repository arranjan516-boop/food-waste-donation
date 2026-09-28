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
        d.method,
        d.status,
        d.created_at,
        d.confirmed_at,
        d.delivered_at,
        fd.food_name,
        fd.food_category,
        fd.quantity,
        fd.unit
    FROM deliveries d
    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id
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
    <title>NGO History</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>Donation History</h1>

    <?php if ($result->num_rows > 0): ?>

        <table border="1" cellpadding="10" cellspacing="0">

            <tr>
                <th>Food</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Method</th>
                <th>Status</th>
                <th>Created</th>
                <th>Completed</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($row["food_name"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["food_category"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["quantity"]); ?>
                        <?php echo htmlspecialchars($row["unit"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["method"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["status"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["created_at"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["delivered_at"] ?? "Not completed"); ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        </table>

    <?php else: ?>

        <p>No history available.</p>

    <?php endif; ?>

</div>

</body>
</html>
