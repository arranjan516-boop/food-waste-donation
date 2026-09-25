<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/constants.php";
require_once __DIR__ . "/functions.php";

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($page_title ?? SITE_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>assets/css/style.css"
    >

</head>

<body>

<header>

    <nav class="navbar">

        <div class="nav-container">

            <a
                href="<?= BASE_URL ?>index.php"
                class="logo"
            >
                Food Waste Donation
            </a>


            <div class="nav-links">

                <a href="<?= BASE_URL ?>index.php">
                    Home
                </a>

                <a href="<?= BASE_URL ?>about.php">
                    About
                </a>

                <a href="<?= BASE_URL ?>how-it-works.php">
                    How It Works
                </a>

                <a href="<?= BASE_URL ?>available-food.php">
                    Available Food
                </a>


                <?php if (!empty($_SESSION["user_id"])): ?>

                    <a href="<?= BASE_URL ?>logout.php">
                        Logout
                    </a>

                <?php else: ?>

                    <a href="<?= BASE_URL ?>login.php">
                        Login
                    </a>

                    <a href="<?= BASE_URL ?>register.php">
                        Register
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </nav>

</header>


<main class="container">
