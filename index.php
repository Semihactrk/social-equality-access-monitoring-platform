<?php
session_start();
require_once 'includes/db_connect.php';

// Filter State Logic
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
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    
    <style>
        body, html { height: 100%; overflow: hidden; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-globe-americas"></i> &nbsp; Social Equality Map
        </div>
        <div class="navbar-menu">
            <a href="index.php" class="active">Home</a>
            <a href="forum/index.php">Forum</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="user/submit_report.php" class="btn btn-primary">Submit Report</a>
                <a href="user/profile.php">My Profile</a>
                <a href="auth/logout.php" style="color:var(--primary-red);">Logout</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn btn-primary">Login / Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="map-floating-panel panel-top">
        <a href="?filter=active" class="btn-filter <?php echo ($filter == 'active' ? 'active' : ''); ?>">Active Issues</a>
        <a href="?filter=resolved" class="btn-filter <?php echo ($filter == 'resolved' ? 'active' : ''); ?>">Resolved</a>
        <a href="?filter=all" class="btn-filter <?php echo ($filter == 'all' ? 'active' : ''); ?>">All</a>
        <button onclick="clearRoute()" class="btn-filter btn-clear"><i class="fas fa-trash"></i> Clear Route</button>
    </div>

    <div class="map-floating-panel panel-middle">
        <button onclick="switchRouteMode('driving')" class="btn-mode active" id="btn-driving">
            <i class="fas fa-car"></i> By Car
        </button>
        <button onclick="switchRouteMode('walking')" class="btn-mode" id="btn-walking">
            <i class="fas fa-walking"></i> Walking
        </button>
    </div>

    <div class="route-info-panel" id="routeInfoPanel">
        <div class="text-center" style="font-weight:bold; margin-bottom:10px;">Route Comparison</div>
        <div class="route-comparison">
            <div class="route-mode-box active" id="carInfo">
                <i class="fas fa-car fa-2x" style="color:var(--primary-blue)"></i>
                <h4>By Car</h4>
                <div style="font-size:1.2rem; font-weight:bold" id="carDistance">--</div>
                <div style="color:#666" id="carTime">--</div>
            </div>
            <div class="route-mode-box" id="walkInfo">
                <i class="fas fa-walking fa-2x" style="color:var(--primary-green)"></i>
                <h4>Walking</h4>
                <div style="font-size:1.2rem; font-weight:bold" id="walkDistance">--</div>
                <div style="color:#666" id="walkTime">--</div>
            </div>
        </div>
    </div>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

    <script>
        // Harita ayarları
        var map = L.map('map', { zoomControl: false }).setView([41.015137, 28.979530], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        L.control.zoom({ position: 'topright' }).addTo(map);

        // Değişkenler
        var currentMode = 'driving';
        var routeData = { driving: null, walking: null };
        var currentWaypoints = [];
        var clickedPoints = []; 

        // Araba rotası için kontrol
        var control = L.Routing.control({
            waypoints: [], 
            routeWhileDragging: false, 
            addWaypoints: false,
            createMarker: function() { return null; },
            router: L.Routing.osrmv1({ 
                serviceUrl: 'https://router.project-osrm.org/route/v1', 
                profile: 'car' 
            }),
            lineOptions: { styles: [{color: '#00AED9', opacity: 0.7, weight: 6}] },
            show: true, 
            collapsible: true, 
            containerClassName: 'routing-panel-car'
        }).addTo(map);

        // Yürüyüş rotası için kontrol
        var walkingControl = L.Routing.control({
            waypoints: [], 
            routeWhileDragging: false, 
            addWaypoints: false,
            createMarker: function() { return null; },
            router: L.Routing.osrmv1({ 
                serviceUrl: 'https://router.project-osrm.org/route/v1', 
                profile: 'foot' 
            }),
            lineOptions: { styles: [{color: '#27ae60', opacity: 0.7, weight: 6}] },
            show: true, 
            collapsible: true, 
            containerClassName: 'routing-panel-walk'
        });

        function switchRouteMode(mode) {
            currentMode = mode;
            
            // Buton stillerini güncelle
            document.getElementById('btn-driving').classList.toggle('active', mode === 'driving');
            document.getElementById('btn-walking').classList.toggle('active', mode === 'walking');
            document.getElementById('carInfo').classList.toggle('active', mode === 'driving');
            document.getElementById('walkInfo').classList.toggle('active', mode === 'walking');
            
            // Eğer henüz rota yoksa sadece kontrolleri değiştir
            if (currentWaypoints.length === 0) {
                if (mode === 'driving') {
                    if (walkingControl._map) map.removeControl(walkingControl);
                    if (!control._map) control.addTo(map);
                } else {
                    if (control._map) map.removeControl(control);
                    if (!walkingControl._map) walkingControl.addTo(map);
                }
                return;
            }
            
            // Rota varsa, seçilen moda göre haritada göster
            if (mode === 'driving') {
                if (walkingControl._map) map.removeControl(walkingControl);
                if (!control._map) control.addTo(map);
                control.setWaypoints(currentWaypoints);
            } else {
                if (control._map) map.removeControl(control);
                if (!walkingControl._map) walkingControl.addTo(map);
                walkingControl.setWaypoints(currentWaypoints);
            }
        }

        function clearRoute() {
            // Tüm verileri sıfırla
            currentWaypoints = []; 
            clickedPoints = []; 
            routeData = {driving:null, walking:null};
            
            // Bilgi panelini gizle
            document.getElementById('routeInfoPanel').classList.remove('show');
            document.getElementById('carDistance').textContent = '--';
            document.getElementById('carTime').textContent = '--';
            document.getElementById('walkDistance').textContent = '--';
            document.getElementById('walkTime').textContent = '--';
            
            // Haritadaki rotaları temizle
            try { 
                control.setWaypoints([]); 
                walkingControl.setWaypoints([]);
            } catch(e){}
        }

        function formatTime(seconds) {
            var m = Math.floor(seconds / 60);
            var h = Math.floor(m / 60);
            m = m % 60;
            return h > 0 ? h + 'h ' + m + 'm' : m + ' min';
        }

        function getRouteData(waypoints, profile, callback) {
            if (waypoints.length < 2) return;
            
            // Koordinatları API formatına çevir
            var coords = waypoints.map(function(wp) {
                return wp.lng + ',' + wp.lat;
            }).join(';');
            
            var url = 'https://router.project-osrm.org/route/v1/' + profile + '/' + coords + '?overview=false';
            
            fetch(url)
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                        var route = data.routes[0];
                        callback({
                            distance: route.distance,
                            time: route.duration
                        });
                    }
                })
                .catch(function(error) {
                    console.error('Rota hesaplama hatası:', error);
                });
        }

        function calculateBothRoutes(waypoints) {
            if (waypoints.length < 2) return;
            currentWaypoints = waypoints;
            routeData = {driving: null, walking: null};
            
            // Araba rotası hesapla
            getRouteData(waypoints, 'driving', function(result) {
                routeData.driving = result;
                
                // Yürüyüş süresini mesafeye göre hesapla
                var walkSpeed = 1.39;
                var walkTime = result.distance / walkSpeed;
                
                routeData.walking = {
                    distance: result.distance,
                    time: walkTime
                };
                
                updateRouteInfo();
            });
            
            // Seçili moda göre haritada göster
            if (currentMode === 'driving') {
                control.setWaypoints(waypoints);
            } else {
                walkingControl.setWaypoints(waypoints);
            }
        }

        function updateRouteInfo() {
            if (routeData.driving || routeData.walking) {
                document.getElementById('routeInfoPanel').classList.add('show');
                
                if (routeData.driving) {
                    var carDist = (routeData.driving.distance / 1000).toFixed(1) + ' km';
                    var carTime = formatTime(routeData.driving.time);
                    document.getElementById('carDistance').textContent = carDist;
                    document.getElementById('carTime').textContent = carTime;
                }
                
                if (routeData.walking) {
                    var walkDist = (routeData.walking.distance / 1000).toFixed(1) + ' km';
                    var walkTime = formatTime(routeData.walking.time);
                    document.getElementById('walkDistance').textContent = walkDist;
                    document.getElementById('walkTime').textContent = walkTime;
                }
            }
        }

        // Haritaya tıklanınca rota çiz
        map.on('click', function (e) {
            if (clickedPoints.length >= 2) { 
                clearRoute(); 
                clickedPoints = [e.latlng]; 
            } else { 
                clickedPoints.push(e.latlng); 
            }
            
            if (clickedPoints.length > 0) {
                var points = clickedPoints.length == 1 ? [clickedPoints[0]] : clickedPoints;
                calculateBothRoutes(points);
            }
        });
        
        // Sağ tıklanınca rotayı temizle
        map.on('contextmenu', function (e) { 
            clearRoute(); 
        });

        // Raporları haritaya ekle
        var reportsData = <?php echo $reports_json; ?>;
        reportsData.forEach(function(report) {
            if(report.enlem && report.boylam) {
                // Duruma göre marker rengi belirle
                var iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png';
                if(report.durum === 'cozuldu') {
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png';
                }
                if(report.durum === 'isleme_alindi') {
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png';
                }
                
                var marker = L.marker([report.enlem, report.boylam], { 
                    icon: L.icon({
                        iconUrl: iconUrl, 
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png', 
                        iconSize: [25, 41], 
                        iconAnchor: [12, 41], 
                        popupAnchor: [1, -34], 
                        shadowSize: [41, 41]
                    })
                }).addTo(map);

                var popup = `<div style="min-width:180px">
                    <span class="badge" style="font-size:10px; padding:2px 6px; background:#00AED9">${report.sdg_kategori || 'General'}</span>
                    <h4 style="margin:5px 0"><a href="report_details.php?id=${report.id}" style="color:#333">${report.baslik}</a></h4>
                    <p style="font-size:12px; color:#666">${report.aciklama.substring(0,50)}...</p>
                    <a href="report_details.php?id=${report.id}" class="btn btn-primary" style="padding:4px 10px; font-size:11px; display:inline-block; margin-top:5px">View Details</a>
                </div>`;
                marker.bindPopup(popup);
            }
        });
    </script>
</body>
</html>