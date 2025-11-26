<?php
session_start();
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
        .navbar { background-color: #333; color: white; padding: 15px; text-align: right; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; }
        #map { height: 100%; width: 100%; } /* Haritanın tüm kalan alanı kaplamasını sağlar */
    </style>
</head>
<body>

    <div class="navbar">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span>Merhaba, <strong><?php echo htmlspecialchars($_SESSION['kullanici_adi']); ?></strong>!</span>
            <a href="profile.php">Profilim</a>
            <a href="forum.php">Forum</a>
            <a href="submit_report.php">Rapor Gönder</a> <a href="logout.php">Çıkış Yap</a>
        <?php else: ?>
            <a href="login.php">Giriş Yap</a>
            <a href="register.php">Kayıt Ol</a>
        <?php endif; ?>
    </div>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Haritayı oluştur ve merkezini ve zoom seviyesini ayarla
        // Örnek olarak İstanbul koordinatları kullanıldı.
        var map = L.map('map').setView([41.015137, 28.979530], 12);

        // Harita katmanını ekle (OpenStreetMap'in ücretsiz katmanını kullanıyoruz)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Haritaya bir işaretçi (marker) ekleme örneği
        var marker = L.marker([41.015137, 28.979530]).addTo(map);
        marker.bindPopup("<b>Burası İstanbul!</b><br>Raporlar bu şekilde işaretlenecek.").openPopup();
    </script>

</body>
</html>