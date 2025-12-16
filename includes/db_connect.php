<?php
// Database configuration settings
$servername = "localhost";
$username = "root";   // Default XAMPP username
$password = "";       // Default XAMPP password is empty
$dbname = "sehrin_nabzi_db"; // Common database name for the project

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Set character set to UTF-8 (crucial for Turkish characters)
$conn->set_charset("utf8mb4");

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
