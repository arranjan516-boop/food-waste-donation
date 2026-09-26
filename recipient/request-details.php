recipient/request-details.php`

Put:

```php
<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/role-check.php";

require_role("recipient");


$request_id =
    (int)(
        $_GET["id"] ?? 0
    );


$stmt = $conn->prepare(

    "SELECT
        r.*,
        d.food_name,
        d.description,
        d.unit,
        d.city,
        d.area,
        u.name AS donor_name,
        u.phone AS donor_phone

     FROM food_requests r

     INNER JOIN food_donations d
        ON r.donation_id =
           d.donation_id

     INNER JOIN users u
        ON d.donor_id =
           u.user_id

     WHERE r.request_id = ?
     AND r.recipient_id = ?

     LIMIT 1"

);


$stmt->bind_param(

    "ii",

    $request_id,

    $_SESSION["user_id"]

);


$stmt->execute();


$request =
    $stmt
    ->get_result()
    ->fetch_assoc();


if (!$request) {

    die("Request not found.");

}


$page_title =
    "Request Details";

require_once "../includes/header.php";

?>


<h1>
    Request Details
</h1>


<div class="card">


    <h2>

        <?= e(
            $request["food_name"]
        ) ?>

    </h2>


    <p>

        <strong>
            Description:
        </strong>

        <?= e(
            $request["description"]
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
            Message:
        </strong>

        <?= e(
            $request["message"]
        ) ?>

    </p>


    <p>

        <strong>
            Donor:
        </strong>

        <?= e(
            $request["donor_name"]
        ) ?>

    </p>


    <p>

        <strong>
            Location:
        </strong>

        <?= e(
            $request["area"]
        ) ?>,

        <?= e(
            $request["city"]
        ) ?>

    </p>


    <p>

        <strong>
            Request Status:
        </strong>

        <?= e(
            ucfirst(
                $request["status"]
            )
        ) ?>

    </p>


    <br>


    <?php if (
        $request["status"] === "accepted"
    ): ?>

        <div class="alert success">

            Your food request has been accepted.

        </div>


        <a
            href="delivery-status.php?id=<?= (int)$request["request_id"] ?>"
            class="btn"
        >
            Track Delivery
        </a>


    <?php elseif (
        $request["status"] === "pending"
    ): ?>

        <div class="alert">

            Your request is waiting for
            donor confirmation.

        </div>


    <?php elseif (
        $request["status"] === "rejected"
    ): ?>

        <div class="alert">

            Your request was rejected.

        </div>


    <?php endif; ?>


</div>


<?php

require_once "../includes/footer.php";

?>
