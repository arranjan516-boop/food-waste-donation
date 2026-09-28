<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";
require_once "../includes/notification-functions.php";

require_role("collector");

$user_name = $_SESSION["name"] ?? "Collector";

$page_title = "Collector Dashboard";

require_once "../includes/header.php";
?>

<div class="dashboard-container">

    <div class="dashboard-header">
        <h1>Collector Dashboard</h1>

        <p>
            Welcome,
            <strong><?= htmlspecialchars($user_name) ?></strong>
        </p>

        <p>
            Help collect surplus food and deliver it to people who need it.
        </p>
    </div>


    <div class="dashboard-cards">

        <div class="dashboard-card">
            <div class="card-icon">📦</div>

            <h2>Available Tasks</h2>

            <p>
                View food collection requests available for collectors.
            </p>

            <a href="available-tasks.php" class="dashboard-btn">
                View Tasks
            </a>
        </div>


        <div class="dashboard-card">
            <div class="card-icon">🚚</div>

            <h2>My Tasks</h2>

            <p>
                View tasks that you have accepted.
            </p>

            <a href="my-tasks.php" class="dashboard-btn">
                My Tasks
            </a>
        </div>


        <div class="dashboard-card">
            <div class="card-icon">📍</div>

            <h2>Active Delivery</h2>

            <p>
                Manage your current food collection and delivery.
            </p>

            <a href="active-delivery.php" class="dashboard-btn">
                Active Delivery
            </a>
        </div>


        <div class="dashboard-card">
            <div class="card-icon">✅</div>

            <h2>Completed Tasks</h2>

            <p>
                View your completed delivery tasks.
            </p>

            <a href="completed-tasks.php" class="dashboard-btn">
                Completed Tasks
            </a>
        </div>


        <div class="dashboard-card">
            <div class="card-icon">🔔</div>

            <h2>Notifications</h2>

            <p>
                View your latest notifications.
            </p>

            <a href="notifications.php" class="dashboard-btn">
                Notifications
            </a>
        </div>


        <div class="dashboard-card">
            <div class="card-icon">👤</div>

            <h2>My Profile</h2>

            <p>
                View and update your collector profile.
            </p>

            <a href="profile.php" class="dashboard-btn">
                My Profile
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
    padding: 25px;
    margin-bottom: 25px;
    border-radius: 15px;
    background: #f5f7fa;
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
    padding: 25px;
    background: white;
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
    padding: 10px 18px;
    margin-top: 10px;
    background: #333;
    color: white;
    text-decoration: none;
    border-radius: 8px;
}

.dashboard-btn:hover {
    opacity: 0.85;
}

@media (max-width: 900px) {
    .dashboard-cards {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .dashboard-cards {
        grid-template-columns: 1fr;
    }
}

</style>

<?php require_once "../includes/footer.php"; ?>
