<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("collector");

$collector_id = $_SESSION["user_id"];

$page_title = "My Tasks";

$sql = "
    SELECT
        d.delivery_id,
        d.donation_id,
        d.request_id,
        d.method,
        d.status,

        fd.food_name,
        fd.food_category,
        fd.quantity,
        fd.unit,
        fd.city,
        fd.area,
        fd.food_photo

    FROM deliveries d

    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id

    WHERE d.collector_id = ?

    ORDER BY d.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $collector_id);
$stmt->execute();

$result = $stmt->get_result();

require_once "../includes/header.php";
?>

<div class="container">

    <h1>My Tasks</h1>

    <?php if ($result->num_rows === 0): ?>

        <div class="alert">
            You have not accepted any tasks yet.
        </div>

    <?php else: ?>

        <div class="task-grid">

            <?php while ($task = $result->fetch_assoc()): ?>

                <div class="task-card">

                    <?php if (!empty($task["food_photo"])): ?>

                        <img
                            src="../uploads/food/<?= htmlspecialchars($task["food_photo"]) ?>"
                            alt="Food"
                        >

                    <?php endif; ?>

                    <h2>
                        <?= htmlspecialchars($task["food_name"]) ?>
                    </h2>

                    <p>
                        <strong>Quantity:</strong>
                        <?= htmlspecialchars($task["quantity"]) ?>
                        <?= htmlspecialchars($task["unit"]) ?>
                    </p>

                    <p>
                        <strong>Location:</strong>
                        <?= htmlspecialchars($task["area"]) ?>,
                        <?= htmlspecialchars($task["city"]) ?>
                    </p>

                    <p>
                        <strong>Status:</strong>
                        <?= htmlspecialchars($task["status"]) ?>
                    </p>

                    <a
                        href="active-delivery.php?id=<?= (int)$task["delivery_id"] ?>"
                        class="btn"
                    >
                        Manage Task
                    </a>

                </div>

            <?php endwhile; ?>

        </div>

    <?php endif; ?>

</div>


<style>

.container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}

.task-grid {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 20px;
}

.task-card {
    padding: 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 3px 12px rgba(0,0,0,.08);
}

.task-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 10px;
}

.btn {
    display: inline-block;
    margin-top: 10px;
    padding: 10px 18px;
    background: #333;
    color: white;
    text-decoration: none;
    border-radius: 8px;
}

@media(max-width:900px) {
    .task-grid {
        grid-template-columns: repeat(2,1fr);
    }
}

@media(max-width:600px) {
    .task-grid {
        grid-template-columns: 1fr;
    }
}

</style>

<?php
$stmt->close();
require_once "../includes/footer.php";
?>
