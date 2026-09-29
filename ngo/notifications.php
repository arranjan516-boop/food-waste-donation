<?php
session_start();

require_once "../config/database.php";
require_once "../includes/ngo-layout.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$ngo_id = (int)$_SESSION['user_id'];

/* Mark notification as read */
if (isset($_GET['read'])) {

    $notification_id = (int)$_GET['read'];

    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = ?
        AND user_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $notification_id,
        $ngo_id
    );

    $stmt->execute();
    $stmt->close();

    header("Location: notifications.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT
        notification_id,
        title,
        message,
        notification_type,
        related_id,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY notification_id DESC
");

$stmt->bind_param("i", $ngo_id);
$stmt->execute();

$notifications = $stmt->get_result();

ngo_header("Notifications");
?>

<div class="ngo-content">

    <div class="ngo-page-header">

        <div>
            <h1>Notifications</h1>
            <p>Stay updated with donation and delivery activities.</p>
        </div>

    </div>

    <section class="ngo-section">

        <?php if ($notifications->num_rows > 0): ?>

            <?php while ($notification = $notifications->fetch_assoc()): ?>

                <div class="ngo-notification
                    <?= $notification['is_read'] ? '' : 'ngo-notification-unread' ?>">

                    <div class="ngo-notification-icon">
                        🔔
                    </div>

                    <div class="ngo-notification-content">

                        <h3>
                            <?= htmlspecialchars($notification['title']) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($notification['message']) ?>
                        </p>

                        <small>
                            <?= htmlspecialchars($notification['created_at']) ?>
                        </small>

                    </div>

                    <?php if (!$notification['is_read']): ?>

                        <a
                            href="?read=<?= (int)$notification['notification_id'] ?>"
                            class="ngo-btn ngo-btn-small">
                            Mark Read
                        </a>

                    <?php endif; ?>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="ngo-empty">
                <div>🔔</div>
                <h3>No notifications</h3>
                <p>You are all caught up.</p>
            </div>

        <?php endif; ?>

    </section>

</div>

<?php
$stmt->close();
ngo_footer();
?>
