<?php
// Oturumu başlat
session_start();

// Kullanıcı giriş yapmış mı diye kontrol et
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantımızı dahil edelim
require_once 'includes/db_connect.php';

$errors = [];
$success_message = '';

// Form gönderilmiş mi diye kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $baslik = trim($_POST['baslik']);
    $aciklama = trim($_POST['aciklama']);
    $enlem = $_POST['enlem'];
    $boylam = $_POST['boylam'];
    $kullanici_id = $_SESSION['user_id']; // Raporu gönderen kullanıcının ID'si

    // Basit doğrulama
    if (empty($baslik) || empty($aciklama) || empty($enlem) || empty($boylam)) {
        $errors[] = "Tüm alanlar doldurulmalıdır ve haritadan bir konum seçilmelidir.";
    }

    // Hata yoksa veritabanına kaydet
    if (empty($errors)) {
        $sql = "INSERT INTO raporlar (kullanici_id, baslik, aciklama, enlem, boylam) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            // Parametreleri bağla
            // i: integer (kullanici_id)
            // s: string (baslik, aciklama)
            // d: double (enlem, boylam)
            $stmt->bind_param("issdd", $kullanici_id, $baslik, $aciklama, $enlem, $boylam);

            // Sorguyu çalıştır
            if ($stmt->execute()) {
                // Başarılı olursa ana sayfaya yönlendir
                header("Location: index.php?report_success=1");
                exit();
            } else {
                $errors[] = "Rapor gönderilirken bir hata oluştu. Lütfen tekrar deneyin.";
            }
            $stmt->close();
        } else {
             $errors[] = "Sorgu hazırlanırken bir hata oluştu.";
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
        input[type="text"], textarea { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
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
        <p>Lütfen sorunu harita üzerinde işaretleyin ve aşağıdaki formu doldurun.</p>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p id="coord-text" class="coord-info">Konum seçmek için haritaya tıklayın.</p>
        
        <div id="report-map"></div>

        <form action="submit_report.php" method="post">
            <div class="form-group">
                <label for="baslik">Rapor Başlığı:</label>
                <input type="text" id="baslik" name="baslik" placeholder="Örn: Bozuk Kaldırım Taşı" required>
            </div>
            <div class="form-group">
                <label for="aciklama">Açıklama:</label>
                <textarea id="aciklama" name="aciklama" placeholder="Sorunla ilgili detayları buraya yazın." required></textarea>
            </div>

            <input type="hidden" id="enlem" name="enlem">
            <input type="hidden" id="boylam" name="boylam">

            <button type="submit">Raporu Gönder</button>
        </form>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('report-map').setView([41.015137, 28.979530], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var marker;
        
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            document.getElementById('enlem').value = lat;
            document.getElementById('boylam').value = lng;
            
            document.getElementById('coord-text').innerText = `Seçilen Koordinatlar: Enlem=${lat.toFixed(6)}, Boylam=${lng.toFixed(6)}`;

            if (!marker) {
                marker = L.marker(e.latlng).addTo(map);
            } else {
                marker.setLatLng(e.latlng);
            }
        });
    </script>
</body>
</html>