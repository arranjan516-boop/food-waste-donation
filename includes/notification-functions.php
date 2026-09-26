<?php

/*
|--------------------------------------------------------------------------
| CREATE NOTIFICATION
|--------------------------------------------------------------------------
| Creates a notification for a user.
*/

function create_notification(
    $user_id,
    $title,
    $message,
    $type = 'general',
    $related_id = null
) {

    global $conn;

    $sql = "
        INSERT INTO notifications
        (
            user_id,
            title,
            message,
            type,
            related_id,
            is_read,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, 0, NOW())
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "isssi",
        $user_id,
        $title,
        $message,
        $type,
        $related_id
    );

    return $stmt->execute();
}


/*
|--------------------------------------------------------------------------
| GET UNREAD NOTIFICATION COUNT
|--------------------------------------------------------------------------
*/

function get_unread_notification_count($user_id)
{
    global $conn;

    $sql = "
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE user_id = ?
        AND is_read = 0
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return (int)$row['total'];
}


/*
|--------------------------------------------------------------------------
| MARK NOTIFICATION AS READ
|--------------------------------------------------------------------------
*/

function mark_notification_as_read($notification_id, $user_id)
{
    global $conn;

    $sql = "
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = ?
        AND user_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "ii",
        $notification_id,
        $user_id
    );

    return $stmt->execute();
}


/*
|--------------------------------------------------------------------------
| MARK ALL NOTIFICATIONS AS READ
|--------------------------------------------------------------------------
*/

function mark_all_notifications_as_read($user_id)
{
    global $conn;

    $sql = "
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $user_id);

    return $stmt->execute();
}

?>
