<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/role-check.php";

require_role("recipient");


$stmt = $conn->prepare(

    "SELECT
        r.*,
        d.food_name,
        d.unit,
        d.city,
        d.area

     FROM food_requests r

     INNER JOIN food_donations d
        ON r.donation_id =
           d.donation_id

     WHERE r.recipient_id = ?

     ORDER BY r.requested_at DESC"

);


$stmt->bind_param(
    "i",
    $_SESSION["user_id"]
);


$stmt->execute();


$result =
    $stmt->get_result();


$page_title =
    "My Requests";

require_once "../includes/header.php";

?>


<h1>
    My Food Requests
</h1>


<div class="grid">


<?php if ($result->num_rows > 0): ?>


    <?php while (
        $request =
        $result->fetch_assoc()
    ): ?>


        <div class="card">


            <h3>

                <?= e(
                    $request["food_name"]
                ) ?>

            </h3>


            <p>

                <strong>
                    Quantity:
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
                    Status:
                </strong>

                <?= e(
                    ucfirst(
                        $request["status"]
                    )
                ) ?>

            </p>


            <br>


            <a
                href="request-details.php?id=<?= (int)$request["request_id"] ?>"
                class="btn"
            >
                View Request
            </a>


        </div>


    <?php endwhile; ?>


<?php else: ?>


    <div class="card">

        <h3>
            No Requests
        </h3>

        <p>
            You have not requested any food yet.
        </p>

    </div>


<?php endif; ?>


</div>


<?php

require_once "../includes/footer.php";

?>
