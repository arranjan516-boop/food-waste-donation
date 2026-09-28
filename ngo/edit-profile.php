<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

$user_id = $_SESSION["user_id"];

$message = "";
$error = "";

$stmt = $conn->prepare("
    SELECT
        name,
        phone,
        address,
        city,
        area,
        pincode
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
            $user_id
        );

        if ($stmt->execute()) {

            $_SESSION["name"] = $name;

            $message = "Profile updated successfully.";

            $user["name"] = $name;
            $user["phone"] = $phone;
            $user["address"] = $address;
            $user["city"] = $city;
            $user["area"] = $area;
            $user["pincode"] = $pincode;

        } else {

            $error = "Unable to update profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>Edit Profile</h1>

    <?php if ($message): ?>

        <p>
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php endif; ?>

    <?php if ($error): ?>

        <p>
            <?php echo htmlspecialchars($error); ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <label>Name</label>
        <br>

        <input
            type="text"
            name="name"
            value="<?php echo htmlspecialchars($user["name"]); ?>"
            required
        >

        <br><br>

        <label>Phone</label>
        <br>

        <input
            type="text"
            name="phone"
            value="<?php echo htmlspecialchars($user["phone"]); ?>"
        >

        <br><br>

        <label>Address</label>
        <br>

        <textarea
            name="address"
            rows="4"
        ><?php echo htmlspecialchars($user["address"]); ?></textarea>

        <br><br>

        <label>City</label>
        <br>

        <input
            type="text"
            name="city"
            value="<?php echo htmlspecialchars($user["city"]); ?>"
        >

        <br><br>

        <label>Area</label>
        <br>

        <input
            type="text"
            name="area"
            value="<?php echo htmlspecialchars($user["area"]); ?>"
        >

        <br><br>

        <label>Pincode</label>
        <br>

        <input
            type="text"
            name="pincode"
            value="<?php echo htmlspecialchars($user["pincode"]); ?>"
        >

        <br><br>

        <button type="submit">
            Save Changes
        </button>

    </form>

</div>

</body>
</html>
