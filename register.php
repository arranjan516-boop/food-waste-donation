<?php

session_start();

require_once "config/database.php";
require_once "config/constants.php";
require_once "includes/functions.php";


$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name =
        trim($_POST["name"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $phone =
        trim($_POST["phone"] ?? "");

    $password =
        $_POST["password"] ?? "";

    $role =
        $_POST["role"] ?? "recipient";

    $city =
        trim($_POST["city"] ?? "");

    $area =
        trim($_POST["area"] ?? "");

    $pincode =
        trim($_POST["pincode"] ?? "");


    $allowed_roles = [
        "donor",
        "recipient",
        "collector",
        "ngo"
    ];


    if (
        $name === "" ||
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        ) ||
        strlen($password) < 6 ||
        !in_array(
            $role,
            $allowed_roles,
            true
        )
    ) {

        $error =
            "Please enter valid details. Password must contain at least 6 characters.";

    } else {


        /* CHECK EMAIL */

        $stmt = $conn->prepare(
            "SELECT user_id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param(
            "s",
            $email
        );

        $stmt->execute();

        $result =
            $stmt->get_result();


        if ($result->num_rows > 0) {

            $error =
                "This email is already registered.";

        } else {


            /* PASSWORD */

            $hashed_password =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            /* INSERT USER */

            $stmt = $conn->prepare(

                "INSERT INTO users
                (
                    name,
                    email,
                    password,
                    phone,
                    role,
                    city,
                    area,
                    pincode
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)"

            );


            $stmt->bind_param(

                "ssssssss",

                $name,
                $email,
                $hashed_password,
                $phone,
                $role,
                $city,
                $area,
                $pincode

            );


            if ($stmt->execute()) {

                $_SESSION["user_id"] =
                    $stmt->insert_id;

                $_SESSION["name"] =
                    $name;

                $_SESSION["role"] =
                    $role;


                redirect_by_role(
                    $role
                );

            } else {

                $error =
                    "Registration failed. Please try again.";

            }

        }

    }

}


$page_title = "Register";

require_once "includes/header.php";

?>


<div class="form">

    <h1>
        Create Account
    </h1>


    <?php if ($error): ?>

        <div class="alert">

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
    >


        <div class="group">

            <label>
                Full Name
            </label>

            <input
                type="text"
                name="name"
                required
            >

        </div>


        <div class="group">

            <label>
                Email
            </label>

            <input
                type="email"
                name="email"
                required
            >

        </div>


        <div class="group">

            <label>
                Phone
            </label>

            <input
                type="text"
                name="phone"
            >

        </div>


        <div class="group">

            <label>
                I am a
            </label>

            <select name="role" required>

                <option value="recipient">
                    Recipient
                </option>

                <option value="donor">
                    Donor
                </option>

                <option value="collector">
                    Collector
                </option>

                <option value="ngo">
                    NGO
                </option>

            </select>

        </div>


        <div class="group">

            <label>
                City
            </label>

            <input
                type="text"
                name="city"
            >

        </div>


        <div class="group">

            <label>
                Area
            </label>

            <input
                type="text"
                name="area"
            >

        </div>


        <div class="group">

            <label>
                Pincode
            </label>

            <input
                type="text"
                name="pincode"
            >

        </div>


        <div class="group">

            <label>
                Password
            </label>

            <input
                type="password"
                name="password"
                required
            >

        </div>


        <button type="submit">
            Register
        </button>

    </form>

</div>


<?php

require_once "includes/footer.php";

?>
