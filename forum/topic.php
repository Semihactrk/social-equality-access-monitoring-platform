<?php
session_start();
require_once '../includes/db_connect.php';

// Check ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$konu_id = $_GET['id'];

// Handle New Comment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['yorum_metni'])) {
    if (isset($_SESSION['user_id'])) {
        $yorum_metni = trim($_POST['yorum_metni']);
        $kullanici_id = $_SESSION['user_id'];

        if (!empty($yorum_metni)) {
            // FIX: Using correct column 'yorum_metni' and 'konu_id'
            $sql_insert = "INSERT INTO yorumlar (konu_id, kullanici_id, yorum_metni) VALUES (?, ?, ?)";
            if ($stmt = $conn->prepare($sql_insert)) {
                $stmt->bind_param("iis", $konu_id, $kullanici_id, $yorum_metni);
                $stmt->execute();
                header("Location: topic.php?id=" . $konu_id);
                exit();
            }
        }
    } else {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Fetch Topic Details
$sql_konu = "SELECT t.baslik, k.kullanici_adi, t.olusturma_tarihi 
             FROM forum_konulari t 
             JOIN kullanicilar k ON t.kullanici_id = k.id 
             WHERE t.id = ?";
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

// Fetch Comments for this Topic
$yorumlar = [];
$sql_yorumlar = "SELECT y.id, y.yorum, y.olusturma_tarihi, k.kullanici_adi 
                 FROM yorumlar y 
                 JOIN kullanicilar k ON y.kullanici_id = k.id 
                 WHERE y.rapor_id = ? 
                 ORDER BY y.olusturma_tarihi ASC"; 

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
    <title>Discussion - Social Equality Platform</title>
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
        
        <div class="card" style="border-left: 5px solid var(--primary-blue);">
            <h1 style="margin-top:0; font-size: 1.8rem; color: var(--primary-blue);"><?php echo htmlspecialchars($konu['baslik']); ?></h1>
            <p style="margin: 10px 0 0; color: #666; font-size: 0.95rem;">
                <i class="fas fa-user"></i> Started by <strong><?php echo htmlspecialchars($konu['kullanici_adi']); ?></strong> 
                &nbsp;|&nbsp; 
                <i class="far fa-clock"></i> <?php echo date('d M Y, H:i', strtotime($konu['olusturma_tarihi'])); ?>
            </p>
        </div>

        <h3 style="margin: 30px 0 20px;">
            <i class="fas fa-comments"></i> Comments (<?php echo count($yorumlar); ?>)
        </h3>

        <div class="comments-section">
            <?php if (!empty($yorumlar)): ?>
                <?php foreach ($yorumlar as $yorum): ?>
                    <div class="comment-item">
                        <div class="comment-meta">
                            <span style="font-weight: bold; color: var(--text-main);">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($yorum['kullanici_adi']); ?>
                            </span> 
                            <span style="float: right; font-size: 0.85rem;">
                                <?php echo date('d M Y, H:i', strtotime($yorum['olusturma_tarihi'])); ?>
                            </span>
                        </div>
                        <div class="comment-body" style="color: #444;">
                            <?php echo nl2br(htmlspecialchars($yorum['yorum'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info text-center" style="background: #f8f9fa; border: none; color: #777;">
                    No comments yet. Be the first to share your thoughts!
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card" style="margin-top: 30px; background: #f8f9fa; border: none;">
            <?php if (isset($_SESSION['user_id'])): ?>
                <form action="topic.php?id=<?php echo $konu_id; ?>" method="POST">
                    <h4 style="margin-top:0; margin-bottom: 15px;">Leave a Reply</h4>
                    <div class="form-group">
                        <textarea name="yorum_metni" rows="4" class="form-control" required placeholder="Write your comment here..." style="background: white; resize: vertical;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Post Comment</button>
                </form>
            <?php else: ?>
                <div class="text-center">
                    Please <a href="../auth/login.php" style="color: var(--primary-blue); font-weight: bold; text-decoration: underline;">login</a> to join the discussion.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>