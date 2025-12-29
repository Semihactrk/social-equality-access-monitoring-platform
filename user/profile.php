<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['rol'] ?? 'vatandas'; 
$status_msg = "";

// --- YENİ ÖZELLİK: ETKİLEŞİMLİ GIDA YARDIMI MANTIĞI ---
// 1. İŞLEM: PAKET ALMA
// --- YENİLENMİŞ GIDA YARDIMI MANTIĞI ---
if (isset($_POST['paket_al'])) {
    $ad_soyad = trim($_POST['ad_soyad']);
    $tc_no = trim($_POST['tc_no']);

    // 1. KONTROL: TC No 11 hane mi?
    if (!preg_match('/^[0-9]{11}$/', $tc_no)) {
        $status_msg = "Error: TC Identity Number must be exactly 11 digits!";
    } else {
        // 2. KONTROL: Bu TC son 30 gün içinde alım yapmış mı?
        $check_sql = "SELECT tarih FROM kullanici_hareketleri 
                      WHERE tc_no = ? 
                      AND islem_tipi = 'ALIM' 
                      AND tarih > DATE_SUB(NOW(), INTERVAL 30 DAY)
                      ORDER BY tarih DESC LIMIT 1";
        
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("s", $tc_no);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();

        if ($check_result->num_rows > 0) {
            // Son alım tarihini veritabanından al
            $row = $check_result->fetch_assoc();
            $son_alim = new DateTime($row['tarih']);
            $simdi = new DateTime();
            
            // 30 günlük sürenin dolacağı tarihi belirle
            $bitis_tarihi = clone $son_alim;
            $bitis_tarihi->modify('+30 days');
            
            // Kalan süreyi hesapla
            $fark = $simdi->diff($bitis_tarihi);
            $kalan_gun = $fark->days;

            // Hata mesajını dinamikleştir
            $status_msg = "<div style='color:#e74c3c; font-weight:bold;'>
                            ❌ Access Denied: You already claimed a package.<br>
                            You can claim again in <span style='font-size:1.2rem;'>$kalan_gun</span> days.
                        </div>";
        } else {
            // Kayıt yoksa alım işlemini gerçekleştir
            $conn->begin_transaction();
            try {
                // Stoğu güncelle
                $update = "UPDATE yardim_istatistikleri SET guncel_stok = guncel_stok - 1, toplam_dagitilan = toplam_dagitilan + 1 WHERE id = 1 AND guncel_stok > 0";
                $conn->query($update);

                if ($conn->affected_rows > 0) {
                    // Hareketi kaydet
                    $stmt_log = $conn->prepare("INSERT INTO kullanici_hareketleri (kullanici_id, islem_tipi, ad_soyad, tc_no) VALUES (?, 'ALIM', ?, ?)");
                    $stmt_log->bind_param("iss", $user_id, $ad_soyad, $tc_no);
                    $stmt_log->execute();

                    $conn->commit();
                    $status_msg = "✅ Success! Your package is reserved.";
                } else {
                    $conn->rollback();
                    $status_msg = "Sorry, out of stock!";
                }
            } catch (Exception $e) {
                $conn->rollback();
                $status_msg = "System error occurred.";
            }
        }
    }
}

// 2. İŞLEM: BAĞIŞ YAPMA
if (isset($_POST['bagis_yap'])) {
    $conn->begin_transaction();
    try {
        $update = "UPDATE yardim_istatistikleri SET guncel_stok = guncel_stok + 1 WHERE id = 1";
        $conn->query($update);

        $stmt_log = $conn->prepare("INSERT INTO kullanici_hareketleri (kullanici_id, islem_tipi) VALUES (?, 'BAGIS')");
        $stmt_log->bind_param("i", $user_id);
        $stmt_log->execute();
        
        $conn->commit();
        $status_msg = "Thank you for your donation!";
    } catch (Exception $e) { $conn->rollback(); }
}

// İSTATİSTİKLERİ VE HAREKETLERİ ÇEK
$stats_query = $conn->query("SELECT * FROM yardim_istatistikleri WHERE id = 1");
$stats = $stats_query->fetch_assoc();
$hedef = 100;
$yuzde = ($stats['toplam_dagitilan'] / $hedef) * 100;
if($yuzde > 100) $yuzde = 100;

