<?php
session_start();
// Yetkili koruması
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'yetkili') {
    header("Location: index.php");
    exit();
}

// Formun POST metodu ile ve rapor_id ile geldiğinden emin ol
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rapor_id'])) {
    
    require_once 'includes/db_connect.php';
    
    $rapor_id = $_POST['rapor_id'];
    $yetkili_id = $_SESSION['user_id']; // Raporu üstlenen yetkilinin ID'si

    // Sadece hala 'beklemede' durumundaysa güncelle (aynı anda iki kişinin üstlenmesini engeller)
    $sql = "UPDATE raporlar SET durum = 'islemde', ustlenen_yetkili_id = ? WHERE id = ? AND durum = 'beklemede'";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $yetkili_id, $rapor_id);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}

// İşlem bittikten sonra yetkili paneline geri yönlendir
header("Location: official_panel.php");
exit();
?>