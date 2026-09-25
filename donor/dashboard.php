<?php

require_once "../config/constants.php";
require_once "../includes/role-check.php";

require_role("donor");


$page_title =
    "Donor Dashboard";

require_once "../includes/header.php";

?>


<h1>
    Welcome, <?= e($_SESSION["name"]) ?>
</h1>

<p>
    Donor Dashboard
</p>


<div class="grid">


    <div class="card">

        <h3>
            Donate Food
        </h3>

        <p>
            Post your surplus food
            and help someone in need.
        </p>

        <br>

        <a
            href="donate-food.php"
            class="btn"
        >
            Donate Food
        </a>

    </div>


    <div class="card">

        <h3>
            My Donations
        </h3>

        <p>
            View and manage your
            donated food.
        </p>

        <br>

        <a
            href="my-donations.php"
            class="btn"
        >
            My Donations
        </a>

    </div>


    <div class="card">

        <h3>
            Food Requests
        </h3>

        <p>
            See requests received
            from recipients.
        </p>

        <br>

        <a
            href="food-requests.php"
            class="btn"
        >
            View Requests
        </a>

    </div>


</div>


<?php

require_once "../includes/footer.php";

?>
