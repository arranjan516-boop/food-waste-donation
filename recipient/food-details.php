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


$stmt = $conn->prepare(

    "SELECT
        d.*,
        u.name AS donor_name,
        u.phone AS donor_phone

     FROM food_donations d

     INNER JOIN users u
        ON d.donor_id = u.user_id

     WHERE d.donation_id = ?

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


$page_title =
    "Food Details";

require_once "../includes/header.php";

?>


<h1>
    Food Details
</h1>


<div class="card">


    <?php if (
        !empty(
            $food["food_photo"]
        )
    ): ?>

        <img
            src="<?= BASE_URL ?>uploads/food/<?= e($food["food_photo"]) ?>"
            class="food-img"
            alt="Food"
        >

    <?php endif; ?>


    <h2>

        <?= e(
            $food["food_name"]
        ) ?>

    </h2>


    <p>

        <strong>
            Description:
        </strong>

        <?= e(
            $food["description"]
        ) ?>

    </p>


    <p>

        <strong>
            Quantity:
        </strong>

        <?= e(
            $food["quantity"]
        ) ?>

        <?= e(
            $food["unit"]
        ) ?>

    </p>


    <p>

        <strong>
            Food Type:
        </strong>

        <?= e(
            $food["food_type"]
        ) ?>

    </p>


    <p>

        <strong>
            Location:
        </strong>

        <?= e(
            $food["area"]
        ) ?>,

        <?= e(
            $food["city"]
        ) ?>

    </p>


    <p>

        <strong>
            Donor:
        </strong>

        <?= e(
            $food["donor_name"]
        ) ?>

    </p>


    <?php if (
        $food["status"] === "available"
    ): ?>


        <br>


        <a
            href="request-food.php?id=<?= (int)$food["donation_id"] ?>"
            class="btn"
        >
            Request This Food
        </a>


    <?php else: ?>


        <div class="alert">

            This food is no longer available.

        </div>


    <?php endif; ?>


</div>


<?php

require_once "../includes/footer.php";

?>
