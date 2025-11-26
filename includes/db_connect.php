<?php
// Veritabanı bağlantı bilgileri
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sehrin_nabzi_db";

// Bağlantıyı oluşturma
$conn = new mysqli($servername, $username, $password, $dbname);

// Karakter setini UTF-8 olarak ayarlama (Türkçe karakterler için önemli)
$conn->set_charset("utf8mb4");

// Bağlantıyı kontrol etme
if ($conn->connect_error) {
    // Bağlantı başarısız olursa, hatayı göster ve programı sonlandır
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}
?>