<?php
// register.php
$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';

// Pre-select role from ?role=
$preselect = get('role');
$validRoles = ['recipient','donor','collector','ngo'];
if (!in_array($preselect, $validRoles, true)) $preselect = 'recipient';

$errors = [];
$old    = [
    'name' => '', 'email' => '', 'phone' => '',
    'role' => $preselect,
    'address' => '', 'city' => '', 'area' => '', 'pincode' => '',
    'latitude' => '', 'longitude' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['name']    = post('name');
    $old['email']   = post('email');
    $old['phone']   = post('phone');
    $old['role']    = post('role', $preselect);
    $old['address'] = post('address');
    $old['city']    = post('city');
    $old['area']    = post('area');
    $old['pincode'] = post('pincode');
    $old['latitude']  = post('latitude');
    $old['longitude'] = post('longitude');

    $password        = post('password');
    $confirmPassword = post('confirm_password');

    // ---- Validation ----
    if ($old['name'] === '')  $errors[] = 'Name is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
    if (!in_array($old['role'], $validRoles, true)) $errors[] = 'Invalid role selected.';
    if ($old['city'] === '')    $errors[] = 'City is required for 15 KM matching.';
    if ($old['pincode'] === '') $errors[] = 'Pincode is required.';

    // ---- Duplicate email check ----
    if (!$errors) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :e");
        $stmt->execute([':e' => $old['email']]);
        if ($stmt->fetch()) $errors[] = 'This email is already registered.';
    }

    // ---- Profile photo (optional) ----
    $photoName = null;
    if (!$errors && !empty($_FILES['profile_photo']['name'])) {
        $photoName = upload_image($_FILES['profile_photo'], PROFILE_UPLOAD, PROFILE_UPLOAD_URL);
    }

    // ---- Insert ----
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Try to get lat/lng from pincode if user didn't provide
        $lat = $old['latitude']  !== '' ? (float)$old['latitude']  : null;
        $lng = $old['longitude'] !== '' ? (float)$old['longitude'] : null;

        $sql = "INSERT INTO users
                (name, email, password, phone, role, address, city, area, pincode,
                 latitude, longitude, profile_photo, status)
                VALUES
                (:name, :email, :pass, :phone, :role, :address, :city, :area, :pin,
                 :lat, :lng, :photo, 'active')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'    => $old['name'],
            ':email'   => $old['email'],
            ':pass'    => $hash,
            ':phone'   => $old['phone'],
            ':role'    => $old['role'],
            ':address' => $old['address'],
            ':city'    => $old['city'],
            ':area'    => $old['area'],
            ':pin'     => $old['pincode'],
            ':lat'     => $lat,
            ':lng'     => $lng,
            ':photo'   => $photoName,
        ]);

        $newUserId = (int)$pdo->lastInsertId();

        // Notify admins
        require_once __DIR__ . '/includes/notification-functions.php';
        notify_admins($pdo, 'New ' . ucfirst($old['role']) . ' registered',
            $old['name'] . ' (' . $old['email'] . ') joined as ' . $old['role'] . '.',
            'new_user', $newUserId);

        set_flash('success', 'Registration successful! Please log in.');
        redirect(BASE_URL . 'login.php');
    }
}
?>

