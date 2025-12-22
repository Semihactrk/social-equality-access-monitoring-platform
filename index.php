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
        
        .route-mode-controls {
            position: absolute;
            top: 150px;
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
        .btn-mode { padding: 8px 20px; font-size: 0.9rem; border-radius: 5px; background: #eee; color: #333; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-mode.active { background: #00AED9; color: white; }
        .btn-mode:hover { transform: translateY(-2px); box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .btn-mode i { margin-right: 5px; }
        
        .route-info-panel {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2500;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 20px;
            min-width: 400px;
            display: none;
        }
        .route-info-panel.show { display: block; }
        .route-info-header { font-size: 1.1rem; font-weight: bold; margin-bottom: 15px; color: #333; text-align: center; }
        .route-comparison { display: flex; gap: 20px; justify-content: space-around; }
        .route-mode-info { flex: 1; text-align: center; padding: 15px; border-radius: 8px; background: #f8f9fa; }
        .route-mode-info.active-mode { background: #e3f2fd; border: 2px solid #00AED9; }
        .route-mode-info h4 { margin: 0 0 10px 0; color: #00AED9; font-size: 1rem; }
        .route-mode-info .icon { font-size: 2rem; margin-bottom: 10px; }
        .route-mode-info .distance { font-size: 1.3rem; font-weight: bold; color: #333; margin: 5px 0; }
        .route-mode-info .time { font-size: 1.1rem; color: #666; }
        .route-mode-info .speed { font-size: 0.85rem; color: #999; margin-top: 5px; }
        
        /* Leaflet Routing Panel Styles */
        .leaflet-routing-container { 
            position: absolute;
            top: 220px;
            right: 10px;
            max-height: 400px; 
            max-width: 320px;
            overflow-y: auto; 
            z-index: 2000 !important;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 10px;
        }
        .leaflet-routing-container h2 {
            font-size: 1rem;
            margin: 0 0 10px 0;
            color: #333;
        }
        .leaflet-routing-container h3 {
            font-size: 0.9rem;
            margin: 10px 0 5px 0;
            color: #666;
        }
        .leaflet-routing-alt {
            background: white;
            padding: 8px;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 4px solid #00AED9;
        }
        .routing-panel-walk .leaflet-routing-alt {
            border-left-color: #27ae60;
        }
        .leaflet-routing-alt-minimized {
            display: none;
        }
        .leaflet-routing-collapse-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #00AED9;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 3px 8px;
            cursor: pointer;
        }
        .routing-panel-walk .leaflet-routing-collapse-btn {
            background: #27ae60;
        }
        
        .leaflet-marker-icon { cursor: pointer !important; }
        
        /* Hide the walking panel by default */
        .routing-panel-walk {
            display: none;
        }
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

    <div class="route-mode-controls">
        <button onclick="switchRouteMode('driving')" class="btn-mode active" id="btn-driving">
            <i class="fas fa-car"></i> By Car
        </button>
        <button onclick="switchRouteMode('walking')" class="btn-mode" id="btn-walking">
            <i class="fas fa-walking"></i> Walking
        </button>
    </div>

    <div class="route-info-panel" id="routeInfoPanel">
        <div class="route-info-header">Route Comparison</div>
        <div class="route-comparison">
            <div class="route-mode-info" id="carInfo">
                <div class="icon"><i class="fas fa-car"></i></div>
                <h4>By Car</h4>
                <div class="distance" id="carDistance">--</div>
                <div class="time" id="carTime">--</div>
                <div class="speed">Avg: <span id="carSpeed">--</span></div>
            </div>
            <div class="route-mode-info" id="walkInfo">
                <div class="icon"><i class="fas fa-walking"></i></div>
                <h4>Walking</h4>
                <div class="distance" id="walkDistance">--</div>
                <div class="time" id="walkTime">--</div>
                <div class="speed">Avg: <span id="walkSpeed">--</span></div>
            </div>
        </div>
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

        // Route mode and data storage
        var currentMode = 'driving';
        var routeData = {
            driving: null,
            walking: null
        };
        var currentWaypoints = [];
        var clickedPoints = []; // Track clicked points manually

        // 2. Rota Kontrolü - Driving (default)
        var control = L.Routing.control({
            waypoints: [],
            routeWhileDragging: false,
            addWaypoints: false,
            createMarker: function() { return null; },
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1',
                profile: 'car' // Use 'car' profile for driving
            }),
            lineOptions: {
                styles: [{color: '#00AED9', opacity: 0.7, weight: 6}]
            },
            show: true,
            collapsible: true,
            containerClassName: 'routing-panel-car'
        }).addTo(map);

        // Walking control (hidden by default)
        var walkingControl = L.Routing.control({
            waypoints: [],
            routeWhileDragging: false,
            addWaypoints: false,
            createMarker: function() { return null; },
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1',
                profile: 'foot' // Use 'foot' profile for walking - uses pedestrian paths
            }),
            lineOptions: {
                styles: [{color: '#27ae60', opacity: 0.7, weight: 6}]
            },
            show: true,
            collapsible: true,
            containerClassName: 'routing-panel-walk'
        });

        // Switch between route modes
        function switchRouteMode(mode) {
            currentMode = mode;
            
            // Update button states
            document.getElementById('btn-driving').classList.toggle('active', mode === 'driving');
            document.getElementById('btn-walking').classList.toggle('active', mode === 'walking');
            
            // Update active mode highlight in info panel
            document.getElementById('carInfo').classList.toggle('active-mode', mode === 'driving');
            document.getElementById('walkInfo').classList.toggle('active-mode', mode === 'walking');
            
            // Only switch if there are waypoints to show
            if (currentWaypoints.length === 0) {
                // No route to switch, just update the active control
                if (mode === 'driving') {
                    if (walkingControl._map) map.removeControl(walkingControl);
                    if (!control._map) control.addTo(map);
                } else {
                    if (control._map) map.removeControl(control);
                    if (!walkingControl._map) walkingControl.addTo(map);
                }
                return;
            }
            
            // Switch visible route and navigation panel
            if (mode === 'driving') {
                // Hide walking panel
                var walkPanel = document.querySelector('.routing-panel-walk');
                if (walkPanel) walkPanel.style.display = 'none';
                
                // Show car panel
                var carPanel = document.querySelector('.routing-panel-car');
                if (carPanel) carPanel.style.display = 'block';
                
                // Remove walking route from map
                if (walkingControl._map) {
                    map.removeControl(walkingControl);
                }
                // Add car route to map
                if (!control._map) {
                    control.addTo(map);
                }
                // Set waypoints
                control.setWaypoints(currentWaypoints);
            } else {
                // Hide car panel
                var carPanel = document.querySelector('.routing-panel-car');
                if (carPanel) carPanel.style.display = 'none';
                
                // Show walking panel
                var walkPanel = document.querySelector('.routing-panel-walk');
                if (walkPanel) walkPanel.style.display = 'block';
                
                // Remove car route from map
                if (control._map) {
                    map.removeControl(control);
                }
                // Add walking route to map
                if (!walkingControl._map) {
                    walkingControl.addTo(map);
                }
                // Set waypoints
                walkingControl.setWaypoints(currentWaypoints);
            }
        }

        // Manuel Temizleme Fonksiyonu
        function clearRoute() {
            // Reset data first
            currentWaypoints = [];
            clickedPoints = [];
            routeData.driving = null;
            routeData.walking = null;
            
            // Hide comparison panel
            document.getElementById('routeInfoPanel').classList.remove('show');
            
            // Remove both controls from map completely
            if (control._map) {
                map.removeControl(control);
            }
            if (walkingControl._map) {
                map.removeControl(walkingControl);
            }
            
            // Clear waypoints from both controls (even if not on map)
            try {
                control.setWaypoints([]);
            } catch(e) {}
            try {
                walkingControl.setWaypoints([]);
            } catch(e) {}
            
            // Re-add the active control with empty waypoints
            setTimeout(function() {
                if (currentMode === 'driving') {
                    if (!control._map) {
                        control.addTo(map);
                    }
                } else {
                    if (!walkingControl._map) {
                        walkingControl.addTo(map);
                    }
                }
            }, 50);
        }

        // Format time from seconds to readable format
        function formatTime(seconds) {
            var hours = Math.floor(seconds / 3600);
            var minutes = Math.floor((seconds % 3600) / 60);
            
            if (hours > 0) {
                return hours + 'h ' + minutes + 'min';
            }
            return minutes + ' min';
        }

        // Calculate and fetch both routes
        function calculateBothRoutes(waypoints) {
            if (waypoints.length < 2) return;
            
            currentWaypoints = waypoints;
            
            // Set waypoints for both controls
            control.setWaypoints(waypoints);
            walkingControl.setWaypoints(waypoints);
        }

        // Update route info display
        function updateRouteInfo() {
            if (routeData.driving || routeData.walking) {
                document.getElementById('routeInfoPanel').classList.add('show');
                
                // Update driving info
                if (routeData.driving) {
                    var drivingDist = (routeData.driving.distance / 1000).toFixed(2);
                    var drivingTime = routeData.driving.time;
                    var drivingSpeed = (routeData.driving.distance / routeData.driving.time * 3.6).toFixed(1);
                    
                    document.getElementById('carDistance').textContent = drivingDist + ' km';
                    document.getElementById('carTime').textContent = formatTime(drivingTime);
                    document.getElementById('carSpeed').textContent = drivingSpeed + ' km/h';
                }
                
                // Update walking info
                if (routeData.walking) {
                    var walkingDist = (routeData.walking.distance / 1000).toFixed(2);
                    var walkingTime = routeData.walking.time;
                    var walkingSpeed = (routeData.walking.distance / routeData.walking.time * 3.6).toFixed(1);
                    
                    document.getElementById('walkDistance').textContent = walkingDist + ' km';
                    document.getElementById('walkTime').textContent = formatTime(walkingTime);
                    document.getElementById('walkSpeed').textContent = walkingSpeed + ' km/h';
                }
            }
        }

        // Akıllı Tıklama Mantığı
        map.on('click', function (e) {
            // Eğer rota zaten tamamsa (2 nokta varsa), sıfırla ve yeni başlangıç yap
            if (clickedPoints.length >= 2) {
                clearRoute();
                clickedPoints = [e.latlng];
                calculateBothRoutes([e.latlng]);
            } 
            // İlk nokta
            else if (clickedPoints.length === 0) {
                clickedPoints = [e.latlng];
                calculateBothRoutes([e.latlng]);
            } 
            // İkinci nokta - hesapla
            else if (clickedPoints.length === 1) {
                clickedPoints.push(e.latlng);
                calculateBothRoutes([clickedPoints[0], clickedPoints[1]]);
            }
        });

        // Sağ Tık ile Temizleme
        map.on('contextmenu', function (e) {
            clearRoute();
        });

        // Listen for driving route found
        control.on('routesfound', function(e) {
            var summary = e.routes[0].summary;
            routeData.driving = {
                distance: summary.totalDistance,
                time: summary.totalTime
            };
            console.log("Driving - Distance: " + (summary.totalDistance / 1000).toFixed(2) + " km, Time: " + formatTime(summary.totalTime));
            updateRouteInfo();
        });

        // Listen for walking route found
        walkingControl.on('routesfound', function(e) {
            var summary = e.routes[0].summary;
            routeData.walking = {
                distance: summary.totalDistance,
                time: summary.totalTime
            };
            console.log("Walking - Distance: " + (summary.totalDistance / 1000).toFixed(2) + " km, Time: " + formatTime(summary.totalTime));
            updateRouteInfo();
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