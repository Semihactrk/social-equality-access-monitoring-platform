<?php
// Session & Backend Logic - DO NOT TOUCH
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../includes/db_connect.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $baslik = trim($_POST['baslik']);
    $aciklama = trim($_POST['aciklama']);
    $enlem = $_POST['enlem'];
    $boylam = $_POST['boylam'];
    $sdg_kategori = isset($_POST['sdg_kategori']) ? $_POST['sdg_kategori'] : NULL; 
    $kullanici_id = $_SESSION['user_id'];
    
    $fotograf_yolu = NULL; 

    if (isset($_FILES['rapor_foto']) && $_FILES['rapor_foto']['error'] === 0) {
        $izin_verilen_uzantilar = ['jpg', 'jpeg', 'png', 'gif'];
        $dosya_adi = $_FILES['rapor_foto']['name'];
        $dosya_boyutu = $_FILES['rapor_foto']['size'];
        $dosya_gecici_yolu = $_FILES['rapor_foto']['tmp_name'];
        
        $dosya_uzantisi = strtolower(pathinfo($dosya_adi, PATHINFO_EXTENSION));

        if (!in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
            $errors[] = "Only JPG, JPEG, PNG and GIF files are allowed.";
        }
        elseif ($dosya_boyutu > 5000000) {
            $errors[] = "File size is too large (Max 5MB).";
        }
        else {
            $yeni_dosya_adi = "rapor_" . $kullanici_id . "_" . uniqid() . "." . $dosya_uzantisi;
            $fiziksel_hedef = "../uploads/" . $yeni_dosya_adi;
            $db_hedef = "uploads/" . $yeni_dosya_adi;

            if (move_uploaded_file($dosya_gecici_yolu, $fiziksel_hedef)) {
                $fotograf_yolu = $db_hedef;
            } else {
                $errors[] = "Error uploading file. Check folder permissions.";
            }
        }
    }

    if (empty($baslik) || empty($aciklama) || empty($enlem) || empty($boylam)) {
        $errors[] = "Title, description and location are required.";
    }

    if (empty($errors)) {
        $sql = "INSERT INTO raporlar (kullanici_id, baslik, aciklama, enlem, boylam, fotograf_yolu, sdg_kategori) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("issddss", $kullanici_id, $baslik, $aciklama, $enlem, $boylam, $fotograf_yolu, $sdg_kategori);

            if ($stmt->execute()) {
                header("Location: ../index.php?report_success=1");
                exit();
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Report - Social Equality Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        #report-map { height: 400px; width: 100%; border-radius: var(--radius); border: 1px solid var(--border-color); margin-bottom: 10px; }
        .location-status { display: none; padding: 10px; background-color: #d4edda; color: #155724; border-radius: var(--radius); margin-bottom: 15px; font-weight: bold; }
        .navbar-menu .btn { padding: 5px 15px; } 
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-globe-americas"></i> &nbsp; Social Equality Map
        </div>
        <div class="navbar-menu">
            <a href="../index.php">Home</a>
            <a href="../forum/index.php">Forum</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="../user/submit_report.php" class="btn btn-primary">Submit Report</a>
                <a href="../user/profile.php">My Profile</a>
                <a href="../auth/logout.php" style="color:var(--primary-red);">Logout</a>
            <?php else: ?>
                <a href="../auth/login.php">Login</a>
                <a href="../auth/register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div style="max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: var(--radius); border: 1px solid var(--border-color);">
            
            <h2 style="border-bottom: 2px solid var(--primary-blue); padding-bottom: 10px; margin-bottom: 20px;">Submit New Report</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <label style="font-size: 1.1rem;">1. Select Location on Map <span style="color:red">*</span></label>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 10px;">Click on the map to pinpoint the issue location.</p>
            
            <div id="report-map"></div>
            <div id="location-msg" class="location-status">Location Selected ✅</div>

            <form action="submit_report.php" method="post" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="baslik">Report Title <span style="color:red">*</span></label>
                    <input type="text" id="baslik" name="baslik" required placeholder="E.g., Broken Water Pipe in Kadikoy">
                </div>
                
                <div class="form-group">
                    <label for="sdg_kategori">Related SDG Goal</label>
                    <select name="sdg_kategori" id="sdg_kategori">
                        <option value="SDG-11: Sürdürülebilir Şehirler ve Topluluklar">SDG-11: Sustainable Cities</option>
                        <option value="SDG-10: Eşitsizliklerin Azaltılması">SDG-10: Reduced Inequalities</option>
                        <option value="SDG-6: Temiz Su ve Sanitasyon">SDG-6: Clean Water and Sanitation</option>
                        <option value="SDG-7: Erişilebilir ve Temiz Enerji">SDG-7: Affordable and Clean Energy</option>
                        <option value="SDG-3: Sağlık ve Kaliteli Yaşam">SDG-3: Good Health and Well-being</option>
                        <option value="Diğer">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="aciklama">Description <span style="color:red">*</span></label>
                    <textarea id="aciklama" name="aciklama" rows="5" required placeholder="Describe the issue in detail..."></textarea>
                </div>

                <div class="form-group">
                    <label for="rapor_foto">Upload Photo (Optional)</label>
                    <input type="file" id="rapor_foto" name="rapor_foto" accept="image/*">
                    <small style="color: var(--text-muted);">Supported: JPG, PNG. Max 5MB.</small>
                </div>

                <input type="hidden" id="enlem" name="enlem">
                <input type="hidden" id="boylam" name="boylam">

                <button type="submit" class="btn btn-primary btn-block" style="font-size: 1.1rem; padding: 15px;">Submit Report</button>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('report-map').setView([41.015137, 28.979530], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        
        var marker;
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;
            
            // Update hidden inputs
            document.getElementById('enlem').value = lat;
            document.getElementById('boylam').value = lng;
            
            // Show success message
            var msgDiv = document.getElementById('location-msg');
            msgDiv.style.display = 'block';
            msgDiv.innerHTML = `Location Selected ✅ (${lat.toFixed(4)}, ${lng.toFixed(4)})`;

            // Update marker
            if (marker) { marker.setLatLng(e.latlng); } 
            else { marker = L.marker(e.latlng).addTo(map); }
        });
    </script>
</body>
</html>