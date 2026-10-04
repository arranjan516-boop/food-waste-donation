<?php
// index.php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/location-functions.php';

// ---- Live counts for "Our Impact" ----
$stats = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM food_donations WHERE status = 'completed') AS completed,
      (SELECT COALESCE(SUM(people_served),0) FROM food_donations WHERE status = 'completed') AS meals,
      (SELECT COUNT(*) FROM users WHERE role = 'ngo' AND status = 'active') AS ngos,
      (SELECT COUNT(*) FROM users WHERE role = 'collector' AND status = 'active') AS collectors,
      (SELECT COUNT(*) FROM users WHERE role = 'donor' AND status = 'active') AS donors
")->fetch();

// ---- Latest 4 available donations ----
$latest = $pdo->query("
    SELECT d.*, u.name AS donor_name
    FROM food_donations d
    JOIN users u ON u.user_id = d.donor_id
    WHERE d.status = 'available'
      AND (d.best_before IS NULL OR d.best_before > NOW())
    ORDER BY d.created_at DESC
    LIMIT 4
")->fetchAll();
?>

<!-- ============ HERO ============ -->
<section class="hero">
    <div class="container">
        <div>
            <h1>Don't Waste Food.<br>Share It.</h1>
            <p>Connect surplus food with people who need it through donors, collectors and NGOs.</p>
            <div class="hero-cta">
                <a href="<?= BASE_URL ?>register.php?role=donor"     class="btn btn-primary btn-lg">Donate Food</a>
                <a href="<?= BASE_URL ?>available-food.php"           class="btn btn-accent btn-lg">Find Food</a>
                <a href="<?= BASE_URL ?>register.php?role=collector"  class="btn btn-outline btn-lg">Become a Collector</a>
            </div>
        </div>
        <img src="<?= BASE_URL ?>assets/images/hero-food.jpg" alt="Fresh food ready to be shared">
    </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section class="section">
    <div class="container">
        <h2 class="section-title">How It Works</h2>
        <p class="section-sub">Four simple steps from surplus to sharing.</p>

        <div class="steps-grid">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Donor Posts Food</h3>
                <p>Add details and a photo of the surplus food available.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>System Finds Nearby</h3>
                <p>We match within 15 KM radius with eligible recipients, collectors, and NGOs.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>Request &amp; Accept</h3>
                <p>Recipients request, donors accept, collectors or NGOs step in.</p>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <h3>Food Reaches People</h3>
                <p>Delivery proof + recipient confirmation completes the cycle.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============ AVAILABLE FOOD PREVIEW ============ -->
<section class="section" style="background:#fff">
    <div class="container">
        <div class="flex-between mb-3">
            <h2 class="section-title" style="text-align:left;margin:0">Available Food</h2>
            <a href="<?= BASE_URL ?>available-food.php" class="btn btn-outline btn-sm">View All</a>
        </div>

        <?php if (!$latest): ?>
            <p class="text-muted">No donations available right now. Check back soon!</p>
        <?php else: ?>
            <div class="food-grid">
                <?php foreach ($latest as $d): ?>
                    <?php
                        $photo = food_photo_url($d['food_photo']);
                        $urgencyClass = match($d['urgency']) {
                            'urgent'      => 'badge-red',
                            'very_urgent' => 'badge-red',
                            default       => 'badge-yellow',
                        };
                        $urgencyText = match($d['urgency']) {
                            'urgent'      => 'Urgent',
                            'very_urgent' => 'Very Urgent',
                            default       => 'Normal',
                        };
                    ?>
                    <div class="food-card">
                        <img src="<?= $photo ?>" alt="<?= sanitize($d['food_name']) ?>">
                        <div class="food-card-body">
                            <div class="flex-between">
                                <div class="food-card-title"><?= sanitize($d['food_name']) ?></div>
                                <span class="badge <?= $urgencyClass ?>"><?= $urgencyText ?></span>
                            </div>
                            <div class="food-card-meta">
                                <span>🍽️ <?= (int)$d['people_served'] ?> meals</span>
                                <span>🥗 <?= sanitize($d['food_type'] ?: 'Veg') ?></span>
                            </div>
                            <div class="food-card-meta">
                                <span>📍 <?= sanitize($d['area'] ?: $d['city'] ?: 'Nearby') ?></span>
                            </div>
                            <div class="food-card-actions">
                                <a href="<?= BASE_URL ?>available-food.php" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============ OUR IMPACT ============ -->
<section class="section" style="background: var(--green-light);">
    <div class="container">
        <h2 class="section-title">Our Impact</h2>
        <p class="section-sub">Small actions. Meaningful change.</p>

        <div class="impact-grid">
            <div class="impact-card">
                <div class="impact-num"><?= number_format($stats['meals']) ?></div>
                <div class="impact-label">Meals Provided</div>
            </div>
            <div class="impact-card">
                <div class="impact-num"><?= number_format($stats['completed']) ?></div>
                <div class="impact-label">Completed Donations</div>
            </div>
            <div class="impact-card">
                <div class="impact-num"><?= number_format($stats['ngos']) ?></div>
                <div class="impact-label">Active NGOs</div>
            </div>
            <div class="impact-card">
                <div class="impact-num"><?= number_format($stats['collectors']) ?></div>
                <div class="impact-label">Registered Collectors</div>
            </div>
        </div>
    </div>
</section>

<!-- ============ HELPERS ============ -->
<section class="section">
    <div class="container">
        <div class="helpers-grid">
            <div class="helper-card">
                <div class="helper-icon">🚴</div>
                <h3>How Collectors Help</h3>
                <p>Volunteers pick up food from donors and deliver it to nearby recipients on time.</p>
            </div>
            <div class="helper-card">
                <div class="helper-icon">🏢</div>
                <h3>How NGOs Help</h3>
                <p>NGOs collect, organize and distribute meals to shelters and communities in need.</p>
            </div>
            <div class="helper-card">
                <div class="helper-icon">💚</div>
                <h3>Why Food Donation Matters</h3>
                <p>1/3 of food produced globally is wasted. Every shared meal builds stronger communities.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============ CONTACT STRIP ============ -->
<section class="section" style="background:#fff;padding-bottom:80px">
    <div class="container">
        <div class="contact-strip">
            <div>
                <h2 style="color:#fff;font-size:24px;margin-bottom:6px">Contact Us</h2>
                <p style="color:rgba(255,255,255,.9)">Join our mission to end food waste.</p>
            </div>
            <form action="<?= BASE_URL ?>contact.php" method="post" class="contact-strip-form">
                <input type="email" name="email" placeholder="Your Email Address" required class="form-control">
                <button class="btn btn-accent">Subscribe</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
