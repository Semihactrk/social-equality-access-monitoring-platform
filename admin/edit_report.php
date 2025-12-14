<?php
// Backend Logic - DO NOT TOUCH
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once '../includes/db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$rapor_id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $yeni_durum = $_POST['durum'];
    $gecerli_durumlar = ['beklemede', 'islemde', 'cozuldu'];

    if (in_array($yeni_durum, $gecerli_durumlar)) {
        $update_sql = "UPDATE raporlar SET durum = ? WHERE id = ?";
        if ($stmt = $conn->prepare($update_sql)) {
            $stmt->bind_param("si", $yeni_durum, $rapor_id);
            $stmt->execute();
            $stmt->close();
            header("Location: index.php?status=updated");
            exit();
        }
    }
}

$sql = "SELECT r.id, r.baslik, r.aciklama, r.durum, r.olusturma_tarihi, k.kullanici_adi 
        FROM raporlar r 
        JOIN kullanicilar k ON r.kullanici_id = k.id 
        WHERE r.id = ?";

$rapor = null;
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $rapor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $rapor = $result->fetch_assoc();
    } else {
        header("Location: index.php?error=notfound");
        exit();
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar">
            <div class="sidebar-header">Admin Panel</div>
            <div class="sidebar-menu">
                <a href="index.php">&larr; Back to Reports</a>
            </div>
        </nav>

        <main class="main-content">
            <div style="background: white; padding: 30px; border-radius: var(--radius); border: 1px solid var(--border-color); max-width: 800px;">
                <h2 style="color: var(--primary-blue);">Report Details (#<?php echo $rapor['id']; ?>)</h2>
                
                <div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: var(--radius);">
                    <h3 style="margin-top:0;"><?php echo htmlspecialchars($rapor['baslik']); ?></h3>
                    <p><strong>Submitted by:</strong> <?php echo htmlspecialchars($rapor['kullanici_adi']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('d M Y, H:i', strtotime($rapor['olusturma_tarihi'])); ?></p>
                    <hr style="border:0; border-top:1px solid #ddd; margin: 10px 0;">
                    <p style="line-height: 1.8;"><?php echo nl2br(htmlspecialchars($rapor['aciklama'])); ?></p>
                </div>

                <form action="edit_report.php?id=<?php echo $rapor['id']; ?>" method="post">
                    <div class="form-group">
                        <label for="durum">Update Status:</label>
                        <select name="durum" id="durum" style="max-width: 300px;">
                            <option value="beklemede" <?php if($rapor['durum'] == 'beklemede') echo 'selected'; ?>>Pending (Beklemede)</option>
                            <option value="islemde" <?php if($rapor['durum'] == 'islemde') echo 'selected'; ?>>In Progress (İşlemde)</option>
                            <option value="cozuldu" <?php if($rapor['durum'] == 'cozuldu') echo 'selected'; ?>>Resolved (Çözüldü)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="index.php" class="btn" style="background:#eee; color:#333; margin-left: 10px;">Cancel</a>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
