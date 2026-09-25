<?php

$page_title = "Home";

require_once "includes/header.php";

?>


<section class="hero">

    <h1>
        Reduce Food Waste. Share Food. Help People.
    </h1>

    <p>
        A platform that connects food donors,
        recipients, collectors and NGOs.
    </p>


    <a
        href="register.php"
        class="btn"
    >
        Get Started
    </a>


    <a
        href="available-food.php"
        class="btn"
    >
        Available Food
    </a>

</section>


<div class="grid">

    <div class="card">

        <h3>
            🍱 Donor
        </h3>

        <p>
            Restaurants, hotels, events,
            households and others can donate
            surplus edible food.
        </p>

    </div>


    <div class="card">

        <h3>
            👤 Recipient
        </h3>

        <p>
            Recipients can find available food
            and request food.
        </p>

    </div>


    <div class="card">

        <h3>
            🚚 Collector
        </h3>

        <p>
            Collectors can accept pickup and
            delivery tasks.
        </p>

    </div>


    <div class="card">

        <h3>
            🤝 NGO
        </h3>

        <p>
            NGOs can collect donated food
            and distribute it.
        </p>

    </div>

</div>


<?php

require_once "includes/footer.php";

?>
