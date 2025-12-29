<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
require_once '../includes/db_connect.php';

$sql = "SELECT r.*, k.kullanici_adi FROM raporlar r JOIN kullanicilar k ON r.kullanici_id = k.id ORDER BY r.olusturma_tarihi DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Reports Overview - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>

                <a href="../user/profile.php"><i class="fas fa-user"></i> My Profile / Reports</a>

                <a href="../user/submit_report.php"><i class="fas fa-plus-circle"></i> Submit New Report</a>

                <a href="../forum/index.php"><i class="fas fa-comments"></i> Forum</a>

                <a href="manage_aid.php"><i class="fas fa-shipping-fast"></i> Manage Aid Distribution</a>

                <a href="../auth/logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <h1>All Reports Overview</h1>
            <div class="card">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Reported By</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['baslik']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['kullanici_adi']); ?></td>
                            <td>
                                <div style="font-size: 0.85rem;">
                                    <span style="color: #666;">Gönderildi:</span> <?php echo date('d M Y, H:i', strtotime($row['olusturma_tarihi'])); ?><br>
                                    <span style="color: #2ecc71;">Güncellendi:</span> <?php echo date('d M Y, H:i', strtotime($row['guncelleme_tarihi'])); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php 
                                    if ($row['durum'] == 'beklemede') echo 'pending';
                                    elseif ($row['durum'] == 'islemde' || $row['durum'] == 'IN PROGRESS') echo 'process';
                                    elseif ($row['durum'] == 'cozuldu') echo 'solved';
                                    else echo 'process'; // Varsayılan olarak turkuaz
                                ?>">
                                    <?php 
                                        // Görseldeki gibi "IN PROGRESS" formatında yazdırır
                                        if ($row['durum'] == 'beklemede') echo 'PENDING';
                                        elseif ($row['durum'] == 'islemde' || $row['durum'] == 'IN PROGRESS') echo 'IN PROGRESS';
                                        elseif ($row['durum'] == 'cozuldu') echo 'RESOLVED';
                                        else echo strtoupper(str_replace('_', ' ', $row['durum'])); 
                                    ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_report.php?id=<?php echo $row['id']; ?>" class="btn-action">Edit / View</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>