<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";
require_once "../includes/notification-functions.php";

require_role("ngo");

$ngo_id = $_SESSION["user_id"];

$delivery_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($delivery_id <= 0) {
    die("Invalid delivery ID.");
}

$stmt = $conn->prepare("
    SELECT
        d.*,
        fd.food_name,
        fd.quantity,
        fd.unit,
        fd.donor_id
    FROM deliveries d
    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id
    WHERE d.delivery_id = ?
    AND d.ngo_id = ?
");

$stmt->bind_param("ii", $delivery_id, $ngo_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Delivery not found.");
}

$data = $result->fetch_assoc();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $notes = trim($_POST["notes"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Get valid delivered/pickup status
    |--------------------------------------------------------------------------
    */

    $status = "picked_up";

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

        if (!in_array("picked_up", $values)) {

            $possible = [
                "pickup",
                "collected",
                "collected_food",
                "in_transit"
            ];

            $found = false;

            foreach ($possible as $value) {
                if (in_array($value, $values)) {
                    $status = $value;
                    $found = true;
                    break;
                }
            }

            if (!$found && !empty($values)) {
                $status = $values[0];
            }
        }
    }

    $stmt = $conn->prepare("
        UPDATE deliveries
        SET status = ?, notes = ?, confirmed_at = NOW()
        WHERE delivery_id = ?
        AND ngo_id = ?
    ");

    $stmt->bind_param(
        "ssii",
        $status,
        $notes,
        $delivery_id,
        $ngo_id
    );

    if ($stmt->execute()) {

        create_notification(
            $conn,
            $data["donor_id"],
            "Food Collected",
            "NGO collected your food donation: " . $data["food_name"],
            "delivery",
            $delivery_id
        );

        $message = "Collection status updated successfully.";

        $data["status"] = $status;
        $data["notes"] = $notes;

    } else {

        $message = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Collection Status</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>Collection Status</h1>

    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <h2>
        <?php echo htmlspecialchars($data["food_name"]); ?>
    </h2>

    <p>
        Quantity:
        <?php echo htmlspecialchars($data["quantity"]); ?>
        <?php echo htmlspecialchars($data["unit"]); ?>
    </p>

    <p>
        Current Status:
        <strong><?php echo htmlspecialchars($data["status"]); ?></strong>
    </p>

    <form method="POST">

        <label>Collection Notes</label>
        <br>

        <textarea
            name="notes"
            rows="5"
            cols="50"
            placeholder="Enter collection notes"
        ><?php echo htmlspecialchars($data["notes"] ?? ""); ?></textarea>

        <br><br>

        <button type="submit">
            Confirm Food Collection
        </button>

    </form>

</div>

</body>
</html>
