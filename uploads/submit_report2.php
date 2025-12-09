<?php
// Oturumu başlat
session_start();

// Kullanıcı giriş yapmış mı diye kontrol et
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/db_connect.php';

$errors = [];

// Form gönderilmiş mi diye kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $baslik = trim($_POST['baslik']);
    $aciklama = trim($_POST['aciklama']);
    $enlem = $_POST['enlem'];
    $boylam = $_POST['boylam'];
    $kullanici_id = $_SESSION['user_id'];
    
    // -- FOTOĞRAF YÜKLEME İŞLEMLERİ --
    $fotograf_yolu = NULL; // Varsayılan olarak boş

    // Eğer bir dosya seçildiyse ve hata yoksa (error === 0)
    if (isset($_FILES['rapor_foto']) && $_FILES['rapor_foto']['error'] === 0) {
        $izin_verilen_uzantilar = ['jpg', 'jpeg', 'png', 'gif'];
        $dosya_adi = $_FILES['rapor_foto']['name'];
        $dosya_boyutu = $_FILES['rapor_foto']['size'];
        $dosya_gecici_yolu = $_FILES['rapor_foto']['tmp_name'];
        
        // Dosya uzantısını al ve küçük harfe çevir
        $dosya_uzantisi = strtolower(pathinfo($dosya_adi, PATHINFO_EXTENSION));

        // 1. Uzantı Kontrolü
        if (!in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
            $errors[] = "Sadece JPG, JPEG, PNG ve GIF formatında resimler yükleyebilirsiniz.";
        }
        // 2. Boyut Kontrolü (Örn: 5MB'dan büyük olmasın)
        elseif ($dosya_boyutu > 5000000) {
            $errors[] = "Dosya boyutu çok büyük (Maksimum 5MB).";
        }
        else {
            // Benzersiz bir dosya adı oluştur (çakışmayı önlemek için)
            // Örnek: rapor_15_654a3b1c.jpg
            $yeni_dosya_adi = "rapor_" . $kullanici_id . "_" . uniqid() . "." . $dosya_uzantisi;
            $hedef_klasor = "uploads/" . $yeni_dosya_adi;

            // Dosyayı geçici yerden kalıcı klasöre taşı
            if (move_uploaded_file($dosya_gecici_yolu, $hedef_klasor)) {
                $fotograf_yolu = $hedef_klasor; // Veritabanına kaydedilecek yol
            } else {
                $errors[] = "Fotoğraf yüklenirken bir sunucu hatası oluştu.";
            }
        }
    }
    // -- FOTOĞRAF İŞLEMLERİ BİTTİ --

    if (empty($baslik) || empty($aciklama) || empty($enlem) || empty($boylam)) {
        $errors[] = "Başlık, açıklama ve konum alanları zorunludur.";
    }

    if (empty($errors)) {
        // SQL Sorgusunu güncelle: fotograf_yolu eklendi
        $sql = "INSERT INTO raporlar (kullanici_id, baslik, aciklama, enlem, boylam, fotograf_yolu) VALUES (?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            // Parametreleri bağla (s harfi string için)
            $stmt->bind_param("issdds", $kullanici_id, $baslik, $aciklama, $enlem, $boylam, $fotograf_yolu);

            if ($stmt->execute()) {
                header("Location: index.php?report_success=1");
                exit();
            } else {
                $errors[] = "Veritabanı hatası: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Rapor Gönder - Şehrin Nabzı</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea, input[type="file"] { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        textarea { resize: vertical; height: 100px; }
        button { padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #218838; }
        #report-map { height: 400px; width: 100%; margin-bottom: 15px; border-radius: 4px; }
        .coord-info { font-style: italic; color: #555; }
        .error { color: #D8000C; background-color: #FFD2D2; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Yeni Hizmet Raporu Oluştur</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p id="coord-text" class="coord-info">Konum seçmek için haritaya tıklayın.</p>
        <div id="report-map"></div>

        <form action="submit_report.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="baslik">Rapor Başlığı:</label>
                <input type="text" id="baslik" name="baslik" required>
            </div>
            
            <div class="form-group">
                <label for="rapor_foto">Fotoğraf Ekle (Opsiyonel):</label>
                <input type="file" id="rapor_foto" name="rapor_foto" accept="image/*">
                <small style="color: #666;">Jpg, Png formatında kanıt niteliğinde fotoğraf yükleyebilirsiniz.</small>
            </div>

            <div class="form-group">
                <label for="aciklama">Açıklama:</label>
                <textarea id="aciklama" name="aciklama" required></textarea>
            </div>

            <input type="hidden" id="enlem" name="enlem">
            <input type="hidden" id="boylam" name="boylam">

            <button type="submit">Raporu Gönder</button>
        </form>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('report-map').setView([41.015137, 28.979530], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        
        var marker;
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;
            document.getElementById('enlem').value = lat;
            document.getElementById('boylam').value = lng;
            document.getElementById('coord-text').innerText = `Seçilen: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            if (marker) { marker.setLatLng(e.latlng); } else { marker = L.marker(e.latlng).addTo(map); }
        });
    </script>
</body>
</html>