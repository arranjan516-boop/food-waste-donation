recipient/delivery-status.php`

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


$sql = "

    SELECT
        r.request_id,
        r.status AS request_status,
        d.food_name,
        d.delivery_preference,
        d.status AS donation_status,
        dl.delivery_id,
        dl.delivery_method,
        dl.status AS delivery_status,
        dl.delivered_at,
        dl.confirmed_at

    FROM food_requests r

    INNER JOIN food_donations d
        ON r.donation_id =
           d.donation_id

    LEFT JOIN deliveries dl
        ON dl.request_id =
           r.request_id

    WHERE r.request_id = ?
    AND r.recipient_id = ?

    LIMIT 1

";


$stmt =
    $conn->prepare($sql);


$stmt->bind_param(

    "ii",

    $request_id,

    $_SESSION["user_id"]

);


$stmt->execute();


$delivery =
    $stmt
    ->get_result()
    ->fetch_assoc();


if (!$delivery) {

    die("Delivery information not found.");

}


$page_title =
    "Delivery Status";

require_once "../includes/header.php";

?>


<h1>
    Delivery Status
</h1>


<div class="card">


    <h2>

        <?= e(
            $delivery["food_name"]
        ) ?>

    </h2>


    <p>

        <strong>
            Request Status:
        </strong>

        <?= e(
            ucfirst(
                $delivery["request_status"]
            )
        ) ?>

    </p>


    <p>

        <strong>
            Delivery Method:
        </strong>

        <?= e(
            $delivery["delivery_method"]
            ?? "Not assigned"
        ) ?>

    </p>


    <p>

        <strong>
            Delivery Status:
        </strong>

        <?= e(
            $delivery["delivery_status"]
            ?? "Waiting for delivery assignment"
        ) ?>

    </p>


    <?php if (
        !empty(
            $delivery["delivered_at"]
        )
    ): ?>

        <p>

            <strong>
                Delivered At:
            </strong>

            <?= e(
                $delivery["delivered_at"]
            ) ?>

        </p>

    <?php endif; ?>


    <?php if (
        !empty(
            $delivery["confirmed_at"]
        )
    ): ?>

        <div class="alert success">

            Food delivery has been confirmed.

        </div>

    <?php endif; ?>


</div>


<?php

require_once "../includes/footer.php";

?>
