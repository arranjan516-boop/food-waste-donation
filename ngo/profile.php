<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

$user_id = $_SESSION["user_id"];

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
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Profile not found.");
}

$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>My Profile</h1>

    <?php if (!empty($user["profile_photo"])): ?>

        <img
            src="../uploads/profiles/<?php echo htmlspecialchars($user["profile_photo"]); ?>"
            width="150"
            height="150"
            style="object-fit:cover;border-radius:50%;"
            alt="Profile"
        >

    <?php endif; ?>

    <h2>
        <?php echo htmlspecialchars($user["name"]); ?>
    </h2>

    <p>
        <strong>Email:</strong>
        <?php echo htmlspecialchars($user["email"]); ?>
    </p>

    <p>
        <strong>Phone:</strong>
        <?php echo htmlspecialchars($user["phone"]); ?>
    </p>

    <p>
        <strong>Role:</strong>
        <?php echo htmlspecialchars($user["role"]); ?>
    </p>

    <p>
        <strong>Address:</strong>
        <?php echo htmlspecialchars($user["address"]); ?>
    </p>

    <p>
        <strong>City:</strong>
        <?php echo htmlspecialchars($user["city"]); ?>
    </p>

    <p>
        <strong>Area:</strong>
        <?php echo htmlspecialchars($user["area"]); ?>
    </p>

    <p>
        <strong>Pincode:</strong>
        <?php echo htmlspecialchars($user["pincode"]); ?>
    </p>

    <p>
        <strong>Account Status:</strong>
        <?php echo htmlspecialchars($user["status"]); ?>
    </p>

    <p>
        <strong>Joined:</strong>
        <?php echo htmlspecialchars($user["created_at"]); ?>
    </p>

    <br>

    <a href="edit-profile.php">
        Edit Profile
    </a>

</div>

</body>
</html>
