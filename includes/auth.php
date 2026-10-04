<?php
// includes/auth.php
require_once __DIR__ . '/functions.php';

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) return null;
    return [
        'user_id' => $_SESSION['user_id'],
        'name'    => $_SESSION['name']    ?? '',
        'email'   => $_SESSION['email']   ?? '',
        'role'    => $_SESSION['role']    ?? '',
    ];
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_role() {
    return $_SESSION['role'] ?? null;
}

function login_user(array $user) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];
    session_regenerate_id(true);
}

function logout_user() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_login() {
    if (!is_logged_in()) {
        set_flash('error', 'Please login to continue.');
        redirect(BASE_URL . 'login.php');
    }
}

function require_role($role) {
    require_login();
    if (current_role() !== $role && current_role() !== 'admin') {
        http_response_code(403);
        die('Access denied.');
    }
}

/**
 * Redirect user to their role's dashboard.
 */
function dashboard_url_for_role($role) {
    return match ($role) {
        'donor'     => BASE_URL . 'donor/dashboard.php',
        'recipient' => BASE_URL . 'recipient/dashboard.php',
        'collector' => BASE_URL . 'collector/dashboard.php',
        'ngo'       => BASE_URL . 'ngo/dashboard.php',
        'admin'     => BASE_URL . 'admin/dashboard.php',
        default     => BASE_URL . 'index.php',
    };
}
