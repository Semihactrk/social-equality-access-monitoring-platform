<?php
session_start();
require_once '../includes/db_connect.php';

// Backend Logic - DO NOT TOUCH
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$konu_id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['yorum_metni'])) {
    if (isset($_SESSION['user_id'])) {
        $yorum_metni = trim($_POST['yorum_metni']);
        $kullanici_id = $_SESSION['user_id'];

        if (!empty($yorum_metni)) {
            $sql_insert_yorum = "INSERT INTO yorumlar (konu_id, kullanici_id, yorum_metni) VALUES (?, ?, ?)";
            if ($stmt = $conn->prepare($sql_insert_yorum)) {
                $stmt->bind_param("iis", $konu_id, $kullanici_id, $yorum_metni);
                $stmt->execute();
                header("Location: topic.php?id=" . $konu_id);
                exit();
            }
        }
    }
}

// Fetch Topic
$sql_konu = "SELECT t.baslik, k.kullanici_adi FROM forum_konulari t JOIN kullanicilar k ON t.kullanici_id = k.id WHERE t.id = ?";
$konu = null;
if($stmt = $conn->prepare($sql_konu)) {
    $stmt->bind_param("i", $konu_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 1) {
        $konu = $result->fetch_assoc();
    } else {
        header("Location: index.php");
        exit();
    }
}

// Fetch Comments
$yorumlar = [];
$sql_yorumlar = "SELECT y.yorum_metni, y.olusturma_tarihi, k.kullanici_adi 
                 FROM yorumlar y JOIN kullanicilar k ON y.kullanici_id = k.id 
                 WHERE y.konu_id = ? ORDER BY y.olusturma_tarihi ASC";
if($stmt = $conn->prepare($sql_yorumlar)) {
    $stmt->bind_param("i", $konu_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $yorumlar[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic Discussion - Social Equality Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .topic-header {
            background: #fff;
            padding: 20px;
            border-bottom: 2px solid var(--primary-blue);
            margin-bottom: 30px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
        }
        .comment-block {
            background: white;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            margin-bottom: 15px;
        }
        .comment-meta {
            font-size: 0.85rem;
            color: #777;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .user-name {
            color: var(--text-main);
            font-weight: bold;
            font-size: 1rem;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">Social Equality Map</div>
        <div class="navbar-menu">
            <a href="index.php">&laquo; Back to Topics</a>
        </div>
    </nav>

    <div class="container">
        
        <div class="topic-header">
            <h1 style="margin-top:0; font-size: 1.8rem;"><?php echo htmlspecialchars($konu['baslik']); ?></h1>
            <p style="margin:0; color: #666;">
                Started by <span class="user-name"><?php echo htmlspecialchars($konu['kullanici_adi']); ?></span>
            </p>
        </div>

        <h3>Comments (<?php echo count($yorumlar); ?>)</h3>

        <div class="comments-section">
            <?php if (!empty($yorumlar)): ?>
                <?php foreach ($yorumlar as $yorum): ?>
                    <div class="comment-block">
                        <div class="comment-meta">
                            <span class="user-name"><?php echo htmlspecialchars($yorum['kullanici_adi']); ?></span> 
                            &bull; 
                            <?php echo date('d M Y, H:i', strtotime($yorum['olusturma_tarihi'])); ?>
                        </div>
                        <div class="comment-body">
                            <?php echo nl2br(htmlspecialchars($yorum['yorum_metni'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #666; font-style: italic;">No comments yet. Be the first to share your thoughts!</p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 40px; background: #f9f9f9; padding: 20px; border-radius: var(--radius);">
            <?php if (isset($_SESSION['user_id'])): ?>
                <form action="topic.php?id=<?php echo $konu_id; ?>" method="POST">
                    <h4 style="margin-top:0;">Leave a Reply</h4>
                    <div class="form-group">
                        <textarea name="yorum_metni" rows="4" required placeholder="Write your comment here..." style="background: white;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Post Comment</button>
                </form>
            <?php else: ?>
                <p>Please <a href="../auth/login.php" style="color: var(--primary-blue); font-weight: bold;">login</a> to join the discussion.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
