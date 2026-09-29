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

/*
|--------------------------------------------------------------------------
| Notification
|--------------------------------------------------------------------------
*/

$title = "Donation Request Declined";
$message = "An NGO has declined this donation request.";

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

$_SESSION['ngo_success'] = "Donation rejected.";

header("Location: available-donations.php");
exit;
