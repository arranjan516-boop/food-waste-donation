<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";
require_once "../includes/notification-functions.php";

require_role("collector");

$user_id = $_SESSION["user_id"];

$page_title = "Notifications";

$sql = "
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

require_once "../includes/header.php";
?>

<div class="container">

    <h1>Notifications</h1>

    <?php if ($result->num_rows === 0): ?>

        <div class="alert">
            No notifications available.
        </div>

    <?php else: ?>

        <?php while ($notification = $result->fetch_assoc()): ?>

            <div class="notification">

                <h3>
                    <?= htmlspecialchars($notification["title"]) ?>
                </h3>

                <p>
                    <?= htmlspecialchars($notification["message"]) ?>
                </p>

                <small>
                    <?= htmlspecialchars($notification["created_at"]) ?>
                </small>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>

</div>


<style>

.container {
    max-width: 900px;
    margin: 30px auto;
    padding: 20px;
}

.notification {
    padding: 20px;
    margin-bottom: 15px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,.08);
}

.notification h3 {
    margin-bottom: 8px;
}

.notification small {
    color: #777;
}

</style>

<?php
$stmt->close();
require_once "../includes/footer.php";
?>
