<?php
// Oturumu başlat
session_start();

// Kullanıcı giriş yapmamışsa, login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Veritabanı bağlantısı
require_once '../includes/db_connect.php';

// Giriş yapmış kullanıcının ID'sini session'dan al
$kullanici_id = $_SESSION['user_id'];

// Sadece bu kullanıcıya ait raporları, en yeniden eskiye doğru sıralayarak çek
$raporlar = [];
$sql = "SELECT baslik, aciklama, durum, olusturma_tarihi FROM raporlar WHERE kullanici_id = ? ORDER BY olusturma_tarihi DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $raporlar[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - Şehrin Nabzı</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .navbar { background-color: #333; color: white; padding: 15px; text-align: right; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; }
        .container { max-width: 900px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .report { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
        .report h3 { margin-top: 0; }
        .report-meta { font-size: 0.9em; color: #777; margin-bottom: 10px; }
        .report-status { display: inline-block; padding: 3px 8px; border-radius: 12px; color: white; font-weight: bold; text-transform: capitalize; }
        .status-beklemede { background-color: #ffc107; }
        .status-islemde { background-color: #17a2b8; }
        .status-cozuldu { background-color: #28a745; }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="../index.php">Ana Sayfa (Harita)</a>
        <a href="submit_report.php">Rapor Gönder</a>
        <a href="../logout.php">Çıkış Yap</a>
    </div>

    <div class="container">
        <h2><?php echo htmlspecialchars($_SESSION['kullanici_adi']); ?> Profili</h2>
        <hr>
        <h3>Gönderdiğim Raporlar</h3>

        <?php if (!empty($raporlar)): ?>
            <?php foreach ($raporlar as $rapor): ?>
                <div class="report">
                    <h3><?php echo htmlspecialchars($rapor['baslik']); ?></h3>
                    <div class="report-meta">
                        <span>Tarih: <?php echo date('d/m/Y H:i', strtotime($rapor['olusturma_tarihi'])); ?></span> | 
                        <span>Durum: 
                            <span class="report-status status-<?php echo htmlspecialchars($rapor['durum']); ?>">
                                <?php echo htmlspecialchars($rapor['durum']); ?>
                            </span>
                        </span>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($rapor['aciklama'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Henüz hiç rapor göndermediniz.</p>
        <?php endif; ?>
    </div>

</body>
</html>