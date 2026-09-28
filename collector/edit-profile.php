<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("collector");

$user_id = $_SESSION["user_id"];

$message = "";
$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $area = trim($_POST["area"] ?? "");
    $pincode = trim($_POST["pincode"] ?? "");


    if ($name === "") {

        $error = "Name is required.";

    } else {

        $sql = "
            UPDATE users

            SET
                name = ?,
                phone = ?,
                address = ?,
                city = ?,
                area = ?,
                pincode = ?

            WHERE user_id = ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $stmt->bind_param(
                "ssssssi",
                $name,
                $phone,
                $address,
                $city,
                $area,
                $pincode,
                $user_id
            );

            if ($stmt->execute()) {

                $_SESSION["name"] = $name;

                $message = "Profile updated successfully.";

            } else {

                $error = "Failed to update profile: " . $stmt->error;

            }

            $stmt->close();
        }
    }
}


/*
 * Load current profile.
 */

$sql = "
    SELECT
        name,
        email,
        phone,
        address,
        city,
        area,
        pincode

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

$user = $result->fetch_assoc();

$stmt->close();

$page_title = "Edit Profile";

require_once "../includes/header.php";
?>

<div class="container">

    <h1>Edit Profile</h1>


    <?php if ($message !== ""): ?>

        <div class="success">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <div class="form-card">

        <form method="POST">

            <label>Name</label>

            <input
                type="text"
                name="name"
                value="<?= htmlspecialchars($user["name"] ?? "") ?>"
                required
            >


            <label>Email</label>

            <input
                type="email"
                value="<?= htmlspecialchars($user["email"] ?? "") ?>"
                disabled
            >


            <label>Phone</label>

            <input
                type="text"
                name="phone"
                value="<?= htmlspecialchars($user["phone"] ?? "") ?>"
            >


            <label>Address</label>

            <textarea name="address" rows="3"><?= htmlspecialchars($user["address"] ?? "") ?></textarea>


            <label>City</label>

            <input
                type="text"
                name="city"
                value="<?= htmlspecialchars($user["city"] ?? "") ?>"
            >


            <label>Area</label>

            <input
                type="text"
                name="area"
                value="<?= htmlspecialchars($user["area"] ?? "") ?>"
            >


            <label>Pincode</label>

            <input
                type="text"
                name="pincode"
                value="<?= htmlspecialchars($user["pincode"] ?? "") ?>"
            >


            <button type="submit">
                Save Changes
            </button>

        </form>

    </div>

</div>


<style>

.container {
    max-width: 700px;
    margin: 30px auto;
    padding: 20px;
}

.form-card {
    padding: 30px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 3px 12px rgba(0,0,0,.08);
}

label {
    display: block;
    margin-top: 15px;
    margin-bottom: 6px;
    font-weight: bold;
}

input,
textarea {
    width: 100%;
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 8px;
}

button {
    margin-top: 20px;
    padding: 12px 22px;
    background: #333;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.success,
.error {
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 8px;
}

.success {
    background: #e8f5e9;
}

.error {
    background: #ffebee;
}

</style>

<?php require_once "../includes/footer.php"; ?>
