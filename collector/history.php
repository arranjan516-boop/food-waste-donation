<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("collector");

$collector_id = $_SESSION["user_id"];

$page_title = "Delivery History";

$sql = "
    SELECT
        d.delivery_id,
        d.status,
        d.created_at,
        d.delivered_at,

        fd.food_name,
        fd.quantity,
        fd.unit

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

    <h1>Delivery History</h1>

    <?php if ($result->num_rows === 0): ?>

        <div class="alert">
            No delivery history found.
        </div>

    <?php else: ?>

        <?php while ($row = $result->fetch_assoc()): ?>

            <div class="history-card">

                <h2>
                    <?= htmlspecialchars($row["food_name"]) ?>
                </h2>

                <p>
                    Quantity:
                    <?= htmlspecialchars($row["quantity"]) ?>
                    <?= htmlspecialchars($row["unit"]) ?>
                </p>

                <p>
                    Status:
                    <strong>
                        <?= htmlspecialchars($row["status"]) ?>
                    </strong>
                </p>

                <p>
                    Created:
                    <?= htmlspecialchars($row["created_at"]) ?>
                </p>

                <?php if (!empty($row["delivered_at"])): ?>

                    <p>
                        Delivered:
                        <?= htmlspecialchars($row["delivered_at"]) ?>
                    </p>

                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>

</div>


<style>

.container {
    max-width: 900px;
    margin: 30px auto;
    padding: 20px;
}

.history-card {
    background: white;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,.08);
}

</style>

<?php
$stmt->close();
require_once "../includes/footer.php";
?>
