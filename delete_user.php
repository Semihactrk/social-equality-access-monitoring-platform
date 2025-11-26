<?php
session_start();
// Admin koruması
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    require_once 'includes/db_connect.php';

    $user_id_to_delete = $_POST['user_id'];

    // ÖNEMLİ: Bir kullanıcıyı silmeden önce, o kullanıcıya ait raporları da silmeliyiz.
    // Aksi takdirde veritabanı (foreign key constraint) hatası alırız.
    $sql_delete_reports = "DELETE FROM raporlar WHERE kullanici_id = ?";
    if ($stmt_reports = $conn->prepare($sql_delete_reports)) {
        $stmt_reports->bind_param("i", $user_id_to_delete);
        $stmt_reports->execute();
        $stmt_reports->close();
    }

    // Şimdi kullanıcıyı silebiliriz.
    $sql_delete_user = "DELETE FROM kullanicilar WHERE id = ?";
    if ($stmt_user = $conn->prepare($sql_delete_user)) {
        $stmt_user->bind_param("i", $user_id_to_delete);
        $stmt_user->execute();
        $stmt_user->close();
    }
    
    $conn->close();
}

header("Location: manage_users.php");
exit();
?>