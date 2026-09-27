<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";
require_once "../includes/notification-functions.php";

require_role("donor");

$donor_id = $_SESSION["user_id"];

$delivery_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($delivery_id <= 0) {
    die("Invalid delivery ID.");
}


/*
|--------------------------------------------------------------------------
| Get delivery details
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        d.delivery_id,
        d.request_id,
        d.donation_id,
        d.method,
        d.status AS delivery_status,
        d.notes,
        d.confirmed_at,
        d.delivered_at,

        fd.food_name,
        fd.food_category,
        fd.quantity,
        fd.unit,
        fd.food_photo,
        fd.city AS donor_city,
        fd.area AS donor_area,

        fr.recipient_id,
        fr.quantity AS requested_quantity,
        fr.status AS request_status,

        u.name AS recipient_name,
        u.phone AS recipient_phone,
        u.address AS recipient_address,
        u.city AS recipient_city,
        u.area AS recipient_area

    FROM deliveries d

    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id

    INNER JOIN food_requests fr
        ON d.request_id = fr.request_id

    INNER JOIN users u
        ON fr.recipient_id = u.user_id

    WHERE d.delivery_id = ?
      AND fd.donor_id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $delivery_id,
    $donor_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Delivery record not found.");
}

$delivery = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| Read delivery status enum
|--------------------------------------------------------------------------
*/

$status_sql = "
    SELECT COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'deliveries'
      AND COLUMN_NAME = 'status'
    LIMIT 1
";

$status_result = $conn->query($status_sql);

$allowed_statuses = [];

if ($status_result && $status_result->num_rows > 0) {

    $status_row = $status_result->fetch_assoc();

    preg_match_all(
        "/'([^']*)'/",
        $status_row["COLUMN_TYPE"],
        $matches
    );

    $allowed_statuses = $matches[1] ?? [];
}


/*
|--------------------------------------------------------------------------
| Find delivered status
|--------------------------------------------------------------------------
*/

$delivered_status = null;

foreach ($allowed_statuses as $status) {

    if (strtolower($status) === "delivered") {

        $delivered_status = $status;
        break;
    }
}


/*
|--------------------------------------------------------------------------
| Confirm delivery
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    if ($action === "confirm_delivery") {

        if ($delivered_status === null) {

            echo "<h2>Delivery status configuration problem</h2>";

            echo "<p>The deliveries.status column does not contain a 'delivered' value.</p>";

            echo "<p>Available statuses:</p>";

            echo "<ul>";

            foreach ($allowed_statuses as $status) {

                echo "<li>" .
                     htmlspecialchars($status) .
                     "</li>";
            }

            echo "</ul>";

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Update delivery
        |--------------------------------------------------------------------------
        */

        $update_sql = "
            UPDATE deliveries
            SET
                status = ?,
                confirmed_at = NOW(),
                delivered_at = NOW()
            WHERE delivery_id = ?
        ";

        $update_stmt = $conn->prepare($update_sql);

        $update_stmt->bind_param(
            "si",
            $delivered_status,
            $delivery_id
        );

        $update_stmt->execute();

        $update_stmt->close();


        /*
        |--------------------------------------------------------------------------
        | Notify recipient
        |--------------------------------------------------------------------------
        */

        create_notification(
            $conn,
            $delivery["recipient_id"],
            "Food Delivered",
            $delivery["food_name"] .
            " has been delivered by the donor.",
            "delivery",
            $delivery_id
        );


        /*
        |--------------------------------------------------------------------------
        | Redirect to avoid duplicate form submission
        |--------------------------------------------------------------------------
        */

        header(
            "Location: self-delivery.php?id=" .
            $delivery_id .
            "&success=1"
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Success message
|--------------------------------------------------------------------------
*/

$success = isset($_GET["success"]) && $_GET["success"] == "1";

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Self Delivery
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/dashboard.css"
    >

</head>


<body>


<?php include "../includes/navbar.php"; ?>


<div class="container">

    <h1>Self Delivery</h1>


    <?php if ($success): ?>

        <div class="card">

            <h2>✅ Delivery Completed</h2>

            <p>
                The food has been marked as delivered.
            </p>

            <p>
                The recipient has been notified.
            </p>

        </div>

    <?php endif; ?>


    <!-- Food Information -->

    <div class="card">

        <h2>Food Details</h2>

        <p>
            <strong>Food:</strong>
            <?php
            echo htmlspecialchars(
                $delivery["food_name"]
            );
            ?>
        </p>

        <p>
            <strong>Category:</strong>
            <?php
            echo htmlspecialchars(
                $delivery["food_category"]
            );
            ?>
        </p>

        <p>
            <strong>Quantity:</strong>
            <?php
            echo htmlspecialchars(
                $delivery["requested_quantity"]
            );

            echo " ";

            echo htmlspecialchars(
                $delivery["unit"]
            );
            ?>
        </p>

    </div>


    <!-- Recipient Information -->

    <div class="card">

        <h2>Recipient Details</h2>

        <p>
            <strong>Name:</strong>
            <?php
            echo htmlspecialchars(
                $delivery["recipient_name"]
            );
            ?>
        </p>

        <p>
            <strong>Phone:</strong>
            <?php
            echo htmlspecialchars(
                $delivery["recipient_phone"]
            );
            ?>
        </p>

        <p>
            <strong>Address:</strong>
            <?php
            echo htmlspecialchars(
                $delivery["recipient_address"] ?? ""
            );
            ?>
        </p>

        <p>
            <strong>Area:</strong>
            <?php
            echo htmlspecialchars(
                $delivery["recipient_area"] ?? ""
            );
            ?>
        </p>

        <p>
            <strong>City:</strong>
            <?php
            echo htmlspecialchars(
                $delivery["recipient_city"] ?? ""
            );
            ?>
        </p>

    </div>


    <!-- Delivery Information -->

    <div class="card">

        <h2>Delivery Information</h2>

        <p>

            <strong>Delivery Method:</strong>

            <?php
            echo htmlspecialchars(
                $delivery["method"]
            );
            ?>

        </p>


        <p>

            <strong>Current Status:</strong>

            <?php
            echo htmlspecialchars(
                $delivery["delivery_status"]
            );
            ?>

        </p>

    </div>


    <!-- Confirm Delivery -->

    <?php

    $current_status =
        strtolower(
            trim(
                $delivery["delivery_status"]
            )
        );

    ?>


    <?php if ($current_status !== "delivered"): ?>

        <div class="card">

            <h2>Complete Delivery</h2>

            <p>
                After you physically give the food
                to the recipient, click the button below.
            </p>


            <form method="POST">

                <input
                    type="hidden"
                    name="action"
                    value="confirm_delivery"
                >


                <button
                    type="submit"
                    onclick="
                        return confirm(
                            'Have you delivered the food to the recipient?'
                        );
                    "
                >

                    ✅ Confirm Food Delivered

                </button>

            </form>

        </div>

    <?php else: ?>

        <div class="card">

            <h2>✅ Delivered</h2>

            <p>
                This food has already been delivered.
            </p>

            <?php if (!empty($delivery["delivered_at"])): ?>

                <p>

                    <strong>Delivered At:</strong>

                    <?php
                    echo htmlspecialchars(
                        $delivery["delivered_at"]
                    );
                    ?>

                </p>

            <?php endif; ?>

        </div>

    <?php endif; ?>


</div>


<?php include "../includes/footer.php"; ?>


</body>

</html>
