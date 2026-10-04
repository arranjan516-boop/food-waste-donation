<?php
// contact.php
$pageTitle = 'Contact';
require_once __DIR__ . '/includes/header.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name    = post('name');
    $email   = post('email');
    $message = post('message');

    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (:n, :e, :m)");
        $stmt->execute([':n' => $name, ':e' => $email, ':m' => $message]);
        set_flash('success', 'Thanks! We received your message and will get back to you soon.');
        $success = true;
    } else {
        set_flash('error', 'Please fill all fields correctly.');
    }
}
?>
<section class="section">
    <div class="container" style="max-width:900px">
        <h1 class="section-title">Contact Us</h1>
        <p class="section-sub">Questions, suggestions or partnership ideas? We'd love to hear from you.</p>

        <div class="contact-grid">
            <div class="contact-info">
                <div class="contact-card">
                    <div class="helper-icon">📍</div>
                    <h3>Visit Us</h3>
                    <p>Tumkur, Karnataka, India</p>
                </div>
                <div class="contact-card">
                    <div class="helper-icon">📧</div>
                    <h3>Email Us</h3>
                    <p>info@foodshare.local</p>
                </div>
                <div class="contact-card">
                    <div class="helper-icon">📞</div>
                    <h3>Call Us</h3>
                    <p>+91 90000 00000</p>
                </div>
            </div>

            <form method="post" class="card" data-validate>
                <?= csrf_field() ?>
                <h3 class="card-title">Send a Message</h3>

                <div class="form-group">
                    <label class="form-label">Your Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Your Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="5" required></textarea>
                </div>
                <button class="btn btn-primary btn-block">Send Message</button>
            </form>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
