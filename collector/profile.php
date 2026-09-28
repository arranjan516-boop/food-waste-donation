<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("collector");

$user_id = $_SESSION["user_id"];

$sql = "
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
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    die("Profile not found.");
}

$user = $result->fetch_assoc();

$stmt->close();

$page_title = "My Profile";

require_once "../includes/header.php";
?>

<div class="container">

    <h1>My Profile</h1>

    <div class="profile-card">

        <?php if (!empty($user["profile_photo"])): ?>

            <img
                src="../uploads/profiles/<?= htmlspecialchars($user["profile_photo"]) ?>"
                class="profile-image"
                alt="Profile"
            >

        <?php endif; ?>


        <h2>
            <?= htmlspecialchars($user["name"]) ?>
        </h2>


        <p>
            <strong>Email:</strong>
            <?= htmlspecialchars($user["email"]) ?>
        </p>

        <p>
            <strong>Phone:</strong>
            <?= htmlspecialchars($user["phone"]) ?>
        </p>

        <p>
            <strong>Role:</strong>
            <?= htmlspecialchars($user["role"]) ?>
        </p>

        <p>
            <strong>Address:</strong>
            <?= htmlspecialchars($user["address"]) ?>
        </p>

        <p>
            <strong>City:</strong>
            <?= htmlspecialchars($user["city"]) ?>
        </p>

        <p>
            <strong>Area:</strong>
            <?= htmlspecialchars($user["area"]) ?>
        </p>

        <p>
            <strong>Pincode:</strong>
            <?= htmlspecialchars($user["pincode"]) ?>
        </p>

        <p>
            <strong>Status:</strong>
            <?= htmlspecialchars($user["status"]) ?>
        </p>


        <a href="edit-profile.php" class="btn">
            Edit Profile
        </a>

    </div>

</div>


<style>

.container {
    max-width: 800px;
    margin: 30px auto;
    padding: 20px;
}

.profile-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 3px 12px rgba(0,0,0,.08);
}

.profile-image {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
}

.btn {
    display: inline-block;
    margin-top: 15px;
    padding: 11px 20px;
    background: #333;
    color: white;
    text-decoration: none;
    border-radius: 8px;
}

</style>

<?php require_once "../includes/footer.php"; ?>
