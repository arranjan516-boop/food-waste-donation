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

    $confirm_password =
        $_POST["confirm_password"] ?? "";

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


    /*
     * BASIC VALIDATION
     */

    if (
        $name === "" ||
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid name and email.";

    }

    /*
     * PASSWORD LENGTH
     */

    elseif (strlen($password) < 6) {

        $error =
            "Password must contain at least 6 characters.";

    }

    /*
     * CONFIRM PASSWORD
     */

    elseif ($password !== $confirm_password) {

        $error =
            "Password and Confirm Password do not match.";

    }

    /*
     * ROLE
     */

    elseif (
        !in_array(
            $role,
            $allowed_roles,
            true
        )
    ) {

        $error =
            "Invalid account type.";

    }

    else {

        /*
         * CHECK EMAIL
         */

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

        }

        else {

            /*
             * HASH PASSWORD
             */

            $hashed_password =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            /*
             * INSERT USER
             */

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

            }

            else {

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
        id="registerForm"
    >


        <!-- NAME -->

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


        <!-- EMAIL -->

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


        <!-- PHONE -->

        <div class="group">

            <label>
                Phone
            </label>

            <input
                type="text"
                name="phone"
            >

        </div>


        <!-- ROLE -->

        <div class="group">

            <label>
                I am a
            </label>

            <select
                name="role"
                required
            >

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


        <!-- CITY -->

        <div class="group">

            <label>
                City
            </label>

            <input
                type="text"
                name="city"
                placeholder="Example: Tumkur"
            >

        </div>


        <!-- AREA -->

        <div class="group">

            <label>
                Area
            </label>

            <input
                type="text"
                name="area"
                placeholder="Example: Tumkur Town"
            >

        </div>


        <!-- PINCODE -->

        <div class="group">

            <label>
                Pincode
            </label>

            <input
                type="text"
                name="pincode"
            >

        </div>


        <!-- PASSWORD -->

        <div class="group">

            <label>
                Password
            </label>

            <div class="password-box">

                <input
                    type="password"
                    name="password"
                    id="registerPassword"
                    required
                    minlength="6"
                >

                <button
                    type="button"
                    class="password-toggle"
                    onclick="togglePassword(
                        'registerPassword',
                        this
                    )"
                >
                    👁
                </button>

            </div>

        </div>


        <!-- CONFIRM PASSWORD -->

        <div class="group">

            <label>
                Confirm Password
            </label>

            <div class="password-box">

                <input
                    type="password"
                    name="confirm_password"
                    id="confirmPassword"
                    required
                    minlength="6"
                >

                <button
                    type="button"
                    class="password-toggle"
                    onclick="togglePassword(
                        'confirmPassword',
                        this
                    )"
                >
                    👁
                </button>

            </div>

        </div>


        <button type="submit">
            Register
        </button>


    </form>

</div>


<script>

function togglePassword(
    inputId,
    button
) {

    const input =
        document.getElementById(inputId);

    if (input.type === "password") {

        input.type = "text";

        button.textContent = "🙈";

    }

    else {

        input.type = "password";

        button.textContent = "👁";

    }

}

</script>


<?php

require_once "includes/footer.php";

?>
