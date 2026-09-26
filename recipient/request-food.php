<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/role-check.php";

require_role("recipient");


$donation_id =
    (int)(
        $_GET["id"] ?? 0
    );


if ($donation_id <= 0) {

    header(
        "Location: available-food.php"
    );

    exit;
}


/*
 * GET FOOD
 */

$stmt = $conn->prepare(

    "SELECT
        donation_id,
        donor_id,
        food_name,
        quantity,
        unit,
        status

     FROM food_donations

     WHERE donation_id = ?

     LIMIT 1"

);


$stmt->bind_param(
    "i",
    $donation_id
);


$stmt->execute();


$food =
    $stmt
    ->get_result()
    ->fetch_assoc();


if (!$food) {

    die("Food donation not found.");

}


$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $quantity =
        (float)(
            $_POST["quantity"] ?? 0
        );


    $message =
        trim(
            $_POST["message"] ?? ""
        );


    if ($food["status"] !== "available") {

        $error =
            "This food is no longer available.";

    }

    elseif ($quantity <= 0) {

        $error =
            "Please enter a valid quantity.";

    }

    elseif (
        $quantity >
        (float)$food["quantity"]
    ) {

        $error =
            "Requested quantity cannot be greater than available quantity.";

    }

    else {


        /*
         * CHECK EXISTING REQUEST
         */

        $check = $conn->prepare(

            "SELECT request_id

             FROM food_requests

             WHERE donation_id = ?
             AND recipient_id = ?
             AND status IN
             ('pending', 'accepted')

             LIMIT 1"

        );


        $check->bind_param(

            "ii",

            $donation_id,

            $_SESSION["user_id"]

        );


        $check->execute();


        $existing =
            $check
            ->get_result();


        if ($existing->num_rows > 0) {

            $error =
                "You have already requested this food.";

        }

        else {


            /*
             * INSERT REQUEST
             */

            $stmt = $conn->prepare(

                "INSERT INTO food_requests
                (
                    donation_id,
                    recipient_id,
                    quantity,
                    message,
                    status
                )
                VALUES
                (?, ?, ?, ?, 'pending')"

            );


            $stmt->bind_param(

                "iids",

                $donation_id,

                $_SESSION["user_id"],

                $quantity,

                $message

            );


            if ($stmt->execute()) {

                /*
                 * NOTIFY DONOR
                 */

                create_notification(

                    $conn,

                    $food["donor_id"],

                    "New Food Request",

                    $_SESSION["name"] .
                    " requested " .
                    $food["food_name"],

                    "food_request",

                    $donation_id

                );


                header(
                    "Location: my-requests.php"
                );

                exit;

            }

            else {

                $error =
                    "Unable to create food request.";

            }

        }

    }

}


$page_title =
    "Request Food";

require_once "../includes/header.php";

?>


<div class="form">

    <h1>
        Request Food
    </h1>


    <p>

        <strong>
            Food:
        </strong>

        <?= e(
            $food["food_name"]
        ) ?>

    </p>


    <p>

        <strong>
            Available:
        </strong>

        <?= e(
            $food["quantity"]
        ) ?>

        <?= e(
            $food["unit"]
        ) ?>

    </p>


    <?php if ($error): ?>

        <div class="alert">

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <form method="POST">


        <div class="group">

            <label>
                Required Quantity
            </label>

            <input
                type="number"
                name="quantity"
                step="0.01"
                min="0.01"
                max="<?= e($food["quantity"]) ?>"
                required
            >

        </div>


        <div class="group">

            <label>
                Message
            </label>

            <textarea
                name="message"
                placeholder="Example: I need food for 3 people."
            ></textarea>

        </div>


        <button type="submit">
            Send Food Request
        </button>


    </form>

</div>


<?php

require_once "../includes/footer.php";

?>
