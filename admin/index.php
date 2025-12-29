<?php
// Backend Logic - DO NOT TOUCH
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

$raporlar = [];
$sql = "SELECT r.id, r.baslik, r.durum, r.olusturma_tarihi, r.guncelleme_tarihi, k.kullanici_adi 
        FROM raporlar r 
        JOIN kullanicilar k ON r.kullanici_id = k.id 
        ORDER BY r.olusturma_tarihi DESC";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $raporlar[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Social Equality Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-badge {
            padding: 4px 8px; border-radius: 4px; color: white; font-size: 0.8rem; font-weight: bold; text-transform: uppercase;
        }
        .status-beklemede { background-color: #f1c40f; color: #333; }
        .status-islemde { background-color: var(--primary-blue); }
        .status-cozuldu { background-color: var(--primary-green); }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        <nav class="sidebar">
            <div class="sidebar-header">Admin Panel</div>
            <div class="sidebar-menu">
                <a href="../index.php">
                    <i class="fas fa-map-marked-alt"></i> &nbsp; Public Map
                </a>
                <a href="index.php" class="active">
                    <i class="fas fa-list-alt"></i> &nbsp; Manage Reports
                </a>
                <a href="manage_users.php">
                    <i class="fas fa-users"></i> &nbsp; Manage Users
                </a>
                <a href="../auth/logout.php" style="color: #e74c3c;">
                    <i class="fas fa-sign-out-alt"></i> &nbsp; Logout
                </a>
            </div>
        </nav>

        <main class="main-content">
            <h2 style="margin-bottom: 20px;">All Reports Overview</h2>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'updated'): ?>
                <div class="alert alert-success">Report status updated successfully.</div>
            <?php endif; ?>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Reported By</th>
                            <th style="width: 20%;">Dates</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($raporlar)): ?>
                            <?php foreach ($raporlar as $rapor): ?>
                                <tr>
                                    <td>#<?php echo $rapor['id']; ?></td>
                                    <td><?php echo htmlspecialchars($rapor['baslik']); ?></td>
                                    <td><?php echo htmlspecialchars($rapor['kullanici_adi']); ?></td>
                                    
                                    <td>
                                        <span style="display: block; font-weight: 600; font-size: 0.95rem; color: #34495e;">
                                            Gönderildi: <?php echo date('d M Y, H:i', strtotime($rapor['olusturma_tarihi'])); ?>
                                        </span>
                                        
                                        <?php 
                                            // Sadece guncelleme_tarihi, oluşturma tarihinden farklıysa göster
                                            if ($rapor['guncelleme_tarihi'] && strtotime($rapor['guncelleme_tarihi']) > strtotime($rapor['olusturma_tarihi'])): 
                                        ?>
                                            <span style="display: block; font-size: 0.9rem; color: var(--primary-green);">
                                                Güncellendi: <?php echo date('d M Y, H:i', strtotime($rapor['guncelleme_tarihi'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <span class="status-badge status-<?php echo $rapor['durum']; ?>">
                                            <?php 
                                                // Translate status
                                                if($rapor['durum'] == 'beklemede') echo 'Pending';
                                                elseif($rapor['durum'] == 'islemde') echo 'In Progress';
                                                else echo 'Resolved';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_report.php?id=<?php echo $rapor['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;">Edit / View</a>
                                    </td> 
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No reports found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>