<?php
// logout.php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    logout_user();
}

// Start a fresh session for the flash message
session_start();
set_flash('success', 'You have been logged out.');
redirect(BASE_URL . 'login.php');
