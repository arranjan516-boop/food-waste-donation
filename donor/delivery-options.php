<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";
require_once "../includes/notification-functions.php";

require_role("donor");

$donor_id = $_SESSION["user_id"];

$request_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($request_id <= 0) {
    die("Invalid request ID.");
}

/*
|--------------------------------------------------------------------------
| Get request details
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        fr.request_id,
        fr.donation_id,
        fr.recipient_id,
        fr.quantity AS requested_quantity,
        fr.message,
        fr.status AS request_status,

        fd.food_name,
        fd.food_category,
        fd.quantity AS available_quantity,
        fd.unit,
        fd.food_photo,
        fd.city,
        fd.area,

        u.name AS recipient_name,
        u.phone AS recipient_phone,
        u.address AS recipient_address,
        u.city AS recipient_city,
        u.area AS recipient_area

    FROM food_requests fr

    INNER JOIN food_donations fd
        ON fr.donation_id = fd.donation_id

    INNER JOIN users u
        ON fr.recipient_id = u.user_id

    WHERE fr.request_id = ?
      AND fd.donor_id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $request_id, $donor_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Food request not found.");
}

$request = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| Check request status
|--------------------------------------------------------------------------
*/

if ($request["request_status"] !== "accepted") {
    die("This food request must be accepted before selecting delivery.");
}


