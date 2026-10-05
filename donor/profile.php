<?php
// donor/profile.php
$pageTitle = 'Profile';
require_once __DIR__ . '/../includes/dashboard-header.php';

$uid = current_user_id();
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :u");
$stmt->execute([':u' => $uid]);
$u = $stmt->fetch();
?>

<div class="card" style="max-width:720px">
    <div class="flex gap-2 mb-3" style="align-items:center">
        <div class="avatar-lg">
            <?php if ($u['profile_photo']): ?>
                <img src="<?= PROFILE_UPLOAD_URL . rawurlencode($u['profile_photo']) ?>" alt="">
            <?php else: ?>
                <?= strtoupper(substr($u['name'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div>
            <h2><?= sanitize($u['name']) ?></h2>
            <p class="text-muted"><?= sanitize(ucfirst($u['role'])) ?> · <?= sanitize($u['email']) ?></p>
        </div>
    </div>

    <p><strong>Phone:</strong> <?= sanitize($u['phone'] ?: '—') ?></p>
    <p><strong>City / Area:</strong> <?= sanitize($u['city']) ?> · <?= sanitize($u['area']) ?></p>
    <p><strong>Pincode:</strong> <?= sanitize($u['pincode']) ?></p>
    <p><strong>Address:</strong> <?= sanitize($u['address'] ?: '—') ?></p>
    <p><strong>Location:</strong>
        <?= $u['latitude'] && $u['longitude']
            ? sanitize($u['latitude'] . ', ' . $u['longitude'])
            : '<span class="text-muted">Not set (needed for 15 KM matching)</span>' ?>
    </p>
    <p><strong>Joined:</strong> <?= date('d M Y', strtotime($u['created_at'])) ?></p>

    <a href="<?= BASE_URL ?>donor/edit-profile.php" class="btn btn-primary mt-2">Edit Profile</a>
</div>

<style>
.avatar-lg { width:72px;height:72px;border-radius:50%;background:var(--green);color:#fff;
             display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;overflow:hidden;}
.avatar-lg img { width:100%;height:100%;object-fit:cover; }
</style>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
