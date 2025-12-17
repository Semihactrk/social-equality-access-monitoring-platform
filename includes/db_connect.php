<?php
// Database connection information
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sehrin_nabzi_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Set character set to UTF-8 (important for Turkish characters)
$conn->set_charset("utf8mb4");

// Check connection
if ($conn->connect_error) {
    // If connection fails, show error and terminate program
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}
?>