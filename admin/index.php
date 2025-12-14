<?php
// Backend Logic - DO NOT TOUCH
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

$raporlar = [];
$sql = "SELECT r.id, r.baslik, r.durum, r.olusturma_tarihi, k.kullanici_adi 
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
    <style>
        .status-badge {
            padding: 4px 8px; border-radius: 4px; color: white; font-size: 0.8rem; font-weight: bold; text-transform: uppercase;
        }
        .status-beklemede { background-color: #f1c40f; }
        .status-islemde { background-color: #3498db; }
        .status-cozuldu { background-color: #27ae60; }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        <nav class="sidebar">
            <div class="sidebar-header">Admin Panel</div>
            <div class="sidebar-menu">
                <a href="../index.php">&larr; Public Map</a>
                <a href="index.php" class="active">Manage Reports</a>
                <a href="manage_users.php">Manage Users</a>
                <a href="../auth/logout.php" style="color: #e74c3c;">Logout</a>
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
                            <th>Date</th>
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
                                    <td><?php echo date('d M Y', strtotime($rapor['olusturma_tarihi'])); ?></td>
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
