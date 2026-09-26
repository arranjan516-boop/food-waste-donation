<?php

require_once __DIR__ . "/functions.php";

/*
|--------------------------------------------------------------------------
| Notification Functions
|--------------------------------------------------------------------------
*/

function get_unread_notification_count($user_id)
{
    global $conn;

    $sql = "SELECT COUNT(*) AS total
            FROM notifications
            WHERE user_id = ? AND is_read = 0";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['total'] ?? 0;
}


function mark_notification_as_read($notification_id, $user_id)
{
    global $conn;

    $sql = "UPDATE notifications
            SET is_read = 1
            WHERE notification_id = ? AND user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $user_id);

    return $stmt->execute();
}


function mark_all_notifications_as_read($user_id)
{
    global $conn;

    $sql = "UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    return $stmt->execute();
}
?>
