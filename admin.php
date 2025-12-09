<?php
// Oturumu başlat
session_start();

// Kullanıcının giriş yapıp yapmadığını ve rolünün 'admin' olup olmadığını kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once 'includes/db_connect.php';

// --- MÜHENDİSLİK ALGORİTMASI: ACİLİYET PUANI HESAPLAMA ---
// Bu fonksiyon, metin madenciliği ve zaman analizi yaparak rapora bir 'öncelik puanı' atar.
function aciliyetPuaniHesapla($baslik, $aciklama, $tarih) {
    $puan = 0;
    
    // 1. KRİTER: Anahtar Kelime Analizi (Metin Madenciliği)
    // Acil durum ve erişilebilirlik kelimelerine yüksek puan veriyoruz
    $kritik_kelimeler = ['kaza', 'elektrik', 'yangın', 'patlama', 'çökme', 'acil', 'tehlike', 'gaz', 'yaralı'];
    $onemli_kelimeler = ['engelli', 'tekerlekli', 'rampa', 'asansör', 'yaşlı', 'erişim', 'karanlık', 'su', 'kaygan', 'okul'];

    // Küçük harfe çevirerek arama yap (büyük/küçük harf duyarlılığını kaldır)
    $metin = mb_strtolower($baslik . ' ' . $aciklama);

    foreach ($kritik_kelimeler as $kelime) {
        if (strpos($metin, $kelime) !== false) $puan += 50; // Kritik kelime başına +50 puan
    }
    foreach ($onemli_kelimeler as $kelime) {
        if (strpos($metin, $kelime) !== false) $puan += 30; // Önemli kelime başına +30 puan
    }

    // 2. KRİTER: Bekleme Süresi (Zaman Analizi)
    // Rapor ne kadar eskiyse önceliği o kadar artmalı (Her saat için +1 puan)
    // Bu, eski raporların zamanla yukarı tırmanmasını sağlar.
    $gecen_saat = (time() - strtotime($tarih)) / 3600;
    $puan += round($gecen_saat); 

    return $puan;
}
// ---------------------------------------------------------

// Verileri Çek
$raporlar = [];
// Fotoğraf yolunu da çekiyoruz (r.fotograf_yolu)
$sql = "SELECT r.id, r.baslik, r.aciklama, r.durum, r.olusturma_tarihi, r.fotograf_yolu, k.kullanici_adi 
        FROM raporlar r 
        JOIN kullanicilar k ON r.kullanici_id = k.id";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Her rapor için aciliyet puanını hesapla
        if ($row['durum'] == 'cozuldu') {
            // Çözülmüş raporların puanını çok düşük yap ki en alta düşsünler
            $row['aciliyet_puani'] = -1000;
        } else {
            $row['aciliyet_puani'] = aciliyetPuaniHesapla($row['baslik'], $row['aciklama'], $row['olusturma_tarihi']);
        }
        $raporlar[] = $row;
    }
}

// 3. ADIM: Diziyi Aciliyet Puanına Göre Sırala (Büyükten Küçüğe)
// usort fonksiyonu, özel bir sıralama kuralı belirlememizi sağlar.
usort($raporlar, function($a, $b) {
    return $b['aciliyet_puani'] <=> $a['aciliyet_puani'];
});

$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Akıllı Sıralama</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f0f2f5; }
        .container { max-width: 1200px; margin: 30px auto; padding: 25px; background-color: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { border-bottom: 3px solid #007bff; padding-bottom: 15px; color: #333; }
        .top-links { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .top-links a { text-decoration: none; color: #007bff; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #343a40; color: white; text-transform: uppercase; font-size: 0.85em; letter-spacing: 1px; }
        tr:hover { background-color: #f8f9fa; }
        
        .priority-high { color: #dc3545; font-weight: bold; } /* Yüksek Öncelik */
        .priority-medium { color: #ffc107; font-weight: bold; } /* Orta Öncelik */
        .priority-low { color: #28a745; font-weight: bold; } /* Düşük Öncelik */
        
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8em; color: white; }
        .bg-beklemede { background-color: #ffc107; color: black; }
        .bg-islemde { background-color: #17a2b8; }
        .bg-cozuldu { background-color: #28a745; }

        .btn-edit { background-color: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.9em; }
        .img-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-links">
            <a href="manage_users.php">👥 Kullanıcıları Yönet</a>
            <a href="logout.php" style="color: #dc3545;">Güvenli Çıkış Yap</a>
        </div>

        <h1>Admin Paneli - Akıllı Öncelik Yönetimi</h1>
        <p style="color: #666; font-style: italic;">Raporlar, içerik analizi ve bekleme süresine göre otomatik olarak önceliklendirilmiştir.</p>
        
        <table>
            <thead>
                <tr>
                    <th>Öncelik Puanı</th>
                    <th>Fotoğraf</th>
                    <th>Başlık</th>
                    <th>Gönderen</th>
                    <th>Tarih</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($raporlar)): ?>
                    <?php foreach ($raporlar as $rapor): ?>
                        <tr>
                            <td>
                                <?php if($rapor['durum'] == 'cozuldu'): ?>
                                    <span style="color: grey;">✓ Tamamlandı</span>
                                <?php else: ?>
                                    <?php 
                                        $p = $rapor['aciliyet_puani'];
                                        $class = ($p > 50) ? 'priority-high' : (($p > 20) ? 'priority-medium' : 'priority-low');
                                    ?>
                                    <span class="<?php echo $class; ?>"><?php echo $p; ?> Puan</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!empty($rapor['fotograf_yolu'])): ?>
                                    <a href="<?php echo htmlspecialchars($rapor['fotograf_yolu']); ?>" target="_blank">
                                        <img src="<?php echo htmlspecialchars($rapor['fotograf_yolu']); ?>" class="img-thumb" alt="Kanıt">
                                    </a>
                                <?php else: ?>
                                    <span style="color: #ccc; font-size: 0.8em;">Yok</span>
                                <?php endif; ?>
                            </td>

                            <td><?php echo htmlspecialchars($rapor['baslik']); ?></td>
                            <td><?php echo htmlspecialchars($rapor['kullanici_adi']); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($rapor['olusturma_tarihi'])); ?></td>
                            
                            <td>
                                <span class="status-badge bg-<?php echo $rapor['durum']; ?>">
                                    <?php echo ucfirst($rapor['durum']); ?>
                                </span>
                            </td>
                            <td><a href="edit_report.php?id=<?php echo $rapor['id']; ?>" class="btn-edit">Detay/Yönet</a></td> 
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">Sistemde hiç rapor bulunmuyor.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>