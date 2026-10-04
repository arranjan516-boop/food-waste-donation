<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$__pageTitle = $pageTitle ?? SITE_NAME;
$__bellCount = is_logged_in() ? unread_count($pdo, current_user_id()) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($__pageTitle) ?> — <?= SITE_NAME ?></title>

    <link rel="icon" href="<?= BASE_URL ?>assets/images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/food.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/responsive.css">
    <?php if (!empty($extraCss)) foreach ((array)$extraCss as $c): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/<?= sanitize($c) ?>">
    <?php endforeach; ?>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>

<main class="site-main">
<?php render_flash(); ?>
