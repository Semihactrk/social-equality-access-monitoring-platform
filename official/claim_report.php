<?php
session_start();
// Official protection
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'yetkili') {
    header("Location: index.php");
    exit();
}

// Ensure the form comes with POST method and report_id
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rapor_id'])) {
    
    require_once 'includes/db_connect.php';
    
    $rapor_id = $_POST['rapor_id'];
    $yetkili_id = $_SESSION['user_id']; // ID of the official taking over the report

    // Update only if it is still in 'beklemede' (pending) status (prevents two people from taking over at the same time)
    $sql = "UPDATE raporlar SET durum = 'islemde', ustlenen_yetkili_id = ? WHERE id = ? AND durum = 'beklemede'";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $yetkili_id, $rapor_id);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}

// Redirect back to the official panel after the operation is complete
header("Location: official_panel.php");
exit();
?>