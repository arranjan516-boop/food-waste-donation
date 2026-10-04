<?php
$pageTitle = 'About';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1 class="section-title">About <?= SITE_NAME ?></h1>
        <p class="section-sub">Connecting surplus food with people who need it.</p>

        <div class="about-grid">
            <img src="<?= BASE_URL ?>assets/images/about-food.jpg" alt="Food donation drive">
            <div>
                <h2>Our Mission</h2>
                <p>Every day, restaurants, hostels, event organizers and households throw away perfectly edible food while thousands go hungry. <?= SITE_NAME ?> bridges that gap — a simple, organized digital platform where surplus food finds its way to people who need it most.</p>

                <h2 style="margin-top:24px">What We Do</h2>
                <ul class="check-list">
                    <li>Let donors post surplus food in seconds with photo, quantity and best-before time.</li>
                    <li>Match donations with recipients, volunteers and NGOs within a 15 KM radius.</li>
                    <li>Coordinate pickup, delivery and distribution in real time.</li>
                    <li>Verify every delivery with photo proof and recipient confirmation.</li>
                </ul>

                <h2 style="margin-top:24px">Our Values</h2>
                <p><strong>Dignity.</strong> Food is shared respectfully with people who need it.<br>
                   <strong>Speed.</strong> Fresher food reaches faster, within 15 KM.<br>
                   <strong>Trust.</strong> Every delivery is verified with proof and confirmation.</p>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
