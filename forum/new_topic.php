<?php
session_start();
// Backend Logic - DO NOT TOUCH
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once '../includes/db_connect.php';

    $baslik = trim($_POST['baslik']);
    $kullanici_id = $_SESSION['user_id'];

    if (!empty($baslik)) {
        $sql = "INSERT INTO forum_konulari (kullanici_id, baslik) VALUES (?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("is", $kullanici_id, $baslik);
            if ($stmt->execute()) {
                header("Location: index.php");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start New Topic - Forum</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">Social Equality Map</div>
        <div class="navbar-menu">
            <a href="index.php">Back to Forum</a>
        </div>
    </nav>

    <div class="container">
        <div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: var(--radius); border: 1px solid var(--border-color);">
            <h2 style="margin-bottom: 20px;">Start a New Discussion</h2>
            
            <form action="new_topic.php" method="POST">
                <div class="form-group">
                    <label for="baslik">Topic Title / Question:</label>
                    <textarea name="baslik" id="baslik" rows="4" required placeholder="What would you like to discuss about social equality?"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Create Topic</button>
            </form>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="index.php" style="color: #666;">Cancel</a>
            </div>
        </div>
    </div>
</body>
</html>
