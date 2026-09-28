<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notifications</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>Notifications</h1>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($row = $result->fetch_assoc()): ?>

            <div class="dashboard-card">

                <h3>
                    <?php echo htmlspecialchars($row["title"]); ?>
                </h3>

                <p>
                    <?php echo htmlspecialchars($row["message"]); ?>
                </p>

                <small>
                    <?php echo htmlspecialchars($row["created_at"]); ?>
                </small>

            </div>

            <br>

        <?php endwhile; ?>

    <?php else: ?>

        <p>No notifications found.</p>

    <?php endif; ?>

</div>

</body>
</html>
