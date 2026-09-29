<?php
session_start();

require_once "../config/database.php";
require_once "../includes/ngo-layout.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$ngo_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT
        user_id,
        name,
        email,
        phone,
        role,
        address,
        city,
        area,
        pincode,
        profile_photo,
        status,
        created_at
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $ngo_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: ../logout.php");
    exit;
}

ngo_header("NGO Profile");
?>

<div class="ngo-content">

    <div class="ngo-page-header">

        <div>
            <h1>My Profile</h1>
            <p>View your NGO account information.</p>
        </div>

        <a
            href="edit-profile.php"
            class="ngo-btn ngo-btn-primary">
            Edit Profile
        </a>

    </div>

    <section class="ngo-section">

        <div class="ngo-profile-card">

            <div class="ngo-profile-avatar">

                <?php if (!empty($user['profile_photo'])): ?>

                    <img
                        src="../uploads/profiles/<?= htmlspecialchars($user['profile_photo']) ?>"
                        alt="Profile">

                <?php else: ?>

                    <span>
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </span>

                <?php endif; ?>

            </div>

            <h2><?= htmlspecialchars($user['name']) ?></h2>

            <span class="ngo-badge">
                <?= htmlspecialchars($user['role']) ?>
            </span>

            <div class="ngo-profile-details">

                <div>
                    <strong>Email</strong>
                    <span><?= htmlspecialchars($user['email']) ?></span>
                </div>

                <div>
                    <strong>Phone</strong>
                    <span><?= htmlspecialchars($user['phone'] ?? '-') ?></span>
                </div>

                <div>
                    <strong>City</strong>
                    <span><?= htmlspecialchars($user['city'] ?? '-') ?></span>
                </div>

                <div>
                    <strong>Area</strong>
                    <span><?= htmlspecialchars($user['area'] ?? '-') ?></span>
                </div>

                <div>
                    <strong>Pincode</strong>
                    <span><?= htmlspecialchars($user['pincode'] ?? '-') ?></span>
                </div>

                <div>
                    <strong>Address</strong>
                    <span><?= htmlspecialchars($user['address'] ?? '-') ?></span>
                </div>

            </div>

        </div>

    </section>

</div>

<?php ngo_footer(); ?>
