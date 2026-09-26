<?php

require_once "../config/constants.php";
require_once "../includes/role-check.php";

require_role("recipient");


$page_title =
    "Recipient Dashboard";

require_once "../includes/header.php";

?>


<h1>
    Welcome, <?= e($_SESSION["name"]) ?>
</h1>

<p>
    Recipient Dashboard
</p>


<div class="grid">


    <!-- AVAILABLE FOOD -->

    <div class="card">

        <h3>
            🍱 Available Food
        </h3>

        <p>
            Find food donations available
            near you.
        </p>

        <br>

        <a
            href="available-food.php"
            class="btn"
        >
            Find Food
        </a>

    </div>


    <!-- MY REQUESTS -->

    <div class="card">

        <h3>
            📋 My Requests
        </h3>

        <p>
            View the food you have requested.
        </p>

        <br>

        <a
            href="my-requests.php"
            class="btn"
        >
            My Requests
        </a>

    </div>


    <!-- DELIVERY -->

    <div class="card">

        <h3>
            🚚 Delivery Status
        </h3>

        <p>
            Track your food delivery.
        </p>

        <br>

        <a
            href="delivery-status.php"
            class="btn"
        >
            Track Delivery
        </a>

    </div>


    <!-- PROFILE -->

    <div class="card">

        <h3>
            👤 My Profile
        </h3>

        <p>
            View and update your profile.
        </p>

        <br>

        <a
            href="profile.php"
            class="btn"
        >
            Profile
        </a>

    </div>


</div>


<?php

require_once "../includes/footer.php";

?>
