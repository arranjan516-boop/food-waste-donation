<?php
// recipient/edit-profile.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
$uid = current_user_id();

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :u");
$stmt->execute([':u' => $uid]);
$u = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name    = post('name');
    $phone   = post('phone');
    $address = post('address');
    $city    = post('city');
    $area    = post('area');
    $pincode = post('pincode');
    $lat     = post('latitude');
    $lng     = post('longitude');

    if ($name === '')  $errors[] = 'Name is required.';
    if ($city === '')  $errors[] = 'City is required.';

    $photoName = $u['profile_photo'];
    if (!empty($_FILES['profile_photo']['name'])) {
        $newPhoto = upload_image($_FILES['profile_photo'], PROFILE_UPLOAD, PROFILE_UPLOAD_URL);
        if ($newPhoto) $photoName = $newPhoto;
    }

    if (!$errors) {
        $pdo->prepare("UPDATE users SET name=:n, phone=:p, address=:a, city=:c, area=:ar,
                        pincode=:pin, latitude=:lat, longitude=:lng, profile_photo=:ph WHERE user_id=:u")
            ->execute([
                ':n' => $name, ':p' => $phone, ':a' => $address,
                ':c' => $city, ':ar' => $area, ':pin' => $pincode,
                ':lat' => $lat !== '' ? (float)$lat : null,
                ':lng' => $lng !== '' ? (float)$lng : null,
                ':ph' => $photoName, ':u' => $uid,
            ]);

        $_SESSION['name'] = $name;
        set_flash('success', 'Profile updated.');
        redirect(BASE_URL . 'recipient/profile.php');
    }
}

// ---- NOW safe to output HTML ----
$pageTitle = 'Edit Profile';
require_once __DIR__ . '/../includes/dashboard-header.php';
?>

<?php if ($errors): ?>
    <div class="toast toast-error"><?php foreach ($errors as $e): ?><?= sanitize($e) ?><br><?php endforeach; ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card" style="max-width:720px">
    <?= csrf_field() ?>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= sanitize($u['name']) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= sanitize($u['phone']) ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2"><?= sanitize($u['address']) ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">City *</label>
            <input type="text" name="city" class="form-control" required value="<?= sanitize($u['city']) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Area</label>
            <input type="text" name="area" class="form-control" value="<?= sanitize($u['area']) ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Pincode</label>
            <input type="text" name="pincode" class="form-control" value="<?= sanitize($u['pincode']) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Profile Photo</label>
            <input type="file" name="profile_photo" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>
    </div>

    <input type="hidden" name="latitude"  value="<?= sanitize($u['latitude']) ?>">
    <input type="hidden" name="longitude" value="<?= sanitize($u['longitude']) ?>">

    <div class="form-group">
        <button type="button" class="btn btn-outline btn-sm" onclick="detectLocation()">📍 Update GPS location</button>
        <span id="geo-status" class="text-muted"></span>
    </div>

    <button class="btn btn-primary">Save Changes</button>
    <a href="<?= BASE_URL ?>recipient/profile.php" class="btn btn-outline">Cancel</a>
</form>

<script>
function detectLocation() {
    if (!navigator.geolocation) return;
    const s = document.getElementById('geo-status');
    s.textContent = 'Detecting…';
    navigator.geolocation.getCurrentPosition(p => {
        document.querySelector('input[name="latitude"]').value  = p.coords.latitude.toFixed(6);
        document.querySelector('input[name="longitude"]').value = p.coords.longitude.toFixed(6);
        s.textContent = '✅ Updated';
    }, e => s.textContent = '⚠️ ' + e.message);
}
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
