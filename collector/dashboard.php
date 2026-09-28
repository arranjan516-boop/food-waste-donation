<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";
require_once "../includes/notification-functions.php";

require_role("collector");

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["name"] ?? "Collector";

$page_title = "Collector Dashboard";

require_once "../includes/header.php";
?>

<div class="dashboard-container">

    <!-- Welcome Section -->
    <div class="dashboard-header">
        <div>
            <h1>Collector Dashboard</h1>

            <p>
                Welcome,
                <strong><?= htmlspecialchars($user_name) ?></strong>
            </p>

            <p>
                Help collect surplus food and deliver it to people who need it.
            </p>
        </div>
    </div>


    <!-- Dashboard Cards -->
    <div class="dashboard-cards">

        <!-- Available Tasks -->
        <div class="dashboard-card">

            <div class="card-icon">
                📦
            </div>

            <h2>Available Tasks</h2>

            <p>
                View food collection requests available near you.
            </p>

            <a href="available-tasks.php" class="dashboard-btn">
                View Tasks
            </a>

        </div>


        <!-- My Tasks -->
        <div class="dashboard-card">

            <div class="card-icon">
                🚚
            </div>

            <h2>My Tasks</h2>

            <p>
                View the collection and delivery tasks you have accepted.
            </p>

            <a href="my-tasks.php" class="dashboard-btn">
                My Tasks
            </a>

        </div>


        <!-- Active Delivery -->
        <div class="dashboard-card">

            <div class="card-icon">
                📍
            </div>

            <h2>Active Delivery</h2>

            <p>
                Track your current pickup and delivery task.
            </p>

            <a href="active-delivery.php" class="dashboard-btn">
                Active Delivery
            </a>

        </div>


        <!-- Completed Tasks -->
        <div class="dashboard-card">

            <div class="card-icon">
                ✅
            </div>

            <h2>Completed Tasks</h2>

            <p>
                View the food collection tasks you have completed.
            </p>

            <a href="completed-tasks.php" class="dashboard-btn">
                Completed
            </a>

        </div>


        <!-- Notifications -->
        <div class="dashboard-card">

            <div class="card-icon">
                🔔
            </div>

            <h2>Notifications</h2>

            <p>
                Check new food collection requests and updates.
            </p>

            <a href="notifications.php" class="dashboard-btn">
                Notifications
            </a>

        </div>


        <!-- Profile -->
        <div class="dashboard-card">

            <div class="card-icon">
                👤
            </div>

            <h2>My Profile</h2>

            <p>
                View and update your collector profile.
            </p>

            <a href="profile.php" class="dashboard-btn">
                Profile
            </a>

        </div>

    </div>


    <!-- Quick Actions -->
    <div class="dashboard-section">

        <h2>Quick Actions</h2>

        <div class="quick-actions">

            <a href="available-tasks.php">
                🔍 Find Available Tasks
            </a>

            <a href="my-tasks.php">
                📋 View My Tasks
            </a>

            <a href="notifications.php">
                🔔 Check Notifications
            </a>

            <a href="profile.php">
                👤 View Profile
            </a>

        </div>

    </div>

</div>


<style>

.dashboard-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}

.dashboard-header {
    background: #f5f7fa;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 25px;
}

.dashboard-header h1 {
    margin-bottom: 10px;
}

.dashboard-header p {
    margin: 6px 0;
}


.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}


.dashboard-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.card-icon {
    font-size: 35px;
    margin-bottom: 10px;
}

.dashboard-card h2 {
    margin-bottom: 10px;
}

.dashboard-card p {
    color: #666;
    line-height: 1.5;
    min-height: 60px;
}

.dashboard-btn {
    display: inline-block;
    margin-top: 10px;
    padding: 10px 18px;
    background: #333;
    color: white;
    text-decoration: none;
    border-radius: 8px;
}

.dashboard-btn:hover {
    opacity: 0.85;
}


.dashboard-section {
    margin-top: 35px;
}

.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 15px;
}

.quick-actions a {
    padding: 12px 18px;
    background: #f1f1f1;
    color: #222;
    text-decoration: none;
    border-radius: 8px;
}

.quick-actions a:hover {
    background: #ddd;
}


@media (max-width: 900px) {

    .dashboard-cards {
        grid-template-columns: repeat(2, 1fr);
    }

}


@media (max-width: 600px) {

    .dashboard-container {
        padding: 10px;
    }

    .dashboard-cards {
        grid-template-columns: 1fr;
    }

    .quick-actions {
        flex-direction: column;
    }

}

</style>


<?php require_once "../includes/footer.php"; ?>
