<?php
// includes/role-check.php
require_once __DIR__ . '/auth.php';

// Caller must define $REQUIRED_ROLE before including this file.
if (!isset($REQUIRED_ROLE)) {
    die('role-check.php: $REQUIRED_ROLE not set.');
}
require_role($REQUIRED_ROLE);
$currentUser = current_user();
