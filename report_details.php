<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$rapor_id = intval($_GET['id']);
$message = "";

// Comment Submission Process
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['yorum'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: auth/login.php");
        exit();
    }
    $yorum = trim($_POST['yorum']);
    if (!empty($yorum)) {
        $stmt = $conn->prepare("INSERT INTO yorumlar (rapor_id, kullanici_id, yorum) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $rapor_id, $_SESSION['user_id'], $yorum);
        $stmt->execute();
        $message = "Yorumunuz eklendi! ✅";
    }
}

// Fetch Report Information
$sql = "SELECT r.*, k.kullanici_adi FROM raporlar r LEFT JOIN kullanicilar k ON r.kullanici_id = k.id WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $rapor_id);
$stmt->execute();
$rapor = $stmt->get_result()->fetch_assoc();

if (!$rapor) { echo "Rapor bulunamadı."; exit(); }

// Fetch Comments
$yorumlar_sql = "SELECT y.*, k.kullanici_adi FROM yorumlar y LEFT JOIN kullanicilar k ON y.kullanici_id = k.id WHERE y.rapor_id = ? ORDER BY y.tarih DESC";
$stmt2 = $conn->prepare($yorumlar_sql);
$stmt2->bind_param("i", $rapor_id);
$stmt2->execute();
$yorumlar = $stmt2->get_result();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($rapor['baslik']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <h2>Şehrin Nabzı</h2>
            <nav>
                <a href="index.php">⬅️ Haritaya Dön</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="user/profile.php">👤 Profilim</a>
                <?php else: ?>
                    <a href="auth/login.php">Giriş Yap</a>
                <?php endif; ?>
            </nav>
        </aside>

        <main class="main-content">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h1 style="color:var(--primary); margin:0;"><?php echo htmlspecialchars($rapor['baslik']); ?></h1>
                    
                    <?php 
                        $renk = 'red'; $text = 'Bekliyor';
                        if($rapor['durum'] == 'cozuldu') { $renk = 'green'; $text = '✅ Çözüldü'; }
                        elseif($rapor['durum'] == 'isleme_alindi') { $renk = 'orange'; $text = '⚙️ İşleme Alındı'; }
                        elseif($rapor['durum'] == 'gonderildi') { $renk = 'blue'; $text = '📨 Gönderildi'; }
                    ?>
                    <span style="background:<?php echo $renk; ?>; color:white; padding:5px 10px; border-radius:15px; font-size:0.9em; font-weight:bold;">
                        <?php echo $text; ?>
                    </span>
                </div>

                <p style="color:#666; margin-top:5px;">
                    Gönderen: <strong><?php echo htmlspecialchars($rapor['kullanici_adi'] ?? 'Anonim'); ?></strong> | 
                    Tarih: <?php echo date("d.m.Y H:i", strtotime($rapor['olusturma_tarihi'])); ?>
                </p>

                <p style="font-size:1.1em; line-height:1.6;"><?php echo nl2br(htmlspecialchars($rapor['aciklama'])); ?></p>

                <?php if ($rapor['resim']): ?>
                    <img src="uploads/<?php echo $rapor['resim']; ?>" style="max-width:100%; border-radius:8px; margin-top:10px;">
                <?php endif; ?>
            </div>

            <div style="margin-top: 30px;">
                <h3>💬 Yorumlar</h3>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="post" style="background:#f9f9f9; padding:15px; border-radius:8px; margin-bottom:20px;">
                        <?php if($message) echo "<p style='color:green; font-weight:bold;'>$message</p>"; ?>
                        <textarea name="yorum" rows="3" class="form-control" placeholder="Bu konu hakkında bir yorum yaz..." required></textarea>
                        <button type="submit" class="btn btn-primary" style="margin-top:10px;">Yorum Gönder</button>
                    </form>
                <?php else: ?>
                    <p><a href="auth/login.php" style="color:var(--primary); font-weight:bold;">Giriş yaparak</a> yorum yazabilirsiniz.</p>
                <?php endif; ?>

                <?php while($yorum = $yorumlar->fetch_assoc()): ?>
                    <div style="border-bottom:1px solid #eee; padding: 15px 0;">
                        <strong><?php echo htmlspecialchars($yorum['kullanici_adi']); ?></strong>
                        <small style="color:#999; margin-left:10px;"><?php echo date("d.m.Y H:i", strtotime($yorum['tarih'])); ?></small>
                        <p style="margin:5px 0;"><?php echo nl2br(htmlspecialchars($yorum['yorum'])); ?></p>
                    </div>
                <?php endwhile; ?>
                
                <?php if($yorumlar->num_rows == 0): ?>
                    <p style="color:#999;">Henüz yorum yapılmamış. İlk yorumu sen yap! 🚀</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>