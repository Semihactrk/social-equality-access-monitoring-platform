<?php
session_start();

// Güvenlik: Sadece adminler girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// URL'den gelen ID'yi al
if (!isset($_GET['id'])) {
    header("Location: manage_reports.php");
    exit();
}

$report_id = $_GET['id'];
$message = "";

// DURUM GÜNCELLEME İŞLEMİ (Form gönderildiğinde çalışır)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    
    $update_sql = "UPDATE raporlar SET durum = ?, guncelleme_tarihi = NOW() WHERE id = ?";
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("si", $new_status, $report_id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Report status updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-error'>Error updating status.</div>";
        }
        $stmt->close();
    }
}

// RAPOR DETAYLARINI ÇEK
$sql = "SELECT r.*, k.kullanici_adi, k.email 
        FROM raporlar r 
        JOIN kullanicilar k ON r.kullanici_id = k.id 
        WHERE r.id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();
}

if (!$report) {
    die("Report not found!");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Report #<?php echo $report['id']; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <a href="manage_reports.php" class="active"><i class="fas fa-arrow-left"></i> Back to Reports</a>
                <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="../auth/logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <h1>Review Report #<?php echo $report['id']; ?></h1>
            <?php echo $message; ?>

            <div class="card" style="display: flex; gap: 30px; flex-wrap: wrap;">
                <div style="flex: 2; min-width: 300px;">
                    <h3 style="margin-bottom: 20px; border-bottom: 2px solid #f4f6f9; padding-bottom: 10px;">Report Information</h3>
                    
                    <div style="margin-bottom: 15px;">
                        <strong>Title:</strong> <br>
                        <?php echo htmlspecialchars($report['baslik']); ?>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <strong>Description:</strong> <br>
                        <p style="background: #f8f9fa; padding: 15px; border-radius: 8px; color: #555;">
                            <?php echo nl2br(htmlspecialchars($report['aciklama'])); ?>
                        </p>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <strong>Reported By:</strong> <br>
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($report['kullanici_adi']); ?> 
                        (<span style="color: #666;"><?php echo htmlspecialchars($report['email']); ?></span>)
                    </div>

                    <div>
                        <strong>Dates:</strong> <br>
                        <small>Submitted: <?php echo $report['olusturma_tarihi']; ?></small><br>
                        <small>Last Update: <?php echo $report['guncelleme_tarihi']; ?></small>
                    </div>
                </div>

                <div style="flex: 1; min-width: 250px; background: #fdfdfd; padding: 20px; border-radius: 8px; border: 1px solid #eee;">
                    <h3 style="margin-bottom: 20px;">Management</h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Change Status:</label>
                            <select name="status" class="small-select" style="width: 100%; padding: 10px; margin-bottom: 15px;">
                                <option value="beklemede" <?php if($report['durum'] == 'beklemede') echo 'selected'; ?>>Pending</option>
                                <option value="islemde" <?php if($report['durum'] == 'islemde') echo 'selected'; ?>>In Progress</option>
                                <option value="cozuldu" <?php if($report['durum'] == 'cozuldu') echo 'selected'; ?>>Resolved</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn-action" style="width: 100%; cursor: pointer;">Update Status</button>
                    </form>

                    <?php if (!empty($report['fotograf_yolu'])): ?>
                    <div style="margin-top: 30px;">
                        <strong>Attached Image:</strong> <br>
                        <?php 
                        $db_image_path = $report['fotograf_yolu']; // Veritabanından gelen: "uploads/resim.jpg"
                        
                        if (!empty($db_image_path)): 
                            // Admin klasöründen çıkıp ana dizine ulaşıyoruz: "../uploads/resim.jpg"
                            $final_path = "../" . htmlspecialchars($db_image_path);
                        ?>
                            <a href="<?php echo $final_path; ?>" target="_blank">
                                <img src="<?php echo $final_path; ?>" 
                                    alt="Report Image" 
                                    style="max-width: 100%; border-radius: 8px; margin-top: 10px; border: 1px solid #ddd; box-shadow: 0 4px 8px rgba(0,0,0,0.1);"
                                    onerror="this.src='../assets/img/no-image.png';">
                            </a>
                            <p style="font-size: 0.8rem; color: #666; margin-top: 5px;">(Resmi yeni sekmede açmak için üzerine tıklayın)</p>
                        <?php else: ?>
                            <p style="color: #999; margin-top: 10px; font-style: italic;">Görsel bulunamadı.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>