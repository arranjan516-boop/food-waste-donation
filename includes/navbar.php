<?php
// includes/navbar.php
$__current = basename($_SERVER['PHP_SELF']);
$__inDashboard = preg_match('#/(donor|recipient|collector|ngo|admin)/#', $_SERVER['PHP_SELF']);
if (!isset($__bellCount)) $__bellCount = is_logged_in() ? unread_count($pdo, current_user_id()) : 0;
?>
<nav class="site-navbar">
    <div class="container">
        <a class="brand" href="<?= BASE_URL ?>index.php">
            <img src="<?= BASE_URL ?>assets/images/logo.png" alt="FoodShare">
            <span><?= SITE_NAME ?></span>
        </a>

        <button class="nav-toggle" onclick="document.querySelector('.nav-links').classList.toggle('open')">☰</button>

        <ul class="nav-links">
            <li><a href="<?= BASE_URL ?>index.php"          class="<?= $__current==='index.php'?'active':'' ?>">Home</a></li>
            <li><a href="<?= BASE_URL ?>about.php"          class="<?= $__current==='about.php'?'active':'' ?>">About</a></li>
            <li><a href="<?= BASE_URL ?>how-it-works.php"   class="<?= $__current==='how-it-works.php'?'active':'' ?>">How It Works</a></li>
            <li><a href="<?= BASE_URL ?>available-food.php" class="<?= $__current==='available-food.php'?'active':'' ?>">Available Food</a></li>
            <li><a href="<?= BASE_URL ?>register.php?role=donor"     class="<?= $__current==='register.php'?'active':'' ?>">Become a Donor</a></li>
            <li><a href="<?= BASE_URL ?>register.php?role=collector" class="<?= $__current==='register.php'?'active':'' ?>">Become a Collector</a></li>
            <li><a href="<?= BASE_URL ?>contact.php"        class="<?= $__current==='contact.php'?'active':'' ?>">Contact</a></li>

            <?php if (is_logged_in()): ?>
                <li>
                    <a href="<?= dashboard_url_for_role(current_role()) ?>" class="bell-link" title="Dashboard">
                        <span class="bell">🔔
                            <?php if ($__bellCount > 0): ?>
                                <span class="count"><?= $__bellCount ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                </li>
                <li><a href="<?= dashboard_url_for_role(current_role()) ?>" class="btn btn-primary btn-sm">Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>logout.php" class="btn btn-outline btn-sm">Logout</a></li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>login.php"    class="btn btn-outline btn-sm">Login</a></li>
                <li><a href="<?= BASE_URL ?>register.php" class="btn btn-primary btn-sm">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
