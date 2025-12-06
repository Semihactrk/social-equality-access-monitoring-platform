<?php
$servername = "localhost";
$username = "root";
$password = ""; // XAMPP'ta şifre genelde boştur
$dbname = "social_equality"; // BURAYI KONTROL ET: Veritabanı adın neyse onu yaz!

// Bağlantı oluşturma
$conn = new mysqli($servername, $username, $password, $dbname);

// Hata kontrolü
if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

// Türkçe karakter sorunu olmaması için
$conn->set_charset("utf8mb4");
?>