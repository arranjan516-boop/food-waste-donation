<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";
require_once "../includes/notification-functions.php";

require_role("ngo");

$ngo_id = $_SESSION["user_id"];

$request_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($request_id <= 0) {
    die("Invalid request ID.");
}

$stmt = $conn->prepare("
    SELECT
        d.delivery_id,
        d.status AS delivery_status,
        d.donation_id,
        fd.food_name,
        fd.food_category,
        fd.quantity AS donated_quantity,
        fd.unit,
        fr.request_id,
        fr.quantity AS requested_quantity,
        fr.message,
        fr.recipient_id,
        u.name AS recipient_name,
        u.phone AS recipient_phone,
        u.address AS recipient_address,
        u.city AS recipient_city,
        u.area AS recipient_area
    FROM food_requests fr
    INNER JOIN food_donations fd
        ON fr.donation_id = fd.donation_id
    INNER JOIN deliveries d
        ON fd.donation_id = d.donation_id
    INNER JOIN users u
        ON fr.recipient_id = u.user_id
    WHERE fr.request_id = ?
    AND d.ngo_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $request_id, $ngo_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Distribution request not found.");
}

$data = $result->fetch_assoc();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | Find valid delivered status
    |--------------------------------------------------------------------------
    */

    $status = "delivered";

    $sql = "
        SELECT COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'deliveries'
        AND COLUMN_NAME = 'status'
    ";

    $enum_result = $conn->query($sql);

    if ($enum_result && $enum_result->num_rows > 0) {

        $row = $enum_result->fetch_assoc();

        preg_match_all(
            "/'([^']+)'/",
            $row["COLUMN_TYPE"],
            $matches
        );

        $values = $matches[1];

        if (!in_array("delivered", $values) && !empty($values)) {
            $status = end($values);
        }
    }

    $stmt = $conn->prepare("
        UPDATE deliveries
        SET
            status = ?,
            confirmed_at = NOW(),
            delivered_at = NOW()
        WHERE delivery_id = ?
        AND ngo_id = ?
    ");

    $stmt->bind_param(
        "sii",
        $status,
        $data["delivery_id"],
        $ngo_id
    );

    if ($stmt->execute()) {

        create_notification(
            $conn,
            $data["recipient_id"],
            "Food Delivered",
            "Food has been distributed to you by the NGO.",
            "delivery",
            $data["delivery_id"]
        );

        $message = "Food distribution completed successfully.";

        $data["delivery_status"] = $status;

    } else {

        $message = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Distribution Details</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>Distribution Details</h1>

    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <h2>
        <?php echo htmlspecialchars($data["food_name"]); ?>
    </h2>

    <p>
        <strong>Category:</strong>
        <?php echo htmlspecialchars($data["food_category"]); ?>
    </p>

    <p>
        <strong>Requested Quantity:</strong>
        <?php echo htmlspecialchars($data["requested_quantity"]); ?>
        <?php echo htmlspecialchars($data["unit"]); ?>
    </p>

    <hr>

    <h2>Recipient Details</h2>

    <p>
        <strong>Name:</strong>
        <?php echo htmlspecialchars($data["recipient_name"]); ?>
    </p>

    <p>
        <strong>Phone:</strong>
        <?php echo htmlspecialchars($data["recipient_phone"]); ?>
    </p>

    <p>
        <strong>Address:</strong>
        <?php echo htmlspecialchars($data["recipient_address"]); ?>
    </p>

    <p>
        <strong>City:</strong>
        <?php echo htmlspecialchars($data["recipient_city"]); ?>
    </p>

    <p>
        <strong>Area:</strong>
        <?php echo htmlspecialchars($data["recipient_area"]); ?>
    </p>

    <p>
        <strong>Delivery Status:</strong>
        <?php echo htmlspecialchars($data["delivery_status"]); ?>
    </p>

    <?php if ($data["delivery_status"] !== "delivered"): ?>

        <form method="POST">

            <button type="submit">
                Confirm Food Distributed
            </button>

        </form>

    <?php else: ?>

        <p>
            <strong>This food has already been distributed.</strong>
        </p>

    <?php endif; ?>

</div>

</body>
</html>
