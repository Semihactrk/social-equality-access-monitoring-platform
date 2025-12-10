<?php
session_start();
// Yetkili koruması
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'yetkili') {
    header("Location: index.php");
    exit();
}

require_once '../includes/db_connect.php';
$yetkili_id = $_SESSION['user_id'];

// 1. Üstlenilecek Raporları Çek (durum = 'beklemede')
$bekleyen_raporlar = [];
$sql_bekleyen = "SELECT id, baslik, olusturma_tarihi FROM raporlar WHERE durum = 'beklemede' ORDER BY olusturma_tarihi DESC";
$result_bekleyen = $conn->query($sql_bekleyen);
if ($result_bekleyen) {
    while ($row = $result_bekleyen->fetch_assoc()) {
        $bekleyen_raporlar[] = $row;
    }
}

// 2. Bu Yetkilinin Üstlendiği Raporları Çek (durum = 'islemde' VE ustlenen_yetkili_id = kendi ID'si)
$islemdeki_raporlar = [];
$sql_islemde = "SELECT id, baslik, olusturma_tarihi FROM raporlar WHERE durum = 'islemde' AND ustlenen_yetkili_id = ? ORDER BY olusturma_tarihi DESC";
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
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1, h2 { border-bottom: 2px solid #17a2b8; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #17a2b8; color: white; }
        button { padding: 5px 10px; color: white; border: none; cursor: pointer; border-radius: 4px; }
        .claim-btn { background-color: #28a745; }
        .resolve-btn { background-color: #007bff; }
        .logout-link { display: block; text-align: right; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="logout.php" class="logout-link">Güvenli Çıkış Yap</a>
        <h1>Yetkili Paneli</h1>

        <h2>Üstlenilecek Yeni Raporlar</h2>
        <table>
            <tbody>
                <?php if (!empty($bekleyen_raporlar)): ?>
                    <?php foreach ($bekleyen_raporlar as $rapor): ?>
                        <tr>
                            <td>#<?php echo $rapor['id']; ?> - <?php echo htmlspecialchars($rapor['baslik']); ?></td>
                            <td>
                                <form action="claim_report.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="rapor_id" value="<?php echo $rapor['id']; ?>">
                                    <button type="submit" class="claim-btn">Bu Raporu Üstlen</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2">Üstlenilecek yeni rapor bulunmuyor.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h2 style="margin-top: 40px;">Üstlendiğim Raporlar (İşlemde)</h2>
        <table>
            <tbody>
                <?php if (!empty($islemdeki_raporlar)): ?>
                    <?php foreach ($islemdeki_raporlar as $rapor): ?>
                        <tr>
                            <td>#<?php echo $rapor['id']; ?> - <?php echo htmlspecialchars($rapor['baslik']); ?></td>
                            <td>
                                <form action="resolve_report.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="rapor_id" value="<?php echo $rapor['id']; ?>">
                                    <button type="submit" class="resolve-btn">İşlemi Tamamla (Çözüldü)</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2">Üzerinizde işlemde olan rapor bulunmuyor.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>