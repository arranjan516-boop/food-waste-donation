<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("collector");

$delivery_id = (int)($_GET["id"] ?? 0);

if ($delivery_id <= 0) {
    die("Invalid task.");
}

header("Location: available-tasks.php");

exit;
