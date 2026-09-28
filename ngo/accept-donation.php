<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";
require_once "../includes/notification-functions.php";

require_role("ngo");

$ngo_id = $_SESSION["user_id"];

$donation_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($donation_id <= 0) {
    die("Invalid donation ID.");
}

/*
|--------------------------------------------------------------------------
| Check whether donation exists
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT donation_id, donor_id, food_name
    FROM food_donations
    WHERE donation_id = ?
");

$stmt->bind_param("i", $donation_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Donation not found.");
}

$food = $result->fetch_assoc();

$donor_id = $food["donor_id"];
$food_name = $food["food_name"];

/*
|--------------------------------------------------------------------------
| Check existing delivery
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT delivery_id, ngo_id, collector_id
    FROM deliveries
    WHERE donation_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $donation_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $delivery = $result->fetch_assoc();

    if (!empty($delivery["ngo_id"]) || !empty($delivery["collector_id"])) {
        header("Location: my-donations.php?error=already_taken");
        exit;
    }

    $delivery_id = $delivery["delivery_id"];

    /*
    |--------------------------------------------------------------------------
    | Existing delivery - assign NGO
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        UPDATE deliveries
        SET ngo_id = ?
        WHERE delivery_id = ?
    ");

    $stmt->bind_param("ii", $ngo_id, $delivery_id);
    $stmt->execute();

} else {

    /*
    |--------------------------------------------------------------------------
    | Find valid method enum value
    |--------------------------------------------------------------------------
    */

    $method = "ngo";

    $enum_sql = "
        SELECT COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'deliveries'
        AND COLUMN_NAME = 'method'
    ";

    $enum_result = $conn->query($enum_sql);

    if ($enum_result && $enum_result->num_rows > 0) {

        $enum_row = $enum_result->fetch_assoc();

        preg_match_all(
            "/'([^']+)'/",
            $enum_row["COLUMN_TYPE"],
            $matches
        );

        $values = $matches[1];

        if (!in_array("ngo", $values)) {

            $possible = [
                "NGO",
                "ngo_delivery",
                "ngo-pickup",
                "pickup",
                "collection"
            ];

            $found = false;

            foreach ($possible as $value) {
                if (in_array($value, $values)) {
                    $method = $value;
                    $found = true;
                    break;
                }
            }

            if (!$found && !empty($values)) {
                $method = $values[0];
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Find valid status enum value
    |--------------------------------------------------------------------------
    */

    $status = "pending";

    $status_sql = "
        SELECT COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'deliveries'
        AND COLUMN_NAME = 'status'
    ";

    $status_result = $conn->query($status_sql);

    if ($status_result && $status_result->num_rows > 0) {

        $status_row = $status_result->fetch_assoc();

        preg_match_all(
            "/'([^']+)'/",
            $status_row["COLUMN_TYPE"],
            $status_matches
        );

        $status_values = $status_matches[1];

        if (!in_array("pending", $status_values) && !empty($status_values)) {
            $status = $status_values[0];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create delivery
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        INSERT INTO deliveries
        (
            donation_id,
            ngo_id,
            method,
            status
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iiss",
        $donation_id,
        $ngo_id,
        $method,
        $status
    );

    if (!$stmt->execute()) {
        die("Unable to accept donation: " . $stmt->error);
    }

    $delivery_id = $conn->insert_id;
}

/*
|--------------------------------------------------------------------------
| Notify donor
|--------------------------------------------------------------------------
*/

create_notification(
    $conn,
    $donor_id,
    "Donation Accepted",
    $_SESSION["name"] . " accepted your food donation: " . $food_name,
    "donation",
    $donation_id
);

header("Location: my-donations.php?success=accepted");
exit;
?>
