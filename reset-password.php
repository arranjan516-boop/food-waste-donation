<?php
// reset-password.php
$pageTitle = 'Reset Password';
require_once __DIR__ . '/includes/header.php';

$token = get('token');
$error = '';
$done  = false;
$userId = null;

// Validate token
if ($token && isset($_SESSION['reset_tokens'][$token])) {
    $info = $_SESSION['reset_tokens'][$token];
    if ($info['expires'] < time()) {
        $error = 'This reset link has expired. Please request a new one.';
    } else {
        $userId = $info['user_id'];
    }
} else {
    $error = 'Invalid or missing reset link.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId) {
    verify_csrf();
    $p1 = post('password');
    $p2 = post('confirm_password');

    if (strlen($p1) < 6)        $error = 'Password must be at least 6 characters.';
    elseif ($p1 !== $p2)        $error = 'Passwords do not match.';
    else {
        $hash = password_hash($p1, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :p WHERE user_id = :u");
        $stmt->execute([':p' => $hash, ':u' => $userId]);

        unset($_SESSION['reset_tokens'][$token]);
        $done = true;
    }
}
?>

<section class="auth-section">
    <div class="auth-container auth-narrow">
        <div class="auth-form-wrap">
            <h1>Reset Your Password</h1>

            <?php if ($done): ?>
                <div class="toast toast-success">
                    Your password was reset successfully. You can now log in.
                </div>
                <a href="<?= BASE_URL ?>login.php" class="btn btn-primary btn-block">Go to Login</a>

            <?php elseif ($error): ?>
                <div class="toast toast-error"><?= sanitize($error) ?></div>
                <a href="<?= BASE_URL ?>forgot-password.php" class="btn btn-outline btn-block mt-2">Request New Link</a>

            <?php else: ?>
                <form method="post" data-validate>
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" id="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="6" required>
                    </div>
                    <button class="btn btn-primary btn-block">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
