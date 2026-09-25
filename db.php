<?php
// Unified Database Configuration for Hostinger
$db_host = "localhost";
$db_user = "u784083016_authuser"; // Confirm this is correct
$db_pass = "Er=>U3w6T*7"; // <--- PASTE THE NEW PASSWORD HERE
$main_db = "u784083016_auth"; // Confirm this is correct

// Primary database connection (for user authentication)
$user_conn = new mysqli($db_host, $db_user, $db_pass, $main_db);
if ($user_conn->connect_error) {
    die("User DB Connection failed: " . $user_conn->connect_error);
}

// Health Articles Database (if distinct from main_db, otherwise they can point to the same)
// If your health articles are in a *different* database, adjust these:
// For now, assuming they are in the *same* database as auth for simplicity
$health_db = new mysqli($db_host, $db_user, $db_pass, $main_db);
if ($health_db->connect_error) {
    die("Health DB Connection failed: " . $health_db->connect_error);
}

// Medicine Articles Database (if distinct from main_db, otherwise they can point to the same)
// For now, assuming they are in the *same* database as auth for simplicity
$medicine_db = new mysqli($db_host, $db_user, $db_pass, $main_db);
if ($medicine_db->connect_error) {
    die("Medicine DB Connection failed: " . $medicine_db->connect_error);
}

// Set character set to UTF-8 for proper handling of various characters
$user_conn->set_charset("utf8mb4");
$health_db->set_charset("utf8mb4");
$medicine_db->set_charset("utf8mb4");
?>