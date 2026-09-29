<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$ngo_id = (int)$_SESSION['user_id'];
$donation_id = (int)($_GET['id'] ?? 0);

if ($donation_id <= 0) {
    header("Location: available-donations.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Check donation
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT donation_id, donor_id, food_name, status
    FROM food_donations
    WHERE donation_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $donation_id);
$stmt->execute();

$donation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$donation) {
    $_SESSION['ngo_error'] = "Donation not found.";
    header("Location: available-donations.php");
    exit;
}

if ($donation['status'] !== 'available') {
    $_SESSION['ngo_error'] = "This donation is no longer available.";
    header("Location: available-donations.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Prevent duplicate NGO delivery
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT delivery_id
    FROM deliveries
    WHERE donation_id = ?
    AND ngo_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $donation_id, $ngo_id);
$stmt->execute();

$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $_SESSION['ngo_error'] = "You have already accepted this donation.";
    header("Location: my-donations.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Find an available delivery enum value
|--------------------------------------------------------------------------
*/

$method = "ngo";

/* Check whether ngo exists in method enum */
$enum_query = $conn->query("
    SELECT COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'deliveries'
    AND COLUMN_NAME = 'method'
    LIMIT 1
");

if ($enum_query && ($enum_row = $enum_query->fetch_assoc())) {

    preg_match_all(
        "/'([^']+)'/",
        $enum_row['COLUMN_TYPE'],
        $matches
    );

    $methods = $matches[1] ?? [];

    if (in_array('ngo', $methods, true)) {
        $method = 'ngo';
    } elseif (in_array('self', $methods, true)) {
        $method = 'self';
    } elseif (!empty($methods)) {
        $method = $methods[0];
    }
}

/*
|--------------------------------------------------------------------------
| Find pending status
|--------------------------------------------------------------------------
*/

$pending_status = "pending";

$status_query = $conn->query("
    SELECT COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'deliveries'
    AND COLUMN_NAME = 'status'
    LIMIT 1
");

if ($status_query && ($status_row = $status_query->fetch_assoc())) {

    preg_match_all(
        "/'([^']+)'/",
        $status_row['COLUMN_TYPE'],
        $matches
    );

    $statuses = $matches[1] ?? [];

    if (in_array('pending', $statuses, true)) {
        $pending_status = 'pending';
    } elseif (!empty($statuses)) {
        $pending_status = $statuses[0];
    }
}

/*
|--------------------------------------------------------------------------
| Create delivery
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO deliveries
    (
        donation_id,
        request_id,
        collector_id,
        ngo_id,
        method,
        status,
        notes
    )
    VALUES (?, NULL, NULL, ?, ?, ?, ?)
");

$notes = "Donation accepted by NGO.";

$stmt->bind_param(
    "iisss",
    $donation_id,
    $ngo_id,
    $method,
    $pending_status,
    $notes
);

$success = $stmt->execute();
$stmt->close();

if (!$success) {
    $_SESSION['ngo_error'] = "Unable to accept donation.";
    header("Location: donation-details.php?id=" . $donation_id);
    exit;
}

/*
|--------------------------------------------------------------------------
| Update donation status only if accepted value exists
|--------------------------------------------------------------------------
|
| We use INFORMATION_SCHEMA so we don't guess the ENUM.
|--------------------------------------------------------------------------
*/

$accepted_status = null;

$status_query = $conn->query("
    SELECT COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'food_donations'
    AND COLUMN_NAME = 'status'
    LIMIT 1
");

if ($status_query && ($status_row = $status_query->fetch_assoc())) {

    preg_match_all(
        "/'([^']+)'/",
        $status_row['COLUMN_TYPE'],
        $matches
    );

    $statuses = $matches[1] ?? [];

    foreach (['accepted', 'reserved', 'assigned'] as $candidate) {
        if (in_array($candidate, $statuses, true)) {
            $accepted_status = $candidate;
            break;
        }
    }
}

if ($accepted_status !== null) {

    $stmt = $conn->prepare("
        UPDATE food_donations
        SET status = ?
        WHERE donation_id = ?
    ");

    $stmt->bind_param(
        "si",
        $accepted_status,
        $donation_id
    );

    $stmt->execute();
    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| Notification to donor
|--------------------------------------------------------------------------
*/

$title = "Donation Accepted";
$message = "Your food donation has been accepted by an NGO.";

$stmt = $conn->prepare("
    INSERT INTO notifications
    (
        user_id,
        title,
        message,
        notification_type,
        related_id,
        is_read
    )
    VALUES (?, ?, ?, ?, ?, 0)
");

$type = "donation";

$stmt->bind_param(
    "isssi",
    $donation['donor_id'],
    $title,
    $message,
    $type,
    $donation_id
);

$stmt->execute();
$stmt->close();

$_SESSION['ngo_success'] = "Donation accepted successfully.";

header("Location: my-donations.php");
exit;
