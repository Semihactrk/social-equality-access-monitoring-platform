<?php
session_start();
require_once 'includes/db_connect.php';

// Veritabanından raporları çek (Fotoğraf ve SDG dahil!)
// Sadece beklemede ve işlemde olanları gösterelim (Çözülenler kirlilik yapmasın)
$raporlar = [];
$sql = "SELECT baslik, aciklama, enlem, boylam, fotograf_yolu, sdg_kategori, durum FROM raporlar WHERE durum != 'cozuldu'"; 
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $raporlar[] = $row;
    }
}
$conn->close();

$raporlar_json = json_encode($raporlar);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Sayfa - Şehrin Nabzı</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { font-family: sans-serif; margin: 0; display: flex; flex-direction: column; height: 100vh; }
        .navbar { background-color: #333; color: white; padding: 15px; text-align: right; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 1000; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; }
        .success-message { text-align: center; padding: 10px; background-color: #DFF2BF; color: #4F8A10; }
        #map { height: 100%; width: 100%; }
        
        /* Popup içindeki resim stili */
        .popup-img {
            width: 100%;
            max-height: 150px;
            object-fit: cover;
            border-radius: 4px;
            margin-top: 5px;
        }
        .sdg-tag {
            background-color: #e3f2fd;
            color: #0d47a1;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    <div class="navbar">
    <?php if (isset($_SESSION['user_id'])): ?>
        <span>Merhaba, <strong><?php echo htmlspecialchars($_SESSION['kullanici_adi']); ?></strong>!</span>
        
        <a href="user/profile.php">Profilim</a>
        
        <a href="forum/">Forum</a>
        
        <a href="user/submit_report.php">Rapor Gönder</a>
        
        <a href="auth/logout.php">Çıkış Yap</a>

    <?php else: ?>
        <a href="forum/">Forum</a>
        
        <a href="auth/login.php">Giriş Yap</a>
        <a href="auth/register.php">Kayıt Ol</a>
        
        <a href="admin/">Admin Paneli</a> <?php endif; ?>
</div>
    <?php
    if (isset($_GET['report_success']) && $_GET['report_success'] == '1') {
        echo '<div class="success-message">Raporunuz ve fotoğrafınız başarıyla sisteme yüklendi!</div>';
    }
    ?>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('map').setView([41.015137, 28.979530], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var raporlarData = <?php echo $raporlar_json; ?>;

        raporlarData.forEach(function(rapor) {
            var marker = L.marker([rapor.enlem, rapor.boylam]).addTo(map);
            
            // --- Popup İçeriğini Hazırla ---
            var popupContent = "";
            
            // 1. Varsa SDG Kategorisini ekle
            if (rapor.sdg_kategori) {
                popupContent += `<span class='sdg-tag'>${rapor.sdg_kategori}</span><br>`;
            }

            // 2. Başlık ve Açıklama
            popupContent += `<b>${rapor.baslik}</b><br>${rapor.aciklama}<br>`;

            // 3. Varsa Fotoğrafı ekle
            if (rapor.fotograf_yolu) {
                popupContent += `<br><img src='${rapor.fotograf_yolu}' class='popup-img' alt='Rapor Fotoğrafı'>`;
            }

            // 4. Durumu ekle
            popupContent += `<br><i>Durum: ${rapor.durum}</i>`;

            marker.bindPopup(popupContent);
        });
    </script>
</body>
</html>