<section class="auth-section">
    <div class="auth-container">
        <!-- Left: Form -->
        <div class="auth-form-wrap">
            <h1>Create Your Account</h1>
            <p class="text-muted mb-3">Join our food donation community.</p>

            <?php if ($errors): ?>
                <div class="toast toast-error">
                    <ul style="margin:0;padding-left:18px">
                        <?php foreach ($errors as $e): ?>
                            <li><?= sanitize($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" data-validate>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label">I want to register as</label>
                    <div class="role-picker">
                        <?php
                        $roles = [
                            'recipient' => ['🙋','Food Recipient',   'I need food'],
                            'donor'     => ['🍱','Donor',            'I have surplus food'],
                            'collector' => ['🚴','Collector',        'I can pick up & deliver'],
                            'ngo'       => ['🏢','NGO',              'We distribute food'],
                        ];
                        foreach ($roles as $key => [$icon, $title, $sub]):
                        ?>
                            <label class="role-option">
                                <input type="radio" name="role" value="<?= $key ?>"
                                       <?= $old['role'] === $key ? 'checked' : '' ?> required>
                                <div class="role-card">
                                    <div class="role-icon"><?= $icon ?></div>
                                    <div class="role-title"><?= $title ?></div>
                                    <div class="role-sub"><?= $sub ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

               <div class="form-row">
    <div class="form-group">
        <label class="form-label">Password *</label>
        <div class="password-wrap">
            <input type="password" name="password" id="password" class="form-control"
                   minlength="6" required>
            <button type="button" class="password-toggle" onclick="togglePassword(this)" aria-label="Show password">👁</button>
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Confirm Password *</label>
        <div class="password-wrap">
            <input type="password" name="confirm_password" id="confirm_password"
                   class="form-control" minlength="6" required>
            <button type="button" class="password-toggle" onclick="togglePassword(this)" aria-label="Show password">👁</button>
        </div>
    </div>
</div>

                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= sanitize($old['email']) ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" id="password" class="form-control"
                               minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" name="confirm_password" id="confirm_password"
                               class="form-control" minlength="6" required>
                    </div>
                </div>

                <h4 style="margin-top:20px;font-size:15px;color:var(--green-dark)">
                    📍 Location (needed for 15 KM matching)
                </h4>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= sanitize($old['address']) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">City *</label>
                        <input type="text" name="city" class="form-control" required
                               value="<?= sanitize($old['city']) ?>" placeholder="e.g. Tumkur">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Area</label>
                        <input type="text" name="area" class="form-control"
                               value="<?= sanitize($old['area']) ?>" placeholder="e.g. Kyathsandra">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Pincode *</label>
                        <input type="text" name="pincode" class="form-control" required
                               value="<?= sanitize($old['pincode']) ?>" maxlength="10">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Profile Photo (optional)</label>
                        <input type="file" name="profile_photo" class="form-control"
                               accept="image/jpeg,image/png,image/webp"
                               onchange="previewImage(this, document.getElementById('preview-photo'))">
                    </div>
                </div>

                <div class="form-group">
                    <img id="preview-photo" style="display:none;max-width:120px;border-radius:10px;margin-top:8px">
                </div>

                <input type="hidden" name="latitude"  value="<?= sanitize($old['latitude']) ?>">
                <input type="hidden" name="longitude" value="<?= sanitize($old['longitude']) ?>">

                <div class="form-group">
                    <button type="button" class="btn btn-outline btn-sm" onclick="detectLocation()">
                        📍 Use my current location (GPS)
                    </button>
                    <span id="geo-status" class="text-muted" style="margin-left:8px"></span>
                </div>

                <button class="btn btn-primary btn-block btn-lg">Register</button>

                <p class="text-center mt-2">
                    Already have an account? <a href="<?= BASE_URL ?>login.php">Login</a>
                </p>
            </form>
        </div>

        <!-- Right: Illustration -->
        <div class="auth-side">
            <img src="<?= BASE_URL ?>assets/images/about-food.jpg" alt="Together we reduce food waste">
            <h2>Together we can reduce food waste.</h2>
            <div class="auth-side-features">
                <div><span>🍱</span> Donate Food</div>
                <div><span>🤝</span> Help People</div>
                <div><span>🌱</span> Build Community</div>
            </div>
        </div>
    </div>
</section>

<script>
function detectLocation() {
    const status = document.getElementById('geo-status');
    if (!navigator.geolocation) {
        status.textContent = 'Geolocation not supported.';
        return;
    }
    status.textContent = 'Detecting…';
    navigator.geolocation.getCurrentPosition(
        pos => {
            document.querySelector('input[name="latitude"]').value  = pos.coords.latitude.toFixed(6);
            document.querySelector('input[name="longitude"]').value = pos.coords.longitude.toFixed(6);
            status.textContent = '✅ Location captured';
        },
        err => { status.textContent = '⚠️ ' + err.message; },
        { enableHighAccuracy: true, timeout: 8000 }
    );
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
