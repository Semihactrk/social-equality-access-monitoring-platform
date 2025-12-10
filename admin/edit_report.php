<?php
session_start();
// Admin koruması
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once '../includes/db_connect.php';

// URL'den gelen rapor ID'sini al
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Geçerli bir ID yoksa admin paneline geri yönlendir
    header("Location: admin.php");
    exit();
}
$rapor_id = $_GET['id'];

// --- FORM GÖNDERİLDİYSE DURUMU GÜNCELLE ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $yeni_durum = $_POST['durum'];
    // Güvenlik için olası durumları bir listede tutalım
    $gecerli_durumlar = ['beklemede', 'islemde', 'cozuldu'];

    if (in_array($yeni_durum, $gecerli_durumlar)) {
        $update_sql = "UPDATE raporlar SET durum = ? WHERE id = ?";
        if ($stmt = $conn->prepare($update_sql)) {
            $stmt->bind_param("si", $yeni_durum, $rapor_id);
            $stmt->execute();
            $stmt->close();
            // Güncelleme sonrası admin paneline geri yönlendir
            header("Location: admin.php?status=updated");
            exit();
        }
    }
}

// --- SAYFA YÜKLENDİĞİNDE RAPOR BİLGİLERİNİ ÇEK ---
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
        // Rapor bulunamazsa paneline yönlendir
        header("Location: admin.php?error=notfound");
        exit();
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
    <title>Raporu Düzenle - Admin Paneli</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .report-detail { border: 1px solid #eee; padding: 15px; margin-bottom: 20px; }
        label { font-weight: bold; }
        select { width: 100%; padding: 8px; margin-top: 5px; margin-bottom: 20px; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin.php">&laquo; Admin Paneline Geri Dön</a>
        <h1>Rapor Detayı (ID: <?php echo $rapor['id']; ?>)</h1>

        <div class="report-detail">
            <h3><?php echo htmlspecialchars($rapor['baslik']); ?></h3>
            <p><strong>Gönderen:</strong> <?php echo htmlspecialchars($rapor['kullanici_adi']); ?></p>
            <p><strong>Tarih:</strong> <?php echo date('d/m/Y H:i', strtotime($rapor['olusturma_tarihi'])); ?></p>
            <p><strong>Açıklama:</strong><br><?php echo nl2br(htmlspecialchars($rapor['aciklama'])); ?></p>
        </div>

        <form action="edit_report.php?id=<?php echo $rapor['id']; ?>" method="post">
            <label for="durum">Raporun Durumunu Güncelle:</label>
            <select name="durum" id="durum">
                <option value="beklemede" <?php if($rapor['durum'] == 'beklemede') echo 'selected'; ?>>Beklemede</option>
                <option value="islemde" <?php if($rapor['durum'] == 'islemde') echo 'selected'; ?>>İşlemde</option>
                <option value="cozuldu" <?php if($rapor['durum'] == 'cozuldu') echo 'selected'; ?>>Çözüldü</option>
            </select>
            <button type="submit">Durumu Güncelle</button>
        </form>
    </div>
</body>
</html>