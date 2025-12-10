<?php
session_start();
require_once '../includes/db_connect.php';

// URL'den konu ID'sini al, geçerli değilse foruma yönlendir
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: forum.php");
    exit();
}
$konu_id = $_GET['id'];

// --- YENİ YORUM GÖNDERİLDİYSE İŞLE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['yorum_metni'])) {
    // Yorum yapmak için giriş yapmış olmak zorunlu
    if (isset($_SESSION['user_id'])) {
        $yorum_metni = trim($_POST['yorum_metni']);
        $kullanici_id = $_SESSION['user_id'];

        if (!empty($yorum_metni)) {
            $sql_insert_yorum = "INSERT INTO yorumlar (konu_id, kullanici_id, yorum_metni) VALUES (?, ?, ?)";
            if ($stmt = $conn->prepare($sql_insert_yorum)) {
                $stmt->bind_param("iis", $konu_id, $kullanici_id, $yorum_metni);
                $stmt->execute();
                // Formu tekrar göndermeyi engellemek için aynı sayfaya yönlendir
                header("Location: topic.php?id=" . $konu_id);
                exit();
            }
        }
    }
}

// --- SAYFA İÇERİĞİNİ VERİTABANINDAN ÇEK ---

// 1. Konu başlığını çek
$sql_konu = "SELECT t.baslik, k.kullanici_adi FROM forum_konulari t JOIN kullanicilar k ON t.kullanici_id = k.id WHERE t.id = ?";
$konu = null;
if($stmt = $conn->prepare($sql_konu)) {
    $stmt->bind_param("i", $konu_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 1) {
        $konu = $result->fetch_assoc();
    } else {
        // Konu bulunamadıysa foruma yönlendir
        header("Location: forum.php");
        exit();
    }
}

// 2. Bu konuya ait tüm yorumları çek
$yorumlar = [];
$sql_yorumlar = "SELECT y.yorum_metni, y.olusturma_tarihi, k.kullanici_adi 
                 FROM yorumlar y JOIN kullanicilar k ON y.kullanici_id = k.id 
                 WHERE y.konu_id = ? ORDER BY y.olusturma_tarihi ASC";
if($stmt = $conn->prepare($sql_yorumlar)) {
    $stmt->bind_param("i", $konu_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $yorumlar[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($konu['baslik']); ?> - Forum</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { max-width: 900px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; }
        .topic-header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .comment { border-bottom: 1px solid #eee; padding: 15px 0; }
        .comment-meta { font-size: 0.9em; color: #555; margin-bottom: 5px; }
        .comment-body { line-height: 1.6; }
        .comment-form { margin-top: 30px; padding-top: 20px; border-top: 2px solid #333; }
        textarea { width: 100%; height: 100px; padding: 10px; box-sizing: border-box; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; border-radius: 5px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <p><a href="forum.php">&laquo; Tüm Konulara Geri Dön</a></p>

        <div class="topic-header">
            <h1><?php echo htmlspecialchars($konu['baslik']); ?></h1>
            <p><strong><?php echo htmlspecialchars($konu['kullanici_adi']); ?></strong> tarafından başlatıldı.</p>
        </div>

        <div class="comments-section">
            <?php if (!empty($yorumlar)): ?>
                <?php foreach ($yorumlar as $yorum): ?>
                    <div class="comment">
                        <div class="comment-meta">
                            <strong><?php echo htmlspecialchars($yorum['kullanici_adi']); ?></strong> yazdı / 
                            <span><?php echo date('d/m/Y H:i', strtotime($yorum['olusturma_tarihi'])); ?></span>
                        </div>
                        <div class="comment-body">
                            <?php echo nl2br(htmlspecialchars($yorum['yorum_metni'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Bu konuya henüz hiç yorum yapılmamış. İlk yorumu siz yapın!</p>
            <?php endif; ?>
        </div>
        
        <div class="comment-form">
            <?php if (isset($_SESSION['user_id'])): ?>
                <form action="topic.php?id=<?php echo $konu_id; ?>" method="POST">
                    <h3>Yorum Yap</h3>
                    <textarea name="yorum_metni" required></textarea><br>
                    <button type="submit">Yorumu Gönder</button>
                </form>
            <?php else: ?>
                <p>Yorum yapmak için <a href="login.php">giriş yapmanız</a> gerekmektedir.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>