<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/role-check.php";

require_role("recipient");


/*
|--------------------------------------------------------------------------
| GET RECIPIENT REQUESTS
|--------------------------------------------------------------------------
| We are not using the deliveries table here yet.
| This avoids depending on a column that does not exist in your database.
*/

$stmt = $conn->prepare("

    SELECT
        r.request_id,
        r.quantity,
        r.message,
        r.status AS request_status,
        r.requested_at,

        d.donation_id,
        d.food_name,
        d.unit,
        d.delivery_preference,
        d.status AS donation_status,
        d.city,
        d.area

    FROM food_requests r

    INNER JOIN food_donations d
        ON r.donation_id = d.donation_id

    WHERE r.recipient_id = ?

    ORDER BY r.requested_at DESC

");


$stmt->bind_param(
    "i",
    $_SESSION["user_id"]
);


$stmt->execute();

$result = $stmt->get_result();


$page_title = "Delivery Status";

require_once "../includes/header.php";

?>


<h1>🚚 Delivery Status</h1>

<p>
    Track the status of your food requests and deliveries.
</p>


<?php if ($result->num_rows > 0): ?>


    <div class="grid">


        <?php while ($request = $result->fetch_assoc()): ?>


            <div class="card">


                <!-- FOOD NAME -->

                <h2>

                    <?= e($request["food_name"]) ?>

                </h2>


                <!-- QUANTITY -->

                <p>

                    <strong>
                        Quantity:
                    </strong>

                    <?= e($request["quantity"]) ?>

                    <?= e($request["unit"]) ?>

                </p>


                <!-- LOCATION -->

                <p>

                    <strong>
                        Location:
                    </strong>

                    <?= e($request["area"]) ?>,

                    <?= e($request["city"]) ?>

                </p>


                <!-- REQUEST STATUS -->

                <p>

                    <strong>
                        Request Status:
                    </strong>

                    <span class="status">

                        <?= e(
                            ucfirst(
                                $request["request_status"]
                            )
                        ) ?>

                    </span>

                </p>


                <!-- DELIVERY PREFERENCE -->

                <p>

                    <strong>
                        Delivery Preference:
                    </strong>

                    <?= e(
                        $request["delivery_preference"]
                    ) ?>

                </p>


                <!-- REQUEST DATE -->

                <p>

                    <strong>
                        Requested On:
                    </strong>

                    <?= e(
                        $request["requested_at"]
                    ) ?>

                </p>


                <hr>


                <?php if (
                    $request["request_status"] === "pending"
                ): ?>


                    <div class="alert">

                        ⏳ Your request is waiting
                        for donor confirmation.

                    </div>


                <?php elseif (
                    $request["request_status"] === "accepted"
                ): ?>


                    <div class="alert success">

                        ✅ Your food request has
                        been accepted.

                    </div>


                    <?php if (
                        $request["delivery_preference"]
                        === "self_delivery"
                    ): ?>

                        <p>

                            🚗 The donor will
                            deliver the food
                            directly to you.

                        </p>


                    <?php elseif (
                        $request["delivery_preference"]
                        === "collector"
                    ): ?>

                        <p>

                            🚚 A collector will
                            collect and deliver
                            the food to you.

                        </p>


                    <?php elseif (
                        $request["delivery_preference"]
                        === "ngo"
                    ): ?>

                        <p>

                            🏢 An NGO will
                            collect and distribute
                            the food to you.

                        </p>


                    <?php else: ?>

                        <p>

                            🚚 Delivery arrangement
                            is being prepared.

                        </p>

                    <?php endif; ?>


                <?php elseif (
                    $request["request_status"] === "rejected"
                ): ?>


                    <div class="alert">

                        ❌ Your food request
                        was rejected.

                    </div>


                <?php elseif (
                    $request["request_status"] === "completed"
                ): ?>


                    <div class="alert success">

                        🎉 Food delivery completed.

                    </div>


                <?php else: ?>


                    <div class="alert">

                        Current status:

                        <?= e(
                            ucfirst(
                                $request["request_status"]
                            )
                        ) ?>

                    </div>


                <?php endif; ?>


            </div>


        <?php endwhile; ?>


    </div>


<?php else: ?>


    <div class="card">

        <h2>
            No Food Requests Yet
        </h2>

        <p>
            You have not requested any food.
        </p>

        <br>

        <a
            href="available-food.php"
            class="btn"
        >
            Find Available Food
        </a>

    </div>


<?php endif; ?>


<?php

require_once "../includes/footer.php";

?> 
