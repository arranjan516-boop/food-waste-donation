<?php // includes/footer.php ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="brand brand-light">
                    <img src="<?= BASE_URL ?>assets/images/logo.png" alt="">
                    <span><?= SITE_NAME ?></span>
                </div>
                <p class="footer-tag">Save Food. Serve Hope. Build Community.</p>
            </div>

            <div>
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>about.php">About</a></li>
                    <li><a href="<?= BASE_URL ?>how-it-works.php">How It Works</a></li>
                    <li><a href="<?= BASE_URL ?>available-food.php">Available Food</a></li>
                    <li><a href="<?= BASE_URL ?>contact.php">Contact</a></li>
                </ul>
            </div>

            <div>
                <h4>Get Involved</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>register.php?role=donor">Become a Donor</a></li>
                    <li><a href="<?= BASE_URL ?>register.php?role=collector">Become a Collector</a></li>
                    <li><a href="<?= BASE_URL ?>register.php?role=ngo">Register an NGO</a></li>
                    <li><a href="<?= BASE_URL ?>register.php?role=recipient">Find Food</a></li>
                </ul>
            </div>

            <div>
                <h4>Contact</h4>
                <ul>
                    <li>📍 Tumkur, Karnataka</li>
                    <li>📧 info@foodshare.local</li>
                    <li>📞 +91 90000 00000</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            © <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
        </div>
    </div>
</footer>

<script src="<?= BASE_URL ?>assets/js/script.js"></script>
<script src="<?= BASE_URL ?>assets/js/validation.js"></script>
<script src="<?= BASE_URL ?>assets/js/notification.js"></script>
<?php if (!empty($extraJs)) foreach ((array)$extraJs as $j): ?>
    <script src="<?= BASE_URL ?>assets/js/<?= sanitize($j) ?>"></script>
<?php endforeach; ?>
</body>
</html>
