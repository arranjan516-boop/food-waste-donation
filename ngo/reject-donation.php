<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

$donation_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($donation_id <= 0) {
    header("Location: available-donations.php");
    exit;
}

header("Location: available-donations.php?message=rejected");
exit;
?>
