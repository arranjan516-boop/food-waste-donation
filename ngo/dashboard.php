<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

$ngo_id = $_SESSION["user_id"];

$total_donations = 0;
$my_donations = 0;
$completed = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM food_donations fd
    LEFT JOIN deliveries d ON fd.donation_id = d.donation_id
    WHERE d.delivery_id IS NULL
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_donations = $row["total"];
}

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE ngo_id = ?
");
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$my_donations = $row["total"];

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE ngo_id = ?
    AND status = 'delivered'
");
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$completed = $row["total"];
?>

<!DOCTYPE html>
<html>
<head>
    <title>NGO Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>NGO Dashboard</h1>

    <p>
        Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!
    </p>

    <div class="dashboard-cards">

        <div class="dashboard-card">
            <h3>Available Donations</h3>
            <p><?php echo $total_donations; ?></p>
            <a href="available-donations.php">View Donations</a>
        </div>

        <div class="dashboard-card">
            <h3>My Donations</h3>
            <p><?php echo $my_donations; ?></p>
            <a href="my-donations.php">View My Donations</a>
        </div>

        <div class="dashboard-card">
            <h3>Completed</h3>
            <p><?php echo $completed; ?></p>
            <a href="completed-donations.php">View Completed</a>
        </div>

        <div class="dashboard-card">
            <h3>Notifications</h3>
            <a href="notifications.php">View Notifications</a>
        </div>

    </div>

    <hr>

    <h2>NGO Actions</h2>

    <p><a href="available-donations.php">View Available Donations</a></p>
    <p><a href="my-donations.php">My Donations</a></p>
    <p><a href="pickup-schedule.php">Pickup Schedule</a></p>
    <p><a href="collection-status.php">Collection Status</a></p>
    <p><a href="distribution.php">Distribution</a></p>
    <p><a href="completed-donations.php">Completed Donations</a></p>
    <p><a href="history.php">History</a></p>
    <p><a href="notifications.php">Notifications</a></p>
    <p><a href="feedback.php">Feedback</a></p>
    <p><a href="profile.php">My Profile</a></p>

</div>

</body>
</html>
