<?php
session_start();

// Access Control
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand"><i class="fas fa-globe-americas"></i> Social Equality Map</div>
       <div class="navbar-menu">
            <a href="../index.php">Home</a>
            <a href="../forum/index.php">Forum</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="../user/submit_report.php" class="btn btn-primary">Submit Report</a>
                <a href="../user/profile.php">My Profile</a>
                <a href="../auth/logout.php" style="color:var(--primary-red);">Logout</a>
            <?php else: ?>
                <a href="../auth/login.php" class="btn btn-primary">Login / Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="card" style="max-width: 700px; margin: 0 auto;">
            <h2 style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; color: var(--primary-blue);">
                <i class="fas fa-pen"></i> Start a New Discussion
            </h2>
            
            <form action="new_topic.php" method="POST">
                <div class="form-group">
                    <label for="baslik">Topic Title / Question:</label>
                    <textarea name="baslik" id="baslik" rows="5" required placeholder="What would you like to discuss about social equality, infrastructure, or local services?" style="resize: vertical;"></textarea>
                    <small style="color: #888;">Please keep discussions respectful and relevant to the community.</small>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Create Topic</button>
                    <a href="index.php" class="btn" style="background: #eee; color: #333;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>