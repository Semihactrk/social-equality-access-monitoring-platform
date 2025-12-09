<?php
session_start();
// Yetkili koruması
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'yetkili') {
    header("Location: index.php");
    exit();
}

require_once 'includes/db_connect.php';
$yetkili_id = $_SESSION['user_id'];

// 1. Üstlenilecek Raporları Çek (durum = 'beklemede')
// GÜNCELLEME: fotograf_yolu ve sdg_kategori eklendi
$bekleyen_raporlar = [];
$sql_bekleyen = "SELECT id, baslik, aciklama, olusturma_tarihi, fotograf_yolu, sdg_kategori 
                 FROM raporlar WHERE durum = 'beklemede' ORDER BY olusturma_tarihi DESC";
$result_bekleyen = $conn->query($sql_bekleyen);
if ($result_bekleyen) {
    while ($row = $result_bekleyen->fetch_assoc()) {
        $bekleyen_raporlar[] = $row;
    }
}

// 2. Bu Yetkilinin Üstlendiği Raporları Çek (durum = 'islemde' VE ustlenen_yetkili_id = kendi ID'si)
// GÜNCELLEME: fotograf_yolu ve sdg_kategori eklendi
$islemdeki_raporlar = [];
$sql_islemde = "SELECT id, baslik, aciklama, olusturma_tarihi, fotograf_yolu, sdg_kategori 
                FROM raporlar WHERE durum = 'islemde' AND ustlenen_yetkili_id = ? ORDER BY olusturma_tarihi DESC";
if($stmt = $conn->prepare($sql_islemde)) {
    $stmt->bind_param("i", $yetkili_id);
    $stmt->execute();
    $result_islemde = $stmt->get_result();
    while ($row = $result_islemde->fetch_assoc()) {
        $islemdeki_raporlar[] = $row;
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
    <title>Yetkili Paneli - Şehrin Nabzı</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2 { border-bottom: 2px solid #17a2b8; padding-bottom: 10px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; vertical-align: top; }
        th { background-color: #17a2b8; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        
        button { padding: 8px 12px; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; }
        .claim-btn { background-color: #28a745; }
        .claim-btn:hover { background-color: #218838; }
        .resolve-btn { background-color: #007bff; }
        .resolve-btn:hover { background-color: #0069d9; }
        
        .logout-link { display: block; text-align: right; margin-bottom: 20px; color: #dc3545; text-decoration: none; font-weight: bold; }
        .img-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer; }
        .sdg-badge { display: inline-block; background-color: #6f42c1; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.75em; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="logout.php" class="logout-link">Güvenli Çıkış Yap</a>
        <h1>Saha Ekibi (Yetkili) Paneli</h1>

        <h2>🚨 Müdahale Bekleyen Yeni Raporlar</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 10%;">Görsel</th>
                    <th style="width: 25%;">Başlık & SDG</th>
                    <th style="width: 40%;">Detay</th>
                    <th style="width: 20%;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($bekleyen_raporlar)): ?>
                    <?php foreach ($bekleyen_raporlar as $rapor): ?>
                        <tr>
                            <td>#<?php echo $rapor['id']; ?></td>
                            <td>
                                <?php if (!empty($rapor['fotograf_yolu'])): ?>
                                    <a href="<?php echo htmlspecialchars($rapor['fotograf_yolu']); ?>" target="_blank">
                                        <img src="<?php echo htmlspecialchars($rapor['fotograf_yolu']); ?>" class="img-thumb" alt="Kanıt">
                                    </a>
                                <?php else: ?>
                                    <span style="color:#999; font-size:0.8em;">Görsel Yok</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!empty($rapor['sdg_kategori']) && $rapor['sdg_kategori'] != 'Genel'): ?>
                                    <span class="sdg-badge"><?php echo htmlspecialchars($rapor['sdg_kategori']); ?></span><br>
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($rapor['baslik']); ?></strong><br>
                                <small style="color:#666;"><?php echo date('d.m.Y H:i', strtotime($rapor['olusturma_tarihi'])); ?></small>
                            </td>
                            <td><?php echo nl2br(htmlspecialchars($rapor['aciklama'])); ?></td>
                            <td>
                                <form action="claim_report.php" method="POST">
                                    <input type="hidden" name="rapor_id" value="<?php echo $rapor['id']; ?>">
                                    <button type="submit" class="claim-btn">Göreve Başla (Üstlen)</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">Şu an bekleyen yeni bir görev yok.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h2 style="margin-top: 40px;">🛠️ Üzerimdeki Görevler (İşlemde)</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 10%;">Görsel</th>
                    <th style="width: 25%;">Başlık & SDG</th>
                    <th style="width: 40%;">Detay</th>
                    <th style="width: 20%;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($islemdeki_raporlar)): ?>
                    <?php foreach ($islemdeki_raporlar as $rapor): ?>
                        <tr>
                            <td>#<?php echo $rapor['id']; ?></td>
                            <td>
                                <?php if (!empty($rapor['fotograf_yolu'])): ?>
                                    <a href="<?php echo htmlspecialchars($rapor['fotograf_yolu']); ?>" target="_blank">
                                        <img src="<?php echo htmlspecialchars($rapor['fotograf_yolu']); ?>" class="img-thumb" alt="Kanıt">
                                    </a>
                                <?php else: ?>
                                    <span style="color:#999; font-size:0.8em;">Görsel Yok</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!empty($rapor['sdg_kategori']) && $rapor['sdg_kategori'] != 'Genel'): ?>
                                    <span class="sdg-badge"><?php echo htmlspecialchars($rapor['sdg_kategori']); ?></span><br>
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($rapor['baslik']); ?></strong>
                            </td>
                            <td><?php echo nl2br(htmlspecialchars($rapor['aciklama'])); ?></td>
                            <td>
                                <form action="resolve_report.php" method="POST">
                                    <input type="hidden" name="rapor_id" value="<?php echo $rapor['id']; ?>">
                                    <button type="submit" class="resolve-btn">✅ Çözüldü Olarak İşaretle</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">Şu an üzerinizde aktif bir görev bulunmuyor.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>