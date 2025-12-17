<?php
session_start();
require_once '../includes/db_connect.php';

// Backend Logic - DO NOT TOUCH
$konular = [];
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
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">Social Equality Map</div>
        <div class="navbar-menu">
            <a href="../index.php">Home</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="../user/profile.php">My Profile</a>
                <a href="../auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="../auth/login.php">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Community Discussions</h2>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="new_topic.php" class="btn btn-primary">+ Start New Topic</a>
            <?php endif; ?>
        </div>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="alert alert-info" style="background: #e3f2fd; color: #0d47a1;">
                Please <a href="../auth/login.php" style="text-decoration: underline; font-weight: bold;">login</a> to start a topic or reply.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60%;">Topic Title</th>
                        <th>Started By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($konular)): ?>
                        <?php foreach ($konular as $konu): ?>
                            <tr>
                                <td>
                                    <a href="topic.php?id=<?php echo $konu['id']; ?>" style="color: var(--primary-blue); font-weight: 600; font-size: 1.05rem;">
                                        <?php echo htmlspecialchars($konu['baslik']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($konu['kullanici_adi']); ?></td>
                                <td style="color: #666; font-size: 0.9rem;">
                                    <?php echo date('d M Y', strtotime($konu['olusturma_tarihi'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center" style="padding: 30px; color: #999;">
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