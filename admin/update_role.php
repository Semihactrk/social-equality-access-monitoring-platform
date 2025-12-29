<?php
session_start();

// 1. Güvenlik Kontrolü: Sadece adminler bu dosyayı çalıştırabilir
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// 2. Formdan veriler gelmiş mi kontrol et
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['new_role'])) {
    
    $user_id = intval($_POST['user_id']); // Güvenlik için sayıya çevir
    $new_role = $_POST['new_role']; // vatandas, yetkili veya admin

    // 3. Veritabanını Güncelle
    $sql = "UPDATE kullanicilar SET rol = ? WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $new_role, $user_id);
        
        if ($stmt->execute()) {
            // Başarılıysa geri dön (URL'ye başarı mesajı eklenebilir)
            header("Location: manage_users.php?status=success");
        } else {
            // Sorgu hatası
            echo "Error updating record: " . $conn->error;
        }
        $stmt->close();
    }
} else {
    // Veri gelmediyse geri gönder
    header("Location: manage_users.php");
}

$conn->close();
?>