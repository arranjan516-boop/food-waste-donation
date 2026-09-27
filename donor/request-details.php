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
$message = "";


/*
|--------------------------------------------------------------------------
| Get Request Details
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
        r.requested_at,

        d.food_name,
        d.food_category,
        d.description,
        d.quantity AS available_quantity,
        d.unit,
        d.food_photo,
        d.delivery_preference,
        d.city AS donor_city,
        d.area AS donor_area,

        u.name AS recipient_name,
        u.phone AS recipient_phone,
        u.email AS recipient_email,
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

    die("Food request not found.");

}


/*
|--------------------------------------------------------------------------
| Accept / Reject Request
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | ACCEPT REQUEST
    |--------------------------------------------------------------------------
    */

    if ($action === "accept") {

        if ($request["status"] !== "pending") {

            $error =
                "This request has already been processed.";

        } else {

            /*
             * Accept this request
             */

            $update = $conn->prepare(
                "UPDATE food_requests
                 SET status = 'accepted'
                 WHERE request_id = ?
                 AND status = 'pending'"
            );


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

                    "Food Request Accepted",

                    "Your request for " .
                    $request["food_name"] .
                    " has been accepted by the donor.",

                    "food_request",

                    $request_id

                );


                /*
                 * Go to delivery options
                 */

                header(
                    "Location: delivery-options.php?id=" .
                    $request_id
                );

                exit;

            } else {

                $error =
                    "Unable to accept the request.";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | REJECT REQUEST
    |--------------------------------------------------------------------------
    */

    elseif ($action === "reject") {

        if ($request["status"] !== "pending") {

            $error =
                "This request has already been processed.";

        } else {

            $update = $conn->prepare(
                "UPDATE food_requests
                 SET status = 'rejected'
                 WHERE request_id = ?
                 AND status = 'pending'"
            );


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

                    "Food Request Rejected",

                    "Your request for " .
                    $request["food_name"] .
                    " was not accepted by the donor.",

                    "food_request",

                    $request_id

                );


                /*
                 * Refresh page
                 */

                header(
                    "Location: request-details.php?id=" .
                    $request_id
                );

                exit;

            } else {

                $error =
                    "Unable to reject the request.";

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$page_title = "Request Details";

require_once "../includes/header.php";

?>


<div class="container">


    <div class="page-header">

        <h1>
            Food Request Details
        </h1>

        <a
            href="food-requests.php"
            class="btn"
        >
            ← Back to Food Requests
        </a>

    </div>


    <?php if ($error !== ""): ?>

        <div class="alert error">

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <!-- Food Information -->

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
                    Category:
                </strong>

                <?= e(
                    $request["food_category"]
                ) ?>

            </p>


            <p>

                <strong>
                    Requested Quantity:
                </strong>

                <?= e(
                    $request["quantity"]
                ) ?>

                <?= e(
                    $request["unit"]
                ) ?>

            </p>


            <p>

                <strong>
                    Available Quantity:
                </strong>

                <?= e(
                    $request["available_quantity"]
                ) ?>

                <?= e(
                    $request["unit"]
                ) ?>

            </p>


            <?php if (!empty($request["description"])): ?>

                <p>

                    <strong>
                        Description:
                    </strong>

                    <?= e(
                        $request["description"]
                    ) ?>

                </p>

            <?php endif; ?>


            <hr>


            <!-- Recipient Information -->

            <h3>
                Recipient Information
            </h3>


            <p>

                <strong>
                    Name:
                </strong>

                <?= e(
                    $request["recipient_name"]
                ) ?>

            </p>


            <p>

                <strong>
                    Phone:
                </strong>

                <?= e(
                    $request["recipient_phone"]
                ) ?>

            </p>


            <p>

                <strong>
                    Email:
                </strong>

                <?= e(
                    $request["recipient_email"]
                ) ?>

            </p>


            <p>

                <strong>
                    Location:
                </strong>

                <?= e(
                    $request["recipient_area"]
                ) ?>

                <?php if (
                    !empty($request["recipient_area"]) &&
                    !empty($request["recipient_city"])
                ): ?>

                    ,

                <?php endif; ?>

                <?= e(
                    $request["recipient_city"]
                ) ?>

            </p>


            <?php if (!empty($request["message"])): ?>

                <p>

                    <strong>
                        Recipient Message:
                    </strong>

                    <?= e(
                        $request["message"]
                    ) ?>

                </p>

            <?php endif; ?>


            <p>

                <strong>
                    Requested On:
                </strong>

                <?= e(
                    $request["requested_at"]
                ) ?>

            </p>


            <p>

                <strong>
                    Status:
                </strong>


                <?php if ($request["status"] === "pending"): ?>

                    <span class="status pending">
                        Pending
                    </span>


                <?php elseif ($request["status"] === "accepted"): ?>

                    <span class="status accepted">
                        Accepted
                    </span>


                <?php elseif ($request["status"] === "rejected"): ?>

                    <span class="status rejected">
                        Rejected
                    </span>


                <?php else: ?>

                    <span class="status">
                        <?= e(
                            ucfirst($request["status"])
                        ) ?>
                    </span>

                <?php endif; ?>

            </p>


            <!-- Buttons -->

            <?php if ($request["status"] === "pending"): ?>


                <div class="action-buttons">


                    <!-- Accept -->

                    <form
                        method="POST"
                        style="display:inline;"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="accept"
                        >

                        <button
                            type="submit"
                            class="btn"
                            onclick="return confirm('Accept this food request?');"
                        >
                            Accept Request
                        </button>

                    </form>


                    <!-- Reject -->

                    <form
                        method="POST"
                        style="display:inline;"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="reject"
                        >

                        <button
                            type="submit"
                            class="btn"
                            onclick="return confirm('Reject this food request?');"
                        >
                            Reject Request
                        </button>

                    </form>


                </div>


            <?php elseif ($request["status"] === "accepted"): ?>


                <div class="alert success">

                    This request has been accepted.

                </div>


                <a
                    href="delivery-options.php?id=<?= (int)$request["request_id"] ?>"
                    class="btn"
                >
                    Continue to Delivery Options
                </a>


            <?php elseif ($request["status"] === "rejected"): ?>


                <div class="alert error">

                    This request has been rejected.

                </div>


            <?php endif; ?>


        </div>

    </div>


</div>


<?php

require_once "../includes/footer.php";

?>
