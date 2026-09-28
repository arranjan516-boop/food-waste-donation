
<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("collector");

$collector_id = $_SESSION["user_id"];

$page_title = "Completed Tasks";

$sql = "
    SELECT
        d.delivery_id,
        d.status,
        d.delivered_at,

        fd.food_name,
        fd.food_category,
        fd.quantity,
        fd.unit,
        fd.city,
        fd.area

    FROM deliveries d

    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id

    WHERE
        d.collector_id = ?
        AND d.status = 'delivered'

    ORDER BY d.delivered_at DESC
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

    <h1>Completed Tasks</h1>

    <?php if ($result->num_rows === 0): ?>

        <div class="alert">
            You have not completed any delivery yet.
        </div>

    <?php else: ?>

        <div class="task-list">

            <?php while ($task = $result->fetch_assoc()): ?>

                <div class="task">

                    <h2>
                        <?= htmlspecialchars($task["food_name"]) ?>
                    </h2>

                    <p>
                        <strong>Category:</strong>
                        <?= htmlspecialchars($task["food_category"]) ?>
                    </p>

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
                        <strong>Delivered:</strong>
                        <?= htmlspecialchars($task["delivered_at"]) ?>
                    </p>

                    <strong>✅ Delivered</strong>

                </div>

            <?php endwhile; ?>

        </div>

    <?php endif; ?>

</div>


<style>

.container {
    max-width: 1000px;
    margin: 30px auto;
    padding: 20px;
}

.task {
    padding: 20px;
    margin-bottom: 15px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,.08);
}

</style>

<?php
$stmt->close();
require_once "../includes/footer.php";
?>
