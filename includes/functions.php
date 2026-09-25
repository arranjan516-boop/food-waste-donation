<?php

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


function redirect_by_role($role)
{
    $pages = [

        "donor" =>
        "donor/dashboard.php",

        "recipient" =>
        "recipient/dashboard.php",

        "collector" =>
        "collector/dashboard.php",

        "ngo" =>
        "ngo/dashboard.php",

        "admin" =>
        "admin/dashboard.php"
    ];

    if (isset($pages[$role])) {

        header(
            "Location: " .
            BASE_URL .
            $pages[$role]
        );

        exit;
    }

    header(
        "Location: " .
        BASE_URL .
        "index.php"
    );

    exit;
}


function create_notification(
    $conn,
    $user_id,
    $title,
    $message,
    $type = "general",
    $related_id = null
) {

    $sql = "
        INSERT INTO notifications
        (
            user_id,
            title,
            message,
            notification_type,
            related_id
        )
        VALUES (?, ?, ?, ?, ?)
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

?>
