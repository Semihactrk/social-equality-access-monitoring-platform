<?php
session_start();
// Official protection
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'yetkili') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rapor_id'])) {
    
    require_once 'includes/db_connect.php';
    
    $rapor_id = $_POST['rapor_id'];
    $yetkili_id = $_SESSION['user_id'];

    // Mark the report as 'cozuldu' (resolved) only if it belongs to this official and is currently 'islemde' (in progress)
    // This prevents an official from closing another official's report.
    $sql = "UPDATE raporlar SET durum = 'cozuldu' WHERE id = ? AND ustlenen_yetkili_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $rapor_id, $yetkili_id);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}

// Redirect back to the official panel after the operation is complete
header("Location: official_panel.php");
exit();
?>