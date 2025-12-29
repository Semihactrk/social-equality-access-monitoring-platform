<?php
session_start();
require_once '../includes/db_connect.php';

// Backend Logic
$konular = [];
// Fetch topics with user info
$sql = "SELECT t.id, t.baslik, t.olusturma_tarihi, k.kullanici_adi 
        FROM forum_konulari t
        JOIN kullanicilar k ON t.kullanici_id = k.id
        ORDER BY t.olusturma_tarihi DESC";

$result = $conn->query($sql);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $konular[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Forum - Social Equality Platform</title>
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
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0; color: var(--primary-blue);">Community Forum</h2>
                <p style="color: #666; margin-top: 5px;">Discuss local issues and solutions with neighbors.</p>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="new_topic.php" class="btn btn-primary"><i class="fas fa-plus"></i> Start New Topic</a>
            <?php endif; ?>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa; text-align: left; border-bottom: 2px solid #eee;">
                        <th style="padding: 15px 20px;">Topic Title</th>
                        <th style="padding: 15px 20px;">Started By</th>
                        <th style="padding: 15px 20px;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($konular)): ?>
                        <?php foreach ($konular as $konu): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 15px 20px;">
                                    <a href="topic.php?id=<?php echo $konu['id']; ?>" style="color: var(--primary-blue); font-weight: 600; font-size: 1.05rem; display: block;">
                                        <?php echo htmlspecialchars($konu['baslik']); ?>
                                    </a>
                                </td>
                                <td style="padding: 15px 20px; color: #555;">
                                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($konu['kullanici_adi']); ?>
                                </td>
                                <td style="padding: 15px 20px; color: #888; font-size: 0.9rem;">
                                    <?php echo date('d M Y', strtotime($konu['olusturma_tarihi'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center" style="padding: 40px; color: #999;">
                                <i class="far fa-comments fa-3x" style="margin-bottom: 15px; display: block;"></i>
                                No discussions yet. Be the first to start one!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>