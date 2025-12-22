<?php
session_start();
require_once '../includes/db_connect.php';

// SECURITY CHECK
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// ACTION: Status Change and Date Update
if (isset($_GET['durum_id']) && isset($_GET['yeni_durum'])) {
    $id = intval($_GET['durum_id']);
    $yeni_durum = $_GET['yeni_durum']; 
    
    // NEW PART: We also set guncelleme_tarihi (update date) to NOW() while changing the status
    $stmt = $conn->prepare("UPDATE raporlar SET durum = ?, guncelleme_tarihi = NOW() WHERE id = ?");
    $stmt->bind_param("si", $yeni_durum, $id);
    $stmt->execute();
    header("Location: manage_reports.php");
    exit();
}

// ACTION: Delete
if (isset($_GET['sil_id'])) {
    $id = intval($_GET['sil_id']);
    $stmt = $conn->prepare("DELETE FROM raporlar WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_reports.php");
    exit();
}

// FETCH DATA
$raporlar = [];
$sql = "SELECT r.*, k.kullanici_adi 
        FROM raporlar r 
        LEFT JOIN kullanicilar k ON r.kullanici_id = k.id 
        ORDER BY r.olusturma_tarihi DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) $raporlar[] = $row;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Rapor Yönetimi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style> 
        body { padding-top: 0; margin: 0; } 
        .btn-status { padding: 6px 12px; font-size: 0.8em; border-radius: 4px; text-decoration: none; display: inline-block; margin-right: 5px; font-weight: bold; color:white;}
        .btn-green { background: #28a745; }
        .btn-orange { background: #fd7e14; }
        .btn-red { color: #dc3545; background: none; font-size: 1.2em; border: 1px solid #dc3545; }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar" style="background: #2c3e50;">
            <h2>Admin Paneli</h2>
            <nav>
                <a href="../index.php">⬅️ Siteye Dön</a>
                <a href="index.php">📊 Genel Bakış</a>
                <a href="manage_users.php">👥 Kullanıcılar</a>
                <a href="manage_reports.php" class="active">🚩 Raporlar</a>
                <a href="../auth/logout.php" class="logout">Çıkış Yap</a>
            </nav>
        </aside>

        <main class="main-content">
            <h1 style="color: var(--primary);">Rapor Yönetimi</h1>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Görsel</th>
                        <th>Başlık</th>
                        <th>Gönderilme Tarihi</th>
                        <th>Mevcut Durum</th>
                        <th>İşlemler</th>
                        <th>Sil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($raporlar as $rapor): ?>
                        <?php $resim = $rapor['resim'] ?? $rapor['fotograf'] ?? ''; ?>
                    <tr>
                        <td>
                            <?php if($resim): ?>
                                <a href="../uploads/<?php echo $resim; ?>" target="_blank"><img src="../uploads/<?php echo $resim; ?>" width="50"></a>
                            <?php else: ?>
                                <span style="color:#ccc;">Yok</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($rapor['baslik'] ?? '-'); ?></strong><br>
                            <small>Gönderen: <?php echo htmlspecialchars($rapor['kullanici_adi'] ?? 'Anonim'); ?></small>
                        </td>
                        
                        <td style="font-size:0.9em; color:#666;">
                            <?php echo date("d.m.Y H:i", strtotime($rapor['olusturma_tarihi'])); ?>
                        </td>

                        <td>
                            <?php if($rapor['durum'] == 'gonderildi'): ?>
                                <span style="color:#007bff; font-weight:bold;">📨 Gönderildi</span>
                            <?php elseif($rapor['durum'] == 'beklemede'): ?>
                                <span style="color:#6610f2; font-weight:bold;">⏳ Beklemede</span>
                            <?php elseif($rapor['durum'] == 'isleme_alindi'): ?>
                                <span style="color:#fd7e14; font-weight:bold;">⚙️ İşleme Alındı</span>
                            <?php elseif($rapor['durum'] == 'cozuldu'): ?>
                                <span style="color:#28a745; font-weight:bold;">✅ Çözüldü</span>
                            <?php endif; ?>
                            
                            <?php if(!empty($rapor['guncelleme_tarihi'])): ?>
                                <br><small style="font-size:0.8em; color:#888;">
                                    Güncelleme: <?php echo date("d.m H:i", strtotime($rapor['guncelleme_tarihi'])); ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if($rapor['durum'] == 'gonderildi' || $rapor['durum'] == 'beklemede'): ?>
                                <a href="manage_reports.php?durum_id=<?php echo $rapor['id']; ?>&yeni_durum=isleme_alindi" class="btn-status btn-orange">İşleme Al ⚙️</a>
                            <?php elseif($rapor['durum'] == 'isleme_alindi'): ?>
                                <a href="manage_reports.php?durum_id=<?php echo $rapor['id']; ?>&yeni_durum=cozuldu" class="btn-status btn-green">Çözüldü Yap ✅</a>
                            <?php elseif($rapor['durum'] == 'cozuldu'): ?>
                                <a href="manage_reports.php?durum_id=<?php echo $rapor['id']; ?>&yeni_durum=isleme_alindi" class="btn-status btn-orange">↩️ Geri Al</a>
                            <?php endif; ?>
                        </td>

                        <td>
                            <a href="manage_reports.php?sil_id=<?php echo $rapor['id']; ?>" onclick="return confirm('Silmek istiyor musunuz?');" class="btn-status btn-red">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>