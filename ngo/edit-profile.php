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
    SELECT *
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

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');

    if ($name === '') {

        $error = "Name is required.";

    } else {

        $stmt = $conn->prepare("
            UPDATE users
            SET
                name = ?,
                phone = ?,
                address = ?,
                city = ?,
                area = ?,
                pincode = ?
            WHERE user_id = ?
        ");

        $stmt->bind_param(
            "ssssssi",
            $name,
            $phone,
            $address,
            $city,
            $area,
            $pincode,
            $ngo_id
        );

        if ($stmt->execute()) {
            $success = "Profile updated successfully.";

            $user['name'] = $name;
            $user['phone'] = $phone;
            $user['address'] = $address;
            $user['city'] = $city;
            $user['area'] = $area;
            $user['pincode'] = $pincode;
        } else {
            $error = "Unable to update profile.";
        }

        $stmt->close();
    }
}

ngo_header("Edit Profile");
?>

<div class="ngo-content">

    <div class="ngo-page-header">

        <div>
            <h1>Edit Profile</h1>
            <p>Update your NGO account details.</p>
        </div>

        <a
            href="profile.php"
            class="ngo-btn ngo-btn-outline">
            ← Back
        </a>

    </div>

    <section class="ngo-section">

        <?php if ($error): ?>

            <div class="ngo-alert ngo-alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <?php if ($success): ?>

            <div class="ngo-alert ngo-alert-success">
                <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>

        <form method="POST" class="ngo-form">

            <div class="ngo-form-grid">

                <div>
                    <label>Name</label>
                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                        required>
                </div>

                <div>
                    <label>Email</label>
                    <input
                        type="email"
                        value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                        disabled>
                </div>

                <div>
                    <label>Phone</label>
                    <input
                        type="text"
                        name="phone"
                        value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>

                <div>
                    <label>City</label>
                    <input
                        type="text"
                        name="city"
                        value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                </div>

                <div>
                    <label>Area</label>
                    <input
                        type="text"
                        name="area"
                        value="<?= htmlspecialchars($user['area'] ?? '') ?>">
                </div>

                <div>
                    <label>Pincode</label>
                    <input
                        type="text"
                        name="pincode"
                        value="<?= htmlspecialchars($user['pincode'] ?? '') ?>">
                </div>

                <div class="ngo-form-full">
                    <label>Address</label>

                    <textarea
                        name="address"
                        rows="4"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>

            </div>

            <button
                type="submit"
                class="ngo-btn ngo-btn-primary">
                Save Changes
            </button>

        </form>

    </section>

</div>

<?php ngo_footer(); ?>
