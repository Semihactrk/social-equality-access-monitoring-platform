<?php
session_start();
// Admin protection
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'], $_POST['new_role'])) {
    require_once 'includes/db_connect.php';

    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    $allowed_roles = ['vatandas', 'yetkili', 'admin'];

    // Security: Ensure only allowed roles can be assigned
    if (in_array($new_role, $allowed_roles)) {
        $sql = "UPDATE kullanicilar SET rol = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $new_role, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    $conn->close();
}

header("Location: manage_users.php");
exit();
?>