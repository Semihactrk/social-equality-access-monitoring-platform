<?php
session_start();
include 'includes/db_connect.php';

// Redirect if logged in
if (isset($_SESSION['user_id'])) {
    header("Location: user/resources.php");
    exit();
}

// Fetch stats - Hata almamak için tablo yoksa 0 göster
$total_distributed = 0;
if($conn) {
    $sql = "SELECT COUNT(*) as total FROM reservations";
    $result = $conn->query($sql);
    if($result) {
        $row = $result->fetch_assoc();
        $total_distributed = $row['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equality Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="hero-center">
        <h1 style="color: var(--primary); font-size: 3rem; margin-bottom: 10px;">Equality Platform</h1>
        <h2 style="color: var(--accent); font-weight: 300;">Bridging the Gap for Social Equality</h2>
        
        <div style="margin: 40px 0; padding: 20px; background: #f1f1f1; border-radius: 10px;">
            <h3 style="margin: 0;">Resources Distributed So Far</h3>
            <p style="font-size: 2.5rem; font-weight: bold; color: var(--primary); margin: 10px 0;">
                <?php echo $total_distributed; ?>
            </p>
        </div>

        <div>
            <a href="auth/login.php" class="btn btn-primary">Login</a>
            <a href="auth/register.php" class="btn btn-accent">Register</a>
        </div>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>
