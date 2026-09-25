<?php

require_once __DIR__ . "/auth.php";


function require_role($required_role)
{
    require_login();

    if (
        !isset($_SESSION["role"]) ||
        $_SESSION["role"] !== $required_role
    ) {

        header(
            "Location: " .
            BASE_URL .
            "index.php"
        );

        exit;
    }
}

?>
