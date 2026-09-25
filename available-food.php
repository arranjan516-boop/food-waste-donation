<?php

require_once "config/database.php";
require_once "config/constants.php";
require_once "includes/functions.php";


$page_title =
    "Available Food";


$sql = "

    SELECT
        d.*,
        u.name AS donor_name

    FROM food_donations d

    INNER JOIN users u
        ON d.donor_id = u.user_id

    WHERE d.status = 'available'

    ORDER BY d.created_at DESC

";


$result =
    $conn->query($sql);


require_once "includes/header.php";

?>


<h1>
    Available Food
</h1>

<p>
    Food donations currently available
    in the system.
</p>


<div class="grid">


<?php if ($result->num_rows > 0): ?>


    <?php while ($food = $result->fetch_assoc()): ?>


        <div class="card">


            <?php if (!empty($food["food_photo"])): ?>

                <img
                    src="<?= BASE_URL ?>uploads/food/<?= e($food["food_photo"]) ?>"
                    class="food-img"
                    alt="Food"
                >

            <?php endif; ?>


            <h3>

                <?= e($food["food_name"]) ?>

            </h3>


            <p>

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


            <?php if (!empty($_SESSION["user_id"])): ?>

                <?php if ($_SESSION["role"] === "recipient"): ?>

                    <br>

                    <a
                        href="recipient/food-details.php?id=<?= $food["donation_id"] ?>"
                        class="btn"
                    >
                        View Food
                    </a>

                <?php endif; ?>

            <?php endif; ?>


        </div>


    <?php endwhile; ?>


<?php else: ?>


    <div class="card">

        <h3>
            No Food Available
        </h3>

        <p>
            There are currently no available
            food donations.
        </p>

    </div>


<?php endif; ?>


</div>


<?php

require_once "includes/footer.php";

?>
