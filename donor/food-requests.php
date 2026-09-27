<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("donor");

$donor_id = $_SESSION["user_id"];

$error = "";


/*
|--------------------------------------------------------------------------
| Get Food Requests
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
        d.unit,
        d.food_photo,

        u.name AS recipient_name,
        u.phone AS recipient_phone,
        u.city AS recipient_city,
        u.area AS recipient_area

    FROM food_requests r

    INNER JOIN food_donations d
        ON r.donation_id = d.donation_id

    INNER JOIN users u
        ON r.recipient_id = u.user_id

    WHERE d.donor_id = ?

    ORDER BY r.requested_at DESC
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    $error = "Database error: " . $conn->error;

} else {

    $stmt->bind_param(
        "i",
        $donor_id
    );

    $stmt->execute();

    $requests = $stmt->get_result();
}


$page_title = "Food Requests";

require_once "../includes/header.php";

?>


<div class="container">

    <div class="page-header">

        <h1>
            Food Requests
        </h1>

        <p>
            View requests received for your donated food.
        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="alert error">

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <?php if (!isset($requests)): ?>

        <div class="alert error">

            Unable to load food requests.

        </div>

    <?php elseif ($requests->num_rows === 0): ?>

        <div class="empty-state">

            <h2>
                No Food Requests Yet
            </h2>

            <p>
                When a recipient requests your donated food,
                the request will appear here.
            </p>

        </div>

    <?php else: ?>


        <div class="food-requests">


            <?php while ($request = $requests->fetch_assoc()): ?>


                <div class="request-card">


                    <!-- Food Image -->

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


                    <!-- Request Information -->

                    <div class="request-content">


                        <h2>

                            <?= e(
                                $request["food_name"]
                            ) ?>

                        </h2>


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
                                Recipient:
                            </strong>

                            <?= e(
                                $request["recipient_name"]
                            ) ?>

                        </p>


                        <?php if (!empty($request["recipient_phone"])): ?>

                            <p>

                                <strong>
                                    Phone:
                                </strong>

                                <?= e(
                                    $request["recipient_phone"]
                                ) ?>

                            </p>

                        <?php endif; ?>


                        <?php if (
                            !empty($request["recipient_city"]) ||
                            !empty($request["recipient_area"])
                        ): ?>

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

                        <?php endif; ?>


                        <?php if (!empty($request["message"])): ?>

                            <p>

                                <strong>
                                    Message:
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


                        <!-- Status -->

                        <p>

                            <strong>
                                Status:
                            </strong>


                            <?php

                            $status = strtolower(
                                $request["status"]
                            );

                            ?>


                            <?php if ($status === "pending"): ?>

                                <span class="status pending">
                                    Pending
                                </span>


                            <?php elseif ($status === "accepted"): ?>

                                <span class="status accepted">
                                    Accepted
                                </span>


                            <?php elseif ($status === "rejected"): ?>

                                <span class="status rejected">
                                    Rejected
                                </span>


                            <?php else: ?>

                                <span class="status">
                                    <?= e(
                                        ucfirst($status)
                                    ) ?>
                                </span>

                            <?php endif; ?>

                        </p>


                        <!-- Request Details -->

                        <a
                            href="request-details.php?id=<?= (int)$request["request_id"] ?>"
                            class="btn"
                        >
                            View Request
                        </a>


                    </div>

                </div>


            <?php endwhile; ?>


        </div>


    <?php endif; ?>

</div>


<?php

require_once "../includes/footer.php";

?>
