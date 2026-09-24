<?php
// Database configuration

$host = "localhost";
$username = "root";
$password = "";
$database = "food_waste_donation";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set UTF-8 character set
$conn->set_charset("utf8mb4");
?>
