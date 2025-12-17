<?php
session_start();
require_once 'includes/db_connect.php';

// Filtre durumunu alıyoruz. Varsayılan olarak sadece 'aktif' raporlar.
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'active'; 

$reports = [];
$sql = "SELECT id, baslik, aciklama, enlem, boylam, fotograf_yolu, sdg_kategori, durum FROM raporlar"; 

if ($filter === 'active') {
    $sql .= " WHERE durum != 'cozuldu'";
} elseif ($filter === 'resolved') {
    $sql .= " WHERE durum = 'cozuldu'";
}

$sql .= " ORDER BY id DESC";
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body, html { height: 100%; margin: 0; padding: 0; overflow: hidden; }
        #map { width: 100%; height: 100vh; z-index: 1; }
        
        .report-filter-controls {
            position: absolute;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2500;
            display: flex;
            gap: 10px;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .btn-filter { padding: 6px 15px; font-size: 0.85rem; text-decoration: none; border-radius: 5px; background: #eee; color: #333; cursor: pointer; border: none; }
        .btn-filter.active { background: #00AED9; color: white; }
        .btn-clear { background: #e74c3c; color: white; }
        .btn-clear:hover { background: #c0392b; }
        
        .leaflet-routing-container { max-height: 150px; overflow-y: auto; z-index: 1000 !important; }
        .leaflet-marker-icon { cursor: pointer !important; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand"><i class="fas fa-globe-americas"></i> Social Equality Map</div>
        <div class="mobile-toggle" onclick="toggleMenu()"><i class="fas fa-bars"></i></div>
        <div class="navbar-menu" id="navbarMenu">
            <a href="index.php">Home</a>
            <a href="forum/index.php">Forum</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="user/profile.php">My Profile</a>
                <a href="user/submit_report.php" class="btn btn-primary" style="color:white;">Submit Report</a>
                <a href="auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php" class="btn btn-primary" style="color:white;">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="report-filter-controls">
        <a href="?filter=active" class="btn btn-filter <?php echo ($filter == 'active' ? 'active' : ''); ?>">Active</a>
        <a href="?filter=resolved" class="btn btn-filter <?php echo ($filter == 'resolved' ? 'active' : ''); ?>">Resolved</a>
        <a href="?filter=all" class="btn btn-filter <?php echo ($filter == 'all' ? 'active' : ''); ?>">All</a>
        <button onclick="clearRoute()" class="btn btn-filter btn-clear"><i class="fas fa-trash"></i> Clear Route</button>
    </div>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

    <script>
        // 1. Haritayı Başlat
        var map = L.map('map', { zoomControl: false }).setView([41.015137, 28.979530], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        L.control.zoom({ position: 'topright' }).addTo(map);

        // 2. Rota Kontrolü
        var control = L.Routing.control({
            waypoints: [null],
            routeWhileDragging: false,
            addWaypoints: false,
            createMarker: function() { return null; },
            lineOptions: {
                styles: [{color: '#00AED9', opacity: 0.7, weight: 6}]
            }
        }).addTo(map);

        // Manuel Temizleme Fonksiyonu
        function clearRoute() {
            control.setWaypoints([]);
        }

        // Akıllı Tıklama Mantığı
        map.on('click', function (e) {
            var container = control.getPlan().getWaypoints();
            
            // Eğer rota zaten tamamsa (2 nokta varsa), sıfırla ve yeni başlangıç yap
            if (container[0] && container[0].latLng && container[1] && container[1].latLng) {
                control.setWaypoints([e.latlng]);
            } 
            // İlk nokta yoksa koy
            else if (!container[0] || !container[0].latLng) {
                control.spliceWaypoints(0, 1, e.latlng);
            } 
            // İkinci noktayı koy
            else {
                control.spliceWaypoints(container.length - 1, 1, e.latlng);
            }
        });

        // Sağ Tık ile Temizleme
        map.on('contextmenu', function (e) {
            clearRoute();
        });

        // Mesafe Hesaplama Takibi (Hocaya göstermek için)
        control.on('routesfound', function(e) {
            var summary = e.routes[0].summary;
            console.log("Mesafe: " + (summary.totalDistance / 1000).toFixed(2) + " km");
        });

        // 3. Veritabanı Markerları
        var reportsData = <?php echo $reports_json; ?>;

        reportsData.forEach(function(report) {
            if(report.enlem && report.boylam) {
                var iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png';
                if(report.durum === 'cozuldu') iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png';
                if(report.durum === 'islemde') iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png';

                var customIcon = L.icon({
                    iconUrl: iconUrl,
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
                    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
                });

                var marker = L.marker([report.enlem, report.boylam], { 
                    icon: customIcon,
                    zIndexOffset: 1000 
                }).addTo(map);

                var popupContent = `<div style="text-align:left; min-width:150px;">`;
                if (report.sdg_kategori) {
                    popupContent += `<span class='sdg-tag' style="background:#00AED9; color:white; padding:2px 5px; font-size:10px; border-radius:3px;">${report.sdg_kategori}</span><br>`;
                }
                popupContent += `<h3 style="margin: 10px 0 5px 0; font-size:14px;">
                                    <a href="report_details.php?id=${report.id}" style="color:#00AED9; text-decoration:none; font-weight:bold;">${report.baslik}</a>
                                 </h3>`;
                popupContent += `<p style="margin:0; font-size:12px; color:#666;">${report.aciklama}</p>`;
                if (report.fotograf_yolu) {
                    var imgPath = report.fotograf_yolu.replace('admin/uploads/', 'uploads/');
                    popupContent += `<img src='${imgPath}' style="width:100%; margin-top:10px; border-radius:5px;">`;
                }
                popupContent += `<div style="margin-top:10px; font-size:11px;"><strong>Status:</strong> ${report.durum}</div></div>`;

                marker.bindPopup(popupContent);
                marker.on('click', function(e) {
                    L.DomEvent.stopPropagation(e);
                });
            }
        });

        function toggleMenu() {
            var menu = document.getElementById("navbarMenu");
            menu.classList.toggle("active");
        }
    </script>
</body>
</html>