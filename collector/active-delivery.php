<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("collector");

$collector_id = $_SESSION["user_id"];

$delivery_id = (int)($_GET["id"] ?? 0);

if ($delivery_id <= 0) {
    die("Invalid delivery.");
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
        fd.city AS donor_city,
        fd.area AS donor_area,

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

    WHERE
        d.delivery_id = ?
        AND d.collector_id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "ii",
    $delivery_id,
    $collector_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    die("Active delivery not found.");
}

$task = $result->fetch_assoc();

$stmt->close();

require_once "../includes/header.php";
?>

<div class="container">

    <h1>Active Delivery</h1>


    <div class="delivery-card">

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


        <h3>Food Details</h3>

        <p>
            <strong>Category:</strong>
            <?= htmlspecialchars($task["food_category"]) ?>
        </p>

        <p>
            <strong>Quantity:</strong>
            <?= htmlspecialchars($task["quantity"]) ?>
            <?= htmlspecialchars($task["unit"]) ?>
        </p>


        <h3>Pickup From Donor</h3>

        <p>
            <strong>Name:</strong>
            <?= htmlspecialchars($task["donor_name"]) ?>
        </p>

        <p>
            <strong>Phone:</strong>
            <?= htmlspecialchars($task["donor_phone"]) ?>
        </p>

        <p>
            <strong>Location:</strong>
            <?= htmlspecialchars($task["donor_area"]) ?>,
            <?= htmlspecialchars($task["donor_city"]) ?>
        </p>


        <h3>Deliver To Recipient</h3>

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

        <?php endif; ?>


        <h3>Current Status</h3>

        <div class="status">
            <?= htmlspecialchars($task["status"]) ?>
        </div>


        <div class="actions">

            <a
                href="update-status.php?id=<?= $delivery_id ?>&status=pickup"
                class="btn"
            >
                Confirm Pickup
            </a>

            <a
                href="update-status.php?id=<?= $delivery_id ?>&status=delivered"
                class="btn success"
            >
                Mark Delivered
            </a>

        </div>

    </div>

</div>


<style>

.container {
    max-width: 900px;
    margin: 30px auto;
    padding: 20px;
}

.delivery-card {
    padding: 30px;
    background: white;
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

.delivery-card h3 {
    margin-top: 25px;
}

.status {
    display: inline-block;
    padding: 10px 18px;
    background: #eee;
    border-radius: 8px;
}

.actions {
    margin-top: 25px;
}

.btn {
    display: inline-block;
    padding: 11px 20px;
    margin-right: 10px;
    color: white;
    background: #333;
    text-decoration: none;
    border-radius: 8px;
}

.success {
    background: #2e7d32;
}

</style>

<?php require_once "../includes/footer.php"; ?>
