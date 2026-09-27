<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";
require_once "../includes/notification-functions.php";

require_role("donor");


/*
|--------------------------------------------------------------------------
| Get Request ID
|--------------------------------------------------------------------------
*/

$request_id = (int)($_GET["id"] ?? 0);

if ($request_id <= 0) {
    header("Location: food-requests.php");
    exit;
}


$donor_id = $_SESSION["user_id"];

$error = "";


/*
|--------------------------------------------------------------------------
| Get Accepted Request
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        r.request_id,
        r.donation_id,
        r.recipient_id,
        r.quantity,
        r.message,
        r.status,

        d.food_name,
        d.unit,
        d.food_photo,
        d.delivery_preference,

        u.name AS recipient_name,
        u.phone AS recipient_phone,
        u.city AS recipient_city,
        u.area AS recipient_area

    FROM food_requests r

    INNER JOIN food_donations d
        ON r.donation_id = d.donation_id

    INNER JOIN users u
        ON r.recipient_id = u.user_id

    WHERE r.request_id = ?
    AND d.donor_id = ?

    LIMIT 1
";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "ii",
    $request_id,
    $donor_id
);

$stmt->execute();

$result = $stmt->get_result();

$request = $result->fetch_assoc();


if (!$request) {
    die("Request not found.");
}


/*
|--------------------------------------------------------------------------
| Request must be accepted
|--------------------------------------------------------------------------
*/

if ($request["status"] !== "accepted") {

    die("This request has not been accepted yet.");

}


/*
|--------------------------------------------------------------------------
| Process Delivery Option
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $delivery_method =
        $_POST["delivery_method"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | SELF DELIVERY
    |--------------------------------------------------------------------------
    */

    if ($delivery_method === "self") {

        /*
         * Save delivery preference
         *
         * We are using the existing food_requests
         * table for this phase.
         */

        $update = $conn->prepare(
            "UPDATE food_requests
             SET delivery_method = 'self'
             WHERE request_id = ?"
        );


        if (!$update) {

            $error =
                "Database error: " .
                $conn->error;

        } else {

            $update->bind_param(
                "i",
                $request_id
            );


            if ($update->execute()) {

                /*
                 * Notify recipient
                 */

                create_notification(

                    $conn,

                    $request["recipient_id"],

                    "Self Delivery Selected",

                    "The donor will deliver your requested food directly.",

                    "delivery",

                    $request_id

                );


                header(
                    "Location: self-delivery.php?id=" .
                    $request_id
                );

                exit;

            } else {

                $error =
                    "Unable to save delivery option.";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | COLLECTOR DELIVERY
    |--------------------------------------------------------------------------
    */

    elseif ($delivery_method === "collector") {

        header(
            "Location: collector-request.php?id=" .
            $request_id
        );

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | NGO DELIVERY
    |--------------------------------------------------------------------------
    */

    elseif ($delivery_method === "ngo") {

        header(
            "Location: ngo-request.php?id=" .
            $request_id
        );

        exit;

    }


    else {

        $error =
            "Please select a delivery method.";

    }

}


$page_title = "Delivery Options";

require_once "../includes/header.php";

?>


<div class="container">


    <div class="page-header">

        <h1>
            Delivery Options
        </h1>

        <p>
            Choose how the food will be delivered to the recipient.
        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="alert error">

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <!-- Food Summary -->

    <div class="request-card">


        <div class="request-image">

            <?php if (!empty($request["food_photo"])): ?>

                <img
                    src="../uploads/food/<?= e($request["food_photo"]) ?>"
                    alt="<?= e($request["food_name"]) ?>"
                >

            <?php else: ?>

                <img
                    src="../assets/images/default-food.jpg"
                    alt="Food"
                >

            <?php endif; ?>

        </div>


        <div class="request-content">


            <h2>
                <?= e($request["food_name"]) ?>
            </h2>


            <p>

                <strong>
                    Requested Quantity:
                </strong>

                <?= e($request["quantity"]) ?>

                <?= e($request["unit"]) ?>

            </p>


            <p>

                <strong>
                    Recipient:
                </strong>

                <?= e($request["recipient_name"]) ?>

            </p>


            <p>

                <strong>
                    Phone:
                </strong>

                <?= e($request["recipient_phone"]) ?>

            </p>


            <p>

                <strong>
                    Location:
                </strong>

                <?= e($request["recipient_area"]) ?>

                <?php if (
                    !empty($request["recipient_area"]) &&
                    !empty($request["recipient_city"])
                ): ?>

                    ,

                <?php endif; ?>

                <?= e($request["recipient_city"]) ?>

            </p>


        </div>

    </div>


    <!-- Delivery Choices -->

    <div class="delivery-options">


        <h2>
            Choose Delivery Method
        </h2>


        <!-- SELF DELIVERY -->

        <form method="POST">

            <input
                type="hidden"
                name="delivery_method"
                value="self"
            >


            <div class="delivery-option">

                <h3>
                    🚗 I Will Deliver the Food Myself
                </h3>

                <p>
                    You can directly deliver the food
                    to the recipient.
                </p>

                <button
                    type="submit"
                    class="btn"
                >
                    Choose Self Delivery
                </button>

            </div>

        </form>


        <!-- COLLECTOR -->

        <form method="POST">

            <input
                type="hidden"
                name="delivery_method"
                value="collector"
            >


            <div class="delivery-option">

                <h3>
                    🚚 Need a Collector
                </h3>

                <p>
                    Request a nearby collector or
                    volunteer to collect and deliver
                    the food.
                </p>

                <button
                    type="submit"
                    class="btn"
                >
                    Find Collector
                </button>

            </div>

        </form>


        <!-- NGO -->

        <form method="POST">

            <input
                type="hidden"
                name="delivery_method"
                value="ngo"
            >


            <div class="delivery-option">

                <h3>
                    🏢 Need an NGO
                </h3>

                <p>
                    Send the donation request to a
                    nearby NGO for collection and
                    distribution.
                </p>

                <button
                    type="submit"
                    class="btn"
                >
                    Find NGO
                </button>

            </div>

        </form>


    </div>


</div>


<?php

require_once "../includes/footer.php";

?>
