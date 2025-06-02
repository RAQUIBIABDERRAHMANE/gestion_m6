<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = ""; // default is empty in XAMPP
$database = "gestion équipements";

// Create connection
$db = new mysqli($servername, $username, $password, $database);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// echo "Connected successfully!";
?>

