<?php
session_start();
require_once 'includes/db_connect.php';

// ID Check
if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$rapor_id = intval($_GET['id']);
$message = "";

// Handle Comment Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['yorum'])) {
    if (!isset($_SESSION['user_id'])) { header("Location: auth/login.php"); exit(); }
    $yorum = trim($_POST['yorum']);
    if (!empty($yorum)) {
        $stmt = $conn->prepare("INSERT INTO yorumlar (rapor_id, kullanici_id, yorum_metni) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $rapor_id, $_SESSION['user_id'], $yorum);
        
        if($stmt->execute()) {
            $message = "Comment added successfully! ✅";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

// Fetch Report Data
$sql = "SELECT r.*, k.kullanici_adi FROM raporlar r LEFT JOIN kullanicilar k ON r.kullanici_id = k.id WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $rapor_id);
$stmt->execute();
$rapor = $stmt->get_result()->fetch_assoc();
if (!$rapor) { echo "Report not found."; exit(); }

// Fetch Comments
$yorumlar_sql = "SELECT y.*, k.kullanici_adi FROM yorumlar y LEFT JOIN kullanicilar k ON y.kullanici_id = k.id WHERE y.rapor_id = ? ORDER BY y.olusturma_tarihi DESC";
$stmt2 = $conn->prepare($yorumlar_sql);
$stmt2->bind_param("i", $rapor_id);
$stmt2->execute();
$yorumlar = $stmt2->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($rapor['baslik']); ?> - Details</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <nav class="navbar">
        <div class="navbar-menu">
            <a href="index.php">Home</a>
            <a href="forum/index.php">Forum</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="user/submit_report.php" class="btn btn-primary">Submit Report</a>
                <a href="user/profile.php">My Profile</a>
                <a href="auth/logout.php" style="color:var(--primary-red);">Logout</a>
            <?php else: ?>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
                <h1 style="color:var(--primary-blue); margin:0; font-size: 1.8rem;"><?php echo htmlspecialchars($rapor['baslik']); ?></h1>
                
                <?php 
                    $badgeClass = 'pending'; 
                    $statusText = 'Pending';
                    
                    if($rapor['durum'] == 'cozuldu') { 
                        $badgeClass = 'solved'; 
                        $statusText = 'Solved ✅'; 
                    }
                    elseif($rapor['durum'] == 'isleme_alindi' || $rapor['durum'] == 'islemde') { 
                        $badgeClass = 'process'; 
                        $statusText = 'In Progress ⚙️'; 
                    }
                ?>
                <span class="badge <?php echo $badgeClass; ?>">
                    <?php echo $statusText; ?>
                </span>
            </div>

            <p style="color:#666; font-size: 0.95rem; margin-bottom: 20px;">
                <i class="fas fa-user"></i> <strong>Reported By:</strong> <?php echo htmlspecialchars($rapor['kullanici_adi'] ?? 'Anonymous'); ?> 
                &nbsp;&nbsp;|&nbsp;&nbsp; 
                <i class="fas fa-calendar-alt"></i> <strong>Date:</strong> <?php echo date("d M Y, H:i", strtotime($rapor['olusturma_tarihi'])); ?>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <i class="fas fa-tag"></i> <strong>Category:</strong> <?php echo htmlspecialchars($rapor['sdg_kategori'] ?? 'General'); ?>
            </p>

            <div style="background: #f9f9f9; padding: 20px; border-radius: var(--radius); border-left: 4px solid var(--primary-blue);">
                <p style="font-size:1.1rem; line-height:1.8; color: #333; margin:0;"><?php echo nl2br(htmlspecialchars($rapor['aciklama'])); ?></p>
            </div>

            <?php if ($rapor['fotograf_yolu']): ?>
                <?php $img = str_replace('admin/uploads/', 'uploads/', $rapor['fotograf_yolu']); ?>
                <div style="margin-top:25px; text-align:center;">
                    <img src="<?php echo $img; ?>" style="max-width:100%; max-height: 500px; border-radius: var(--radius); box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top: 40px; max-width: 800px; margin-left: auto; margin-right: auto;">
            <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px;">
                <i class="fas fa-comments"></i> Discussion
            </h3>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="card" style="background: #f8f9fa; border: none;">
                    <?php if($message) echo "<div class='alert alert-success'>$message</div>"; ?>
                    <form method="post">
                        <div class="form-group">
                            <label>Leave a comment:</label>
                            <textarea name="yorum" rows="3" placeholder="Share your thoughts or updates about this issue..." required style="resize: vertical;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Post Comment</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    Please <a href="auth/login.php" style="font-weight:bold; text-decoration: underline;">login</a> to join the discussion.
                </div>
            <?php endif; ?>

            <?php while($yorum = $yorumlar->fetch_assoc()): ?>
                <div class="comment-item">
                    <div class="comment-meta">
                        <span style="font-weight: bold; color: var(--primary-blue);"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($yorum['kullanici_adi']); ?></span>
                        <span style="float:right; font-size: 0.8rem;"><i class="fas fa-clock"></i> <?php echo date("d M Y, H:i", strtotime($yorum['olusturma_tarihi'])); ?></span>
                    </div>
                    <div class="comment-body" style="color: #444;">
                        <?php echo nl2br(htmlspecialchars($yorum['yorum_metni'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
            
            <?php if($yorumlar->num_rows == 0): ?>
                <div style="text-align: center; padding: 30px; color: #999;">
                    <i class="far fa-comment-dots fa-3x" style="margin-bottom: 10px; display: block;"></i>
                    No comments yet. Be the first to start the discussion!
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>