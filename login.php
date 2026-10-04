<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    redirect(role_dashboard($_SESSION['role']));
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif ($email === '' || $password === '') {
        $errors[] = 'Enter your email and password.';
    } else {
        $stmt = $conn->prepare("SELECT user_id, name, password, role, status FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'The email or password is not correct.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'Your account is not active. Please contact the admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];
            redirect(role_dashboard($user['role']));
        }
    }
}

$page_title = 'Login';
$layout     = 'auth';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
    <div class="auth-form">
        <?= brand_logo() ?>
        <h1>Welcome back</h1>
        <p class="auth-sub">Log in to your account.</p>

        <?php render_flash(); ?>
        <?php if ($errors): ?>
            <div class="alert alert-error" role="alert"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                    <button type="button" class="toggle-pass" data-toggle-pass="password">Show</button>
                </div>
            </div>
            <p class="text-right"><a href="<?= BASE_URL ?>forgot-password.php">Forgot password?</a></p>
            <button class="btn btn-primary btn-block" type="submit">Log in</button>
        </form>
        <p class="auth-switch">Don't have an account? <a href="<?= BASE_URL ?>register.php">Register</a></p>
    </div>
    <div class="auth-side">
        <h2>Good food brings people together</h2>
        <p>Share what you have. Receive what you need.</p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
