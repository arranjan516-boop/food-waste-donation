<?php
$pageTitle = 'How It Works';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1 class="section-title">How It Works</h1>
        <p class="section-sub">From surplus to a shared meal in 4 simple steps.</p>

        <div class="steps-grid">
            <div class="step"><div class="step-num">1</div><h3>Donor Posts Food</h3><p>Add food details and a photo. Set best-before time and location.</p></div>
            <div class="step"><div class="step-num">2</div><h3>System Finds Nearby</h3><p>We instantly notify recipients, collectors and NGOs within 15 KM.</p></div>
            <div class="step"><div class="step-num">3</div><h3>Request &amp; Accept</h3><p>Recipient requests. Donor accepts. Collector or NGO is assigned — locked to one acceptor.</p></div>
            <div class="step"><div class="step-num">4</div><h3>Delivered &amp; Confirmed</h3><p>Delivery proof uploaded. Recipient confirms. Donation is completed.</p></div>
        </div>

        <h2 class="section-title" style="margin-top:60px">Delivery Options</h2>
        <p class="section-sub">Choose what works for you.</p>

        <div class="helpers-grid">
            <div class="helper-card">
                <div class="helper-icon">🚗</div>
                <h3>Direct Delivery</h3>
                <p><strong>Donor → Recipient</strong><br>The donor delivers the food themselves.</p>
            </div>
            <div class="helper-card">
                <div class="helper-icon">🚴</div>
                <h3>Collector Delivery</h3>
                <p><strong>Donor → Collector → Recipient</strong><br>A registered volunteer picks up and delivers.</p>
            </div>
            <div class="helper-card">
                <div class="helper-icon">🏢</div>
                <h3>NGO Handling</h3>
                <p><strong>Donor → NGO → Recipient</strong><br>A nearby NGO collects and distributes.</p>
            </div>
        </div>

        <h2 class="section-title" style="margin-top:60px">Delivery Lifecycle</h2>
        <div class="flow-diagram">
            <div class="flow-step">Available</div>
            <div class="flow-arrow">→</div>
            <div class="flow-step">Requested</div>
            <div class="flow-arrow">→</div>
            <div class="flow-step">Accepted</div>
            <div class="flow-arrow">→</div>
            <div class="flow-step">Assigned</div>
            <div class="flow-arrow">→</div>
            <div class="flow-step">Picked Up</div>
            <div class="flow-arrow">→</div>
            <div class="flow-step">Out for Delivery</div>
            <div class="flow-arrow">→</div>
            <div class="flow-step">Delivered</div>
            <div class="flow-arrow">→</div>
            <div class="flow-step">Confirmed</div>
            <div class="flow-arrow">→</div>
            <div class="flow-step flow-step-done">Completed</div>
        </div>

        <div class="text-center mt-3">
            <a href="<?= BASE_URL ?>register.php" class="btn btn-primary btn-lg">Get Started</a>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
