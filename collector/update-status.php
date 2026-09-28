<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";
require_once "../includes/notification-functions.php";

require_role("collector");

$collector_id = $_SESSION["user_id"];

$delivery_id = (int)($_GET["id"] ?? 0);

$requested_status = $_GET["status"] ?? "";

if ($delivery_id <= 0) {
    die("Invalid delivery.");
}


/*
 * Convert our page action into database status.
 */

if ($requested_status === "pickup") {

    $new_status = "picked_up";

} elseif ($requested_status === "delivered") {

    $new_status = "delivered";

} else {

    die("Invalid status.");

}


/*
 * Check delivery.
 */

$sql = "
    SELECT
        d.delivery_id,
        d.donation_id,
        d.request_id,
        d.collector_id,

        fd.donor_id

    FROM deliveries d

    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id

    WHERE
        d.delivery_id = ?
        AND d.collector_id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "ii",
    $delivery_id,
    $collector_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    die("Delivery not found.");
}

$delivery = $result->fetch_assoc();

$stmt->close();


/*
 * Update status.
 */

if ($new_status === "delivered") {

    $sql = "
        UPDATE deliveries

        SET
            status = ?,
            delivered_at = NOW()

        WHERE
            delivery_id = ?
            AND collector_id = ?
    ";

} else {

    $sql = "
        UPDATE deliveries

        SET
            status = ?

        WHERE
            delivery_id = ?
            AND collector_id = ?
    ";
}


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "sii",
    $new_status,
    $delivery_id,
    $collector_id
);

$stmt->execute();

$stmt->close();


/*
 * Notify donor.
 */

if (!empty($delivery["donor_id"])) {

    $message =
        ($new_status === "delivered")
        ? "The food has been delivered to the recipient."
        : "The collector has picked up the food.";

    create_notification(
        $conn,
        $delivery["donor_id"],
        "Delivery Update",
        $message,
        "delivery",
        $delivery_id
    );
}


if ($new_status === "delivered") {

    header(
        "Location: completed-tasks.php?success=1"
    );

} else {

    header(
        "Location: active-delivery.php?id=" .
        $delivery_id .
        "&success=pickup"
    );
}

exit;
