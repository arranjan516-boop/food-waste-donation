<?php
// forgot-password.php
$pageTitle = 'Forgot Password';
require_once __DIR__ . '/includes/header.php';

$sent = false;
$resetLink = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = post('email');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Don't reveal whether account exists — but for demo we show a friendly message
            $sent = true;
        } else {
            // Create a reset token (store in session for the demo — no DB change needed)
            $token = bin2hex(random_bytes(24));
            $_SESSION['reset_tokens'][$token] = [
                'user_id' => $user['user_id'],
                'expires' => time() + 3600, // 1 hour
            ];
            $resetLink = BASE_URL . 'reset-password.php?token=' . $token;
            $sent = true;
        }
    }
}
?>

<section class="auth-section">
    <div class="auth-container auth-narrow">
        <div class="auth-form-wrap">
            <h1>Forgot Password?</h1>
            <p class="text-muted mb-3">Enter your email and we'll give you a reset link.</p>

            <?php if ($error): ?>
                <div class="toast toast-error"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <?php if ($sent && $resetLink): ?>
                <div class="toast toast-success">
                    Reset link generated successfully. For this demo, click the link below:
                </div>
                <p style="word-break:break-all">
                    <a href="<?= sanitize($resetLink) ?>"><?= sanitize($resetLink) ?></a>
                </p>
            <?php elseif ($sent): ?>
                <div class="toast toast-info">
                    If an account exists for that email, a reset link has been generated.
                </div>
            <?php else: ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required autofocus>
                    </div>
                    <button class="btn btn-primary btn-block">Send Reset Link</button>
                    <p class="text-center mt-2">
                        <a href="<?= BASE_URL ?>login.php">← Back to Login</a>
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
