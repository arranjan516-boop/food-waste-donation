<?php
// donor/donate-food.php
$pageTitle = 'Donate Food';
require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/location-functions.php';
require_once __DIR__ . '/../includes/notification-functions.php';

$uid = current_user_id();

// Pre-fill location from donor's user record
$userRow = $pdo->prepare("SELECT * FROM users WHERE user_id = :u");
$userRow->execute([':u' => $uid]);
$user = $userRow->fetch();

$errors = [];
$old = [
    'food_name' => '', 'food_category' => '', 'description' => '',
    'quantity' => '', 'unit' => 'kg', 'food_type' => 'Vegetarian',
    'people_served' => '',
    'preparation_time' => date('Y-m-d\TH:i'),
    'best_before' => date('Y-m-d\TH:i', strtotime('+6 hours')),
    'urgency' => 'normal',
    'address' => $user['address'] ?? '',
    'city' => $user['city'] ?? '',
    'area' => $user['area'] ?? '',
    'pincode' => $user['pincode'] ?? '',
    'latitude' => $user['latitude'] ?? '',
    'longitude' => $user['longitude'] ?? '',
    'pref_self' => 0, 'pref_collector' => 0, 'pref_ngo' => 0,
    'allow_partial_request' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($old as $k => $_) {
        if (isset($_POST[$k])) $old[$k] = post($k);
    }
    $old['pref_self']      = isset($_POST['pref_self'])      ? 1 : 0;
    $old['pref_collector'] = isset($_POST['pref_collector']) ? 1 : 0;
    $old['pref_ngo']       = isset($_POST['pref_ngo'])       ? 1 : 0;
    $old['allow_partial_request'] = isset($_POST['allow_partial_request']) ? 1 : 0;

    // Validation
    if ($old['food_name'] === '')  $errors[] = 'Food name is required.';
    if ($old['quantity'] === '' || (float)$old['quantity'] <= 0) $errors[] = 'Valid quantity is required.';
    if ($old['people_served'] === '' || (int)$old['people_served'] <= 0) $errors[] = 'Number of people served is required.';
    if ($old['city'] === '')       $errors[] = 'City is required for 15 KM matching.';
    if ($old['pincode'] === '')    $errors[] = 'Pincode is required.';
    if ($old['best_before'] === '')$errors[] = 'Best-before time is required.';
    if (!$old['pref_self'] && !$old['pref_collector'] && !$old['pref_ngo']) {
        $errors[] = 'Select at least one delivery option (self / collector / NGO).';
    }

    // Image upload (required per spec — but allow placeholder for demo, still recommended)
    $photoName = null;
    if (!empty($_FILES['food_photo']['name'])) {
        $photoName = upload_image($_FILES['food_photo'], FOOD_UPLOAD, FOOD_UPLOAD_URL);
        if (!$photoName) $errors[] = 'Food image upload failed.';
    } else {
        $errors[] = 'Food photo is required.';
    }

    // Build delivery_preference enum value from checkboxes
    $pref = 'any';
    if ($old['pref_collector'] && !$old['pref_self'] && !$old['pref_ngo']) $pref = 'collector';
    elseif ($old['pref_self'] && !$old['pref_collector'] && !$old['pref_ngo']) $pref = 'self_delivery';
    elseif ($old['pref_ngo'] && !$old['pref_self'] && !$old['pref_collector']) $pref = 'ngo';
    elseif ($old['pref_self'] && $old['pref_collector'] && !$old['pref_ngo']) $pref = 'self_delivery'; // fallback — 'any' covers combos
    // For 2+ options, use 'any'
    if (($old['pref_self'] + $old['pref_collector'] + $old['pref_ngo']) > 1) $pref = 'any';

    if (!$errors) {
        $sql = "INSERT INTO food_donations
            (donor_id, food_name, food_category, description, quantity, unit,
             food_type, people_served, food_photo, preparation_time, best_before,
             urgency, address, city, area, pincode, latitude, longitude,
             delivery_preference, allow_partial_request, status)
            VALUES
            (:donor_id, :food_name, :food_category, :description, :quantity, :unit,
             :food_type, :people_served, :food_photo, :preparation_time, :best_before,
             :urgency, :address, :city, :area, :pincode, :latitude, :longitude,
             :delivery_preference, :allow_partial, 'available')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':donor_id' => $uid,
            ':food_name' => $old['food_name'],
            ':food_category' => $old['food_category'],
            ':description' => $old['description'],
            ':quantity' => (float)$old['quantity'],
            ':unit' => $old['unit'],
            ':food_type' => $old['food_type'],
            ':people_served' => (int)$old['people_served'],
            ':food_photo' => $photoName,
            ':preparation_time' => date('Y-m-d H:i:s', strtotime($old['preparation_time'])),
            ':best_before' => date('Y-m-d H:i:s', strtotime($old['best_before'])),
            ':urgency' => $old['urgency'],
            ':address' => $old['address'],
            ':city' => $old['city'],
            ':area' => $old['area'],
            ':pincode' => $old['pincode'],
            ':latitude' => $old['latitude'] !== '' ? (float)$old['latitude'] : null,
            ':longitude' => $old['longitude'] !== '' ? (float)$old['longitude'] : null,
            ':delivery_preference' => $pref,
            ':allow_partial' => $old['allow_partial_request'],
        ]);

        $donationId = (int)$pdo->lastInsertId();

        // Log history
        $pdo->prepare("INSERT INTO donation_history (donation_id, old_status, new_status, changed_by)
                       VALUES (:d, NULL, 'available', :u)")
            ->execute([':d' => $donationId, ':u' => $uid]);

        // ---- Notifications to nearby users ----
        if ($old['latitude'] !== '' && $old['longitude'] !== '') {
            $lat = (float)$old['latitude'];
            $lng = (float)$old['longitude'];

            // Recipients (only if collector/self/any delivery — i.e. they can request)
            notify_nearby_role(
                $pdo, 'recipient', $lat, $lng,
                '🍱 New Food Donation',
                $old['people_served'] . ' meals available: ' . $old['food_name'],
                'new_donation', $donationId
            );

            // Collectors (only if collector option is enabled)
            if ($old['pref_collector'] || $pref === 'any') {
                notify_nearby_role(
                    $pdo, 'collector', $lat, $lng,
                    '🚴 New Pickup Available',
                    'A donor near you needs food collection: ' . $old['food_name'],
                    'new_pickup', $donationId
                );
            }

            // NGOs (only if NGO option is enabled)
            if ($old['pref_ngo'] || $pref === 'any') {
                notify_nearby_role(
                    $pdo, 'ngo', $lat, $lng,
                    '🏢 New Donation Available',
                    'New donation in your area: ' . $old['food_name'],
                    'new_donation', $donationId
                );
            }
        }

        // Notify admins
        notify_admins($pdo, '📦 New Donation Posted',
            current_user()['name'] . ' posted "' . $old['food_name'] . '".',
            'new_donation', $donationId);

        set_flash('success', 'Donation posted successfully! Nearby users have been notified.');
        redirect(BASE_URL . 'donor/my-donations.php');
    }
}
?>

