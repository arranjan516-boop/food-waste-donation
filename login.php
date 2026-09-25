<?php

session_start();

require_once "config/database.php";
require_once "config/constants.php";
require_once "includes/functions.php";


$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email =
        trim($_POST["email"] ?? "");

    $password =
        $_POST["password"] ?? "";


    $stmt = $conn->prepare(

        "SELECT
            user_id,
            name,
            password,
            role,
            status
         FROM users
         WHERE email = ?
         LIMIT 1"

    );


    $stmt->bind_param(
        "s",
        $email
    );


    $stmt->execute();


    $user =
        $stmt
        ->get_result()
        ->fetch_assoc();


    if (
        $user &&
        $user["status"] === "active" &&
        password_verify(
            $password,
            $user["password"]
        )
    ) {

        $_SESSION["user_id"] =
            $user["user_id"];

        $_SESSION["name"] =
            $user["name"];

        $_SESSION["role"] =
            $user["role"];


        redirect_by_role(
            $user["role"]
        );

    } else {

        $error =
            "Invalid email or password.";

    }

}


$page_title = "Login";

require_once "includes/header.php";

?>


<div class="form">

    <h1>
        Login
    </h1>


    <?php if ($error): ?>

        <div class="alert">

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <form method="POST">


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
                Password
            </label>

            <input
                type="password"
                name="password"
                required
            >

        </div>


        <button type="submit">
            Login
        </button>

    </form>

</div>


<?php

require_once "includes/footer.php";

?>