$my_actions = $conn->prepare("SELECT islem_tipi, tarih FROM kullanici_hareketleri WHERE kullanici_id = ? ORDER BY tarih DESC LIMIT 5");
$my_actions->bind_param("i", $user_id);
$my_actions->execute();
$my_actions_res = $my_actions->get_result();
// --- ETKİLEŞİM MANTIĞI BİTİŞ ---


// Mevcut Rapor Sorgun 
$my_reports = [];
$sql = "SELECT id, baslik, aciklama, durum, olusturma_tarihi, guncelleme_tarihi 
        FROM raporlar 
        WHERE kullanici_id = ? 
        ORDER BY olusturma_tarihi DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $my_reports[] = $row;
    }
    $stmt->close();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - Social Equality Map</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <h2><?php echo ($user_role === 'admin') ? 'Admin Dashboard' : 'User Panel'; ?></h2>
            <nav>
                <a href="../index.php"><i class="fas fa-map-marked-alt"></i> Public Map</a>
                
                <?php if ($user_role === 'admin'): ?>
                    <a href="../admin/manage_reports.php"><i class="fas fa-tasks"></i> Manage All Reports</a>
                    <a href="../admin/manage_users.php"><i class="fas fa-users-cog"></i> Manage Users</a>
                <?php endif; ?>

                <a href="profile.php" class="active"><i class="fas fa-user"></i> My Profile / Reports</a>
                <a href="submit_report.php"><i class="fas fa-plus-circle"></i> Submit New Report</a>
                <a href="../forum/index.php"><i class="fas fa-comments"></i> Forum</a>
                
                <a href="../auth/logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['kullanici_adi']); ?>!</h1>
                <span class="role-badge" style="background: #2c3e50; color: white; padding: 5px 15px; border-radius: 4px;">
                    Role: <?php echo ($user_role === 'vatandas') ? 'Citizen' : ucfirst($user_role); ?>
                </span>
            </div>

            <?php if($status_msg): ?>
                <div style="padding:15px; background:#e8f5e9; color:#2e7d32; border-radius:8px; margin-bottom:20px; font-weight:bold;">
                    <i class="fas fa-check-circle"></i> <?php echo $status_msg; ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; margin-bottom: 40px;">
                    <div class="card" style="border-top: 5px solid #27ae60; padding: 25px;">
                        <h3 style="margin-top:0;"><i class="fas fa-hand-holding-heart"></i> Community Food Bank</h3>

                        <div style="background: #f0f7ff; border-left: 5px solid #3498db; margin-bottom: 20px; padding: 15px; font-size: 0.9rem;">
                            <?php
                            $last_check = $conn->prepare("SELECT tarih FROM kullanici_hareketleri WHERE kullanici_id = ? AND islem_tipi = 'ALIM' ORDER BY tarih DESC LIMIT 1");
                            $last_check->bind_param("i", $user_id); $last_check->execute();
                            $l_res = $last_check->get_result();
                            if ($l_res->num_rows > 0) {
                                $l_date = new DateTime($l_res->fetch_assoc()['tarih']);
                                $next_date = (clone $l_date)->modify('+30 days');
                                $simdi = new DateTime();
                                if ($simdi < $next_date) {
                                    echo "ℹ️ <strong>Next Eligible Date:</strong> " . $next_date->format('d M Y') . " (In " . $simdi->diff($next_date)->days . " days)";
                                } else { echo "✅ <strong>Status:</strong> Eligible for a new package."; }
                            } else { echo "✅ <strong>Status:</strong> Eligible for your first package."; }
                            ?>
                        </div>

                        <div style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Support our community by donating food packages.</p>
                            <form method="POST">
                                <button type="submit" name="bagis_yap" class="btn-action" 
                                        style="width:100%; background:#2980b9; color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold;">
                                    <i class="fas fa-heart"></i> Donate 1 Package
                                </button>
                            </form>
                        </div>

                        <div style="background: #f9f9f9; padding: 15px; border-radius: 8px;">
                            <h4 style="margin-top:0; font-size:0.9rem; color:#2c3e50;">Claim a Package</h4>
                            <form method="POST">
                                <input type="text" name="ad_soyad" placeholder="Full Name" required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:4px;">
                                <input type="text" name="tc_no" pattern="\d{11}" maxlength="11" placeholder="11-Digit TC No" required 
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '');" 
                                    style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:4px;">
                                
                                <button type="submit" name="paket_al" class="btn-action" 
                                        style="width:100%; background:#27ae60; color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold;" 
                                        <?php echo ($stats['guncel_stok'] <= 0) ? 'disabled style="background:#ccc;"' : ''; ?>>
                                    <i class="fas fa-box"></i> Claim (Stock: <?php echo $stats['guncel_stok']; ?>)
                                </button>
                            </form>
                        </div>

                        <div style="margin-top: 25px;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:0.85rem; font-weight:bold;">
                                <span>Community Goal Progress</span>
                                <span style="color:#27ae60;"><?php echo $stats['toplam_dagitilan']; ?> / 100</span>
                            </div>
                            <div style="background:#eee; height:15px; border-radius:10px; overflow:hidden;">
                                <div style="background:#27ae60; width:<?php echo $yuzde; ?>%; height:100%; transition: width 0.8s ease;"></div>
                            </div>
                        </div>
                    </div>
                

                <div class="card" style="padding: 20px; background: #fdfdfd;">
                    <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;"><i class="fas fa-stream"></i> My Activity</h4>
                    <ul style="list-style:none; padding:0; margin:0;">
                        <?php if($my_actions_res->num_rows > 0): ?>
                            <?php while($act = $my_actions_res->fetch_assoc()): ?>
                                <li style="padding:8px 0; border-bottom:1px solid #f1f1f1; font-size:0.8rem;">
                                    <strong><?php echo ($act['islem_tipi'] == 'ALIM') ? '📦 Claimed' : '❤️ Donated'; ?></strong>
                                    <span style="color:#999; float:right;"><?php echo date('H:i', strtotime($act['tarih'])); ?></span>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li style="color:#ccc; font-style:italic; font-size:0.85rem;">No recent activity.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <?php if ($user_role === 'admin'): ?>
                <div class="card" style="border-left: 5px solid #00bcd4; background: #f0fbfc; margin-bottom: 40px; padding: 25px;">
                    <h3 style="color: #2c3e50; margin-top: 0;"><i class="fas fa-user-shield"></i> Quick Admin Access</h3>
                    <p>You can manage users on the system and approve incoming reports.</p>
                    <div style="display: flex; gap: 15px; margin-top: 15px;">
                        <a href="../admin/manage_reports.php" class="btn-action">Go to Reports Overview</a>
                        <a href="../admin/manage_users.php" class="btn-action" style="background: #2c3e50; color: white !important;">Go to User Management</a>
                    </div>
                </div>
            <?php endif; ?>

            <h3><i class="fas fa-history"></i> My Submitted Reports</h3>
            
            <?php if (!empty($my_reports)): ?>
                <?php foreach ($my_reports as $report): ?>
                    <div class="card" style="margin-bottom: 20px; position: relative;">
                        <div style="position: absolute; top: 20px; right: 20px;">
                            <span class="badge process">
                                <?php 
                                    if ($report['durum'] == 'islemde') echo 'IN PROGRESS';
                                    elseif ($report['durum'] == 'cozuldu') echo 'RESOLVED';
                                    else echo 'PENDING';
                                ?>
                            </span>
                        </div>

                        <div style="font-size: 0.85rem; color: #888; margin-bottom: 10px;">
                            Submitted: <?php echo date('d M Y', strtotime($report['olusturma_tarihi'])); ?>
                            <br>
                            <span style="color: #00bcd4;">
                                Last Update: 
                                <?php 
                                if (!empty($report['guncelleme_tarihi']) && $report['guncelleme_tarihi'] != '0000-00-00 00:00:00') {
                                    echo date('d M Y, H:i', strtotime($report['guncelleme_tarihi']));
                                } else {
                                    echo "Waiting for review";
                                }
                                ?>
                            </span>
                        </div>

                        <h4 style="margin: 10px 0;"><?php echo htmlspecialchars($report['baslik']); ?></h4>
                        <p style="color: #666; font-size: 0.95rem;"><?php echo nl2br(htmlspecialchars($report['aciklama'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card" style="text-align: center; color: #999; padding: 40px;">
                    <p>Henüz bir rapor göndermediniz.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
<label style="font-size:0.8rem; color:#666;">TC Identity Number:</label>
<input type="text" 
       name="tc_no" 
       pattern="\d{11}" 
       title="TC Kimlik numarası tam olarak 11 rakamdan oluşmalıdır." 
       maxlength="11" 
       minlength="11" 
       required 
       oninput="this.value = this.value.replace(/[^0-9]/g, '');"
       style="width:100%; margin-bottom:15px; padding:8px; border:1px solid #ccc; border-radius:4px;">
</html>
<?php if (isset($conn)) { $conn->close(); } ?>