<?php if ($errors): ?>
    <div class="toast toast-error">
        <ul style="margin:0;padding-left:18px">
            <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card" data-validate>
    <?= csrf_field() ?>

    <h3 class="card-title">Food Details</h3>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Food Name *</label>
            <input type="text" name="food_name" class="form-control" required value="<?= sanitize($old['food_name']) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Category</label>
            <select name="food_category" class="form-control">
                <?php foreach (['Rice','Meals','Curry','Bread','Fruits','Vegetables','Bakery','Snacks','Other'] as $c): ?>
                    <option value="<?= $c ?>" <?= $old['food_category']===$c?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2"><?= sanitize($old['description']) ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Quantity *</label>
            <input type="number" step="0.1" min="0.1" name="quantity" class="form-control" required value="<?= sanitize($old['quantity']) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Unit</label>
            <select name="unit" class="form-control">
                <?php foreach (['kg','meals','packets','litres','pieces','boxes'] as $u): ?>
                    <option value="<?= $u ?>" <?= $old['unit']===$u?'selected':'' ?>><?= $u ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Food Type</label>
            <select name="food_type" class="form-control">
                <?php foreach (['Vegetarian','Non-Vegetarian','Vegan'] as $t): ?>
                    <option value="<?= $t ?>" <?= $old['food_type']===$t?'selected':'' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Number of People Served *</label>
            <input type="number" min="1" name="people_served" class="form-control" required value="<?= sanitize($old['people_served']) ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Preparation Date &amp; Time</label>
            <input type="datetime-local" name="preparation_time" class="form-control" value="<?= sanitize($old['preparation_time']) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Best Before *</label>
            <input type="datetime-local" name="best_before" class="form-control" required value="<?= sanitize($old['best_before']) ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Urgency</label>
        <select name="urgency" class="form-control">
            <option value="normal"      <?= $old['urgency']==='normal'?'selected':'' ?>>Normal</option>
            <option value="urgent"      <?= $old['urgency']==='urgent'?'selected':'' ?>>Urgent</option>
            <option value="very_urgent" <?= $old['urgency']==='very_urgent'?'selected':'' ?>>Very Urgent</option>
        </select>
    </div>

    <h3 class="card-title" style="margin-top:24px">Pickup Location</h3>

    <div class="form-group">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2"><?= sanitize($old['address']) ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">City *</label>
            <input type="text" name="city" class="form-control" required value="<?= sanitize($old['city']) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Area</label>
            <input type="text" name="area" class="form-control" value="<?= sanitize($old['area']) ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Pincode *</label>
            <input type="text" name="pincode" class="form-control" required value="<?= sanitize($old['pincode']) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">GPS (optional)</label>
            <button type="button" class="btn btn-outline btn-sm" onclick="detectLocation()">📍 Use my location</button>
            <span id="geo-status" class="text-muted"></span>
        </div>
    </div>

    <input type="hidden" name="latitude"  value="<?= sanitize($old['latitude']) ?>">
    <input type="hidden" name="longitude" value="<?= sanitize($old['longitude']) ?>">

    <h3 class="card-title" style="margin-top:24px">Food Photo *</h3>
    <div class="form-group">
        <input type="file" name="food_photo" class="form-control" accept="image/jpeg,image/png,image/webp"
               onchange="previewImage(this, document.getElementById('food-preview'))" required>
        <img id="food-preview" style="display:none;max-width:220px;margin-top:10px;border-radius:10px">
    </div>

    <h3 class="card-title" style="margin-top:24px">Delivery Preference *</h3>
    <p class="text-muted mb-2">Choose at least one — you can pick multiple.</p>

    <div class="pref-grid">
        <label class="pref-card">
            <input type="checkbox" name="pref_self" <?= $old['pref_self']?'checked':'' ?>>
            <div>
                <strong>🚗 I Can Deliver Myself</strong>
                <span>Donor → Recipient</span>
            </div>
        </label>

        <label class="pref-card">
            <input type="checkbox" name="pref_collector" <?= $old['pref_collector']?'checked':'' ?>>
            <div>
                <strong>🚴 I Need a Collector</strong>
                <span>Donor → Collector → Recipient</span>
            </div>
        </label>

        <label class="pref-card">
            <input type="checkbox" name="pref_ngo" <?= $old['pref_ngo']?'checked':'' ?>>
            <div>
                <strong>🏢 NGO Can Handle This</strong>
                <span>Donor → NGO → Recipient</span>
            </div>
        </label>
    </div>

    <label style="margin-top:14px;display:block">
        <input type="checkbox" name="allow_partial_request" <?= $old['allow_partial_request']?'checked':'' ?>>
        Allow partial quantity requests (multiple recipients can request part of this donation)
    </label>

    <div class="mt-3">
        <button class="btn btn-primary btn-lg">Post Donation</button>
        <a href="<?= BASE_URL ?>donor/dashboard.php" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
function detectLocation() {
    const s = document.getElementById('geo-status');
    if (!navigator.geolocation) { s.textContent = 'Not supported'; return; }
    s.textContent = 'Detecting…';
    navigator.geolocation.getCurrentPosition(
        p => {
            document.querySelector('input[name="latitude"]').value  = p.coords.latitude.toFixed(6);
            document.querySelector('input[name="longitude"]').value = p.coords.longitude.toFixed(6);
            s.textContent = '✅ Location captured';
        },
        e => s.textContent = '⚠️ ' + e.message
    );
}
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
