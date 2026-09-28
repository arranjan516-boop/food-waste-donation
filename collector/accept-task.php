<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";
require_once "../includes/notification-functions.php";

require_role("collector");

$collector_id = $_SESSION["user_id"];

$delivery_id = (int)($_GET["id"] ?? 0);

if ($delivery_id <= 0) {
    die("Invalid task.");
}


/*
 * Check whether the task is still available.
 */

$sql = "
    SELECT
        delivery_id,
        donation_id,
        request_id,
        collector_id,
        status
    FROM deliveries
    WHERE delivery_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $delivery_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    die("Task not found.");
}

$delivery = $result->fetch_assoc();

$stmt->close();


if (!empty($delivery["collector_id"])) {

    header("Location: task-details.php?id=" . $delivery_id . "&error=already_taken");
    exit;

}


/*
 * Assign collector.
 */

$sql = "
    UPDATE deliveries
    SET collector_id = ?
    WHERE delivery_id = ?
      AND collector_id IS NULL
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "ii",
    $collector_id,
    $delivery_id
);

$stmt->execute();

if ($stmt->affected_rows === 0) {

    $stmt->close();

    header(
        "Location: task-details.php?id=" .
        $delivery_id .
        "&error=already_taken"
    );

    exit;
}

$stmt->close();


/*
 * Get recipient and donor information.
 */

$sql = "
    SELECT
        fd.donor_id,
        fr.recipient_id
    FROM deliveries d

    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id

    LEFT JOIN food_requests fr
        ON d.request_id = fr.request_id

    WHERE d.delivery_id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $delivery_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $data = $result->fetch_assoc();


        /*
         * Notify donor.
         */

        if (!empty($data["donor_id"])) {

            create_notification(
                $conn,
                $data["donor_id"],
                "Collector Accepted",
                "A collector has accepted your food delivery task.",
                "delivery",
                $delivery_id
            );

        }


        /*
         * Notify recipient.
         */

        if (!empty($data["recipient_id"])) {

            create_notification(
                $conn,
                $data["recipient_id"],
                "Collector Assigned",
                "A collector has accepted your food delivery.",
                "delivery",
                $delivery_id
            );

        }

    }

    $stmt->close();
}


header(
    "Location: active-delivery.php?id=" .
    $delivery_id .
    "&success=accepted"
);

exit;
