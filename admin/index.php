<?php
// Oturumu başlat
session_start();

// Kullanıcının giriş yapıp yapmadığını ve rolünün 'admin' olup olmadığını kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    // Eğer giriş yapmamışsa veya admin değilse, ana sayfaya yönlendir
    header("Location: ../index.php");
    exit();
}

// Admin ise, sayfanın geri kalanı yüklenir...
require_once '../includes/db_connect.php';

// Tüm raporları, kullanıcı adlarıyla birlikte çekmek için JOIN sorgusu
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
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Şehrin Nabzı</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { border-bottom: 2px solid #333; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #333; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .logout-link { display: block; text-align: right; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="logout.php" class="logout-link">Güvenli Çıkış Yap</a>
        <a href="manage_users.php" style="display: block; margin-bottom: 20px;">Kullanıcıları Yönet &raquo;</a>
        <h1>Admin Paneli - Tüm Raporlar</h1>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Başlık</th>
                    <th>Gönderen Kullanıcı</th>
                    <th>Tarih</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($raporlar)): ?>
                    <?php foreach ($raporlar as $rapor): ?>
                        <tr>
                            <td><?php echo $rapor['id']; ?></td>
                            <td><?php echo htmlspecialchars($rapor['baslik']); ?></td>
                            <td><?php echo htmlspecialchars($rapor['kullanici_adi']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($rapor['olusturma_tarihi'])); ?></td>
                            <td><?php echo htmlspecialchars($rapor['durum']); ?></td>
                            <td><a href="edit_report.php?id=<?php echo $rapor['id']; ?>">Detay/Değiştir</a></td> </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Sistemde hiç rapor bulunmuyor.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>