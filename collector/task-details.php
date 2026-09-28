<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("collector");

$delivery_id = (int)($_GET["id"] ?? 0);

if ($delivery_id <= 0) {
    die("Invalid task.");
}

$sql = "
    SELECT
        d.*,

        fd.food_name,
        fd.food_category,
        fd.description,
        fd.quantity,
        fd.unit,
        fd.food_photo,
        fd.city,
        fd.area,

        donor.name AS donor_name,
        donor.phone AS donor_phone,

        recipient.name AS recipient_name,
        recipient.phone AS recipient_phone,
        recipient.address AS recipient_address,
        recipient.city AS recipient_city,
        recipient.area AS recipient_area

    FROM deliveries d

    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id

    INNER JOIN users donor
        ON fd.donor_id = donor.user_id

    LEFT JOIN food_requests fr
        ON d.request_id = fr.request_id

    LEFT JOIN users recipient
        ON fr.recipient_id = recipient.user_id

    WHERE d.delivery_id = ?

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
    die("Task not found.");
}

$task = $result->fetch_assoc();

$stmt->close();

require_once "../includes/header.php";
?>

<div class="container">

    <h1>Task Details</h1>


    <div class="details-card">

        <?php if (!empty($task["food_photo"])): ?>

            <img
                src="../uploads/food/<?= htmlspecialchars($task["food_photo"]) ?>"
                class="food-image"
                alt="Food"
            >

        <?php endif; ?>


        <h2>
            <?= htmlspecialchars($task["food_name"]) ?>
        </h2>


        <h3>Food Information</h3>

        <p>
            <strong>Category:</strong>
            <?= htmlspecialchars($task["food_category"]) ?>
        </p>

        <p>
            <strong>Description:</strong>
            <?= htmlspecialchars($task["description"]) ?>
        </p>

        <p>
            <strong>Quantity:</strong>
            <?= htmlspecialchars($task["quantity"]) ?>
            <?= htmlspecialchars($task["unit"]) ?>
        </p>


        <h3>Pickup Information</h3>

        <p>
            <strong>Donor:</strong>
            <?= htmlspecialchars($task["donor_name"]) ?>
        </p>

        <p>
            <strong>Phone:</strong>
            <?= htmlspecialchars($task["donor_phone"]) ?>
        </p>

        <p>
            <strong>Location:</strong>
            <?= htmlspecialchars($task["area"]) ?>,
            <?= htmlspecialchars($task["city"]) ?>
        </p>


        <h3>Recipient Information</h3>

        <?php if (!empty($task["recipient_name"])): ?>

            <p>
                <strong>Name:</strong>
                <?= htmlspecialchars($task["recipient_name"]) ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?= htmlspecialchars($task["recipient_phone"]) ?>
            </p>

            <p>
                <strong>Address:</strong>
                <?= htmlspecialchars($task["recipient_address"]) ?>
            </p>

            <p>
                <strong>Location:</strong>
                <?= htmlspecialchars($task["recipient_area"]) ?>,
                <?= htmlspecialchars($task["recipient_city"]) ?>
            </p>

        <?php else: ?>

            <p>Recipient information is not available.</p>

        <?php endif; ?>


        <h3>Delivery Status</h3>

        <p>
            <?= htmlspecialchars($task["status"]) ?>
        </p>


        <?php if (empty($task["collector_id"])): ?>

            <a
                href="accept-task.php?id=<?= $delivery_id ?>"
                class="btn accept"
            >
                Accept Task
            </a>

        <?php else: ?>

            <div class="alert">
                This task has already been accepted by a collector.
            </div>

        <?php endif; ?>


        <a href="available-tasks.php" class="btn back">
            Back
        </a>

    </div>

</div>


<style>

.container {
    max-width: 900px;
    margin: 30px auto;
    padding: 20px;
}

.details-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 3px 12px rgba(0,0,0,.08);
}

.food-image {
    width: 100%;
    max-width: 500px;
    height: 300px;
    object-fit: cover;
    border-radius: 12px;
}

.details-card h3 {
    margin-top: 25px;
}

.btn {
    display: inline-block;
    padding: 11px 20px;
    margin: 15px 8px 0 0;
    border-radius: 8px;
    text-decoration: none;
    color: white;
    background: #333;
}

.accept {
    background: #2e7d32;
}

.back {
    background: #555;
}

</style>

<?php require_once "../includes/footer.php"; ?>