/*
|--------------------------------------------------------------------------
| Handle delivery option
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $delivery_option = $_POST["delivery_option"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | SELF DELIVERY
    |--------------------------------------------------------------------------
    */

    if ($delivery_option === "self") {

        /*
        | Check whether a delivery already exists
        */
        $check_sql = "
            SELECT delivery_id
            FROM deliveries
            WHERE request_id = ?
            LIMIT 1
        ";

        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $request_id);
        $check_stmt->execute();

        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {

            $existing = $check_result->fetch_assoc();

            $check_stmt->close();

            header(
                "Location: self-delivery.php?id=" .
                $existing["delivery_id"]
            );
            exit;

        }

        $check_stmt->close();


        /*
        | Find the actual enum values of deliveries.method
        |
        | This prevents another error if your database uses
        | a slightly different spelling for the enum value.
        */
        $enum_sql = "
            SELECT COLUMN_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'deliveries'
              AND COLUMN_NAME = 'method'
            LIMIT 1
        ";

        $enum_result = $conn->query($enum_sql);

        if (!$enum_result || $enum_result->num_rows === 0) {
            die("Unable to read the delivery method settings.");
        }

        $enum_row = $enum_result->fetch_assoc();

        $column_type = $enum_row["COLUMN_TYPE"];


        /*
        | Extract enum values
        */
        preg_match_all(
            "/'([^']*)'/",
            $column_type,
            $matches
        );

        $allowed_methods = $matches[1] ?? [];


        /*
        | Try to find the self-delivery value
        */
        $method = null;

        foreach ($allowed_methods as $allowed_method) {

            $value = strtolower(trim($allowed_method));

            if (
                $value === "self" ||
                $value === "self_delivery" ||
                $value === "self-delivery" ||
                $value === "donor"
            ) {
                $method = $allowed_method;
                break;
            }
        }


        /*
        | If self delivery is not present in enum,
        | stop safely instead of causing a fatal SQL error.
        */
        if ($method === null) {

            echo "<h2>Self delivery method is not configured.</h2>";

            echo "<p>Your deliveries.method column does not currently contain a self-delivery option.</p>";

            echo "<p>Allowed methods are:</p>";

            echo "<ul>";

            foreach ($allowed_methods as $allowed_method) {
                echo "<li>" . htmlspecialchars($allowed_method) . "</li>";
            }

            echo "</ul>";

            echo "<p>Please send me a screenshot of these values and I will adjust the code.</p>";

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Find a valid pending status
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

        $delivery_status = null;

        if ($status_result && $status_result->num_rows > 0) {

            $status_row = $status_result->fetch_assoc();

            preg_match_all(
                "/'([^']*)'/",
                $status_row["COLUMN_TYPE"],
                $status_matches
            );

            $allowed_statuses = $status_matches[1] ?? [];

            foreach ($allowed_statuses as $allowed_status) {

                if (
                    strtolower($allowed_status) === "pending"
                ) {
                    $delivery_status = $allowed_status;
                    break;
                }
            }

            /*
            | If pending is not available, use first enum value.
            */
            if (
                $delivery_status === null &&
                count($allowed_statuses) > 0
            ) {
                $delivery_status = $allowed_statuses[0];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Create delivery record
        |--------------------------------------------------------------------------
        */

        if ($delivery_status !== null) {

            $insert_sql = "
                INSERT INTO deliveries
                (
                    request_id,
                    donation_id,
                    method,
                    status,
                    notes
                )
                VALUES (?, ?, ?, ?, ?)
            ";

            $notes = "Donor will deliver the food directly to the recipient.";

            $insert_stmt = $conn->prepare($insert_sql);

            $insert_stmt->bind_param(
                "iisss",
                $request_id,
                $request["donation_id"],
                $method,
                $delivery_status,
                $notes
            );

        } else {

            $insert_sql = "
                INSERT INTO deliveries
                (
                    request_id,
                    donation_id,
                    method,
                    notes
                )
                VALUES (?, ?, ?, ?)
            ";

            $notes = "Donor will deliver the food directly to the recipient.";

            $insert_stmt = $conn->prepare($insert_sql);

            $insert_stmt->bind_param(
                "iiss",
                $request_id,
                $request["donation_id"],
                $method,
                $notes
            );
        }


        if (!$insert_stmt->execute()) {
            die("Unable to create delivery record.");
        }

        $delivery_id = $conn->insert_id;

        $insert_stmt->close();


        /*
        |--------------------------------------------------------------------------
        | Notify recipient
        |--------------------------------------------------------------------------
        */

        create_notification(
            $conn,
            $request["recipient_id"],
            "Self Delivery Selected",
            $request["food_name"] .
            " will be delivered directly by the donor.",
            "delivery",
            $delivery_id
        );


        /*
        |--------------------------------------------------------------------------
        | Open Self Delivery page
        |--------------------------------------------------------------------------
        */

        header(
            "Location: self-delivery.php?id=" .
            $delivery_id
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | COLLECTOR
    |--------------------------------------------------------------------------
    */

    if ($delivery_option === "collector") {

        header(
            "Location: collector-request.php?id=" .
            $request_id
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | NGO
    |--------------------------------------------------------------------------
    */

    if ($delivery_option === "ngo") {

        header(
            "Location: ngo-request.php?id=" .
            $request_id
        );

        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Choose Delivery - Food Waste Donation System</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <link rel="stylesheet"
          href="../assets/css/dashboard.css">

</head>

<body>

<?php include "../includes/navbar.php"; ?>


<div class="container">

    <h1>Choose Delivery Method</h1>

    <p>
        Your food request has been accepted.
        Choose how the food will reach the recipient.
    </p>


    <!-- Food Information -->

    <div class="card">

        <h2>
            <?php echo htmlspecialchars($request["food_name"]); ?>
        </h2>

        <p>
            <strong>Category:</strong>
            <?php echo htmlspecialchars($request["food_category"]); ?>
        </p>

        <p>
            <strong>Requested Quantity:</strong>
            <?php echo htmlspecialchars($request["requested_quantity"]); ?>
            <?php echo htmlspecialchars($request["unit"]); ?>
        </p>

        <p>
            <strong>Recipient:</strong>
            <?php echo htmlspecialchars($request["recipient_name"]); ?>
        </p>

        <p>
            <strong>Phone:</strong>
            <?php echo htmlspecialchars($request["recipient_phone"]); ?>
        </p>

        <p>
            <strong>Location:</strong>

            <?php

            $location = [];

            if (!empty($request["recipient_area"])) {
                $location[] = $request["recipient_area"];
            }

            if (!empty($request["recipient_city"])) {
                $location[] = $request["recipient_city"];
            }

            echo htmlspecialchars(
                implode(", ", $location)
            );

            ?>

        </p>

    </div>


    <!-- Delivery Options -->

    <div class="card">

        <h2>Select Delivery Method</h2>


        <!-- Self Delivery -->

        <form method="POST">

            <input
                type="hidden"
                name="delivery_option"
                value="self"
            >

            <button type="submit">

                🚗 I Can Deliver the Food Myself

            </button>

        </form>


        <br>


        <!-- Collector -->

        <form method="POST">

            <input
                type="hidden"
                name="delivery_option"
                value="collector"
            >

            <button type="submit">

                🚚 Request a Collector

            </button>

        </form>


        <br>


        <!-- NGO -->

        <form method="POST">

            <input
                type="hidden"
                name="delivery_option"
                value="ngo"
            >

            <button type="submit">

                🏢 Request an NGO

            </button>

        </form>

    </div>

</div>


<?php include "../includes/footer.php"; ?>

</body>

</html>
