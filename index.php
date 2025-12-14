<?php
session_start();
require_once 'includes/db_connect.php';

// Fetch reports from database (Includes Photo and SDG data)
// We are filtering out 'resolved' (cozuldu) cases to keep the map clean, per logic.
$reports = [];
$sql = "SELECT baslik, aciklama, enlem, boylam, fotograf_yolu, sdg_kategori, durum FROM raporlar WHERE durum != 'cozuldu'"; 
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}
$conn->close();

$reports_json = json_encode($reports);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Social Equality Platform</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <nav class="navbar navbar-transparent">
        <div class="navbar-brand">
            <i class="fas fa-globe-americas"></i> Social Equality Map
        </div>
        
        <div class="mobile-toggle" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </div>

        <div class="navbar-menu" id="navbarMenu">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php">Home</a>
                <a href="user/profile.php">My Profile</a>
                <a href="forum/index.php">Forum</a>
                <a href="user/submit_report.php" class="btn btn-primary" style="padding: 5px 15px; color: white;">Submit Report</a>
                
                <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
                    <a href="admin/index.php" style="color: var(--primary-red);">Admin Panel</a>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'yetkili'): ?>
                    <a href="official/index.php" style="color: var(--primary-red);">Official Panel</a>
                <?php endif; ?>

                <a href="auth/logout.php">Logout</a>

            <?php else: ?>
                <a href="index.php">Home</a>
                <a href="forum/index.php">Forum</a>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php" class="btn btn-primary" style="padding: 5px 15px; color: white;">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <?php
    if (isset($_GET['report_success']) && $_GET['report_success'] == '1') {
        echo '<div style="position: absolute; top: 80px; left: 50%; transform: translateX(-50%); z-index: 2000;" class="alert alert-success fade-in">
                Report and photo submitted successfully!
              </div>';
    }
    ?>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Initialize Map
        // Coordinates: Istanbul (Default) - Change if needed
        var map = L.map('map', { zoomControl: false }).setView([41.015137, 28.979530], 12);

        // Add Zoom Control to Top Right (Square buttons per request)
        L.control.zoom({
            position: 'topright'
        }).addTo(map);

        // Tile Layer (OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Load PHP Data
        var reportsData = <?php echo $reports_json; ?>;

        // Loop through reports and add markers
        reportsData.forEach(function(report) {
            var marker = L.marker([report.enlem, report.boylam]).addTo(map);
            
            // Prepare Popup Content (English)
            var popupContent = `<div style="text-align:left;">`;
            
            // 1. SDG Tag
            if (report.sdg_kategori) {
                popupContent += `<span class='sdg-tag'>${report.sdg_kategori}</span><br>`;
            }

            // 2. Title & Description
            popupContent += `<h3 style="margin: 10px 0 5px 0; font-size:16px;">${report.baslik}</h3>`;
            popupContent += `<p style="margin:0; color:#666;">${report.aciklama}</p>`;

            // 3. Image (if exists)
            if (report.fotograf_yolu) {
                popupContent += `<img src='${report.fotograf_yolu}' class='popup-img' alt='Report Image'>`;
            }

            // 4. Status
            // Translate status for UI display
            let statusText = report.durum;
            if(statusText === 'beklemede') statusText = 'Pending';
            if(statusText === 'islemde') statusText = 'In Progress';
            
            popupContent += `<div style="margin-top:10px; font-size:0.85rem;"><strong>Status:</strong> ${statusText}</div>`;
            popupContent += `</div>`;

            marker.bindPopup(popupContent);
        });

        // Mobile Menu Toggle Script
        function toggleMenu() {
            var menu = document.getElementById("navbarMenu");
            menu.classList.toggle("active");
        }
    </script>
</body>
</html>
