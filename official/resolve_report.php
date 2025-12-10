<?php
session_start();
// Yetkili koruması
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'yetkili') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rapor_id'])) {
    
    require_once 'includes/db_connect.php';
    
    $rapor_id = $_POST['rapor_id'];
    $yetkili_id = $_SESSION['user_id'];

    // Sadece bu yetkiliye ait olan ve durumu 'islemde' olan raporu 'cozuldu' yap
    // Bu, bir yetkilinin başka bir yetkilinin raporunu kapatmasını engeller.
    $sql = "UPDATE raporlar SET durum = 'cozuldu' WHERE id = ? AND ustlenen_yetkili_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $rapor_id, $yetkili_id);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}

// İşlem bittikten sonra yetkili paneline geri yönlendir
header("Location: official_panel.php");
exit();
?>