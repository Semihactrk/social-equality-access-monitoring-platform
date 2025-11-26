<?php
session_start();
require_once 'includes/db_connect.php';

// Tüm forum konularını, açan kullanıcının adıyla birlikte çek
$konular = [];
$sql = "SELECT t.id, t.baslik, t.olusturma_tarihi, k.kullanici_adi 
        FROM forum_konulari t
        JOIN kullanicilar k ON t.kullanici_id = k.id
        ORDER BY t.olusturma_tarihi DESC";

$result = $conn->query($sql);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $konular[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum - Şehrin Nabzı</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .navbar { background-color: #333; color: white; padding: 15px; text-align: right; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; }
        .container { max-width: 900px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .new-topic-btn { display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php">Ana Sayfa</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php">Profilim</a>
            <a href="logout.php">Çıkış Yap</a>
        <?php else: ?>
            <a href="login.php">Giriş Yap</a>
            <a href="register.php">Kayıt Ol</a>
        <?php endif; ?>
    </div>

    <div class="container">
        <h1>Forum Tartışma Başlıkları</h1>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="new_topic.php" class="new-topic-btn">Yeni Konu Aç</a>
        <?php else: ?>
            <p>Yeni konu açmak veya yorum yapmak için <a href="login.php">giriş yapmalısınız</a>.</p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Başlık</th>
                    <th>Açan Kullanıcı</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($konular)): ?>
                    <?php foreach ($konular as $konu): ?>
                        <tr>
                            <td><a href="topic.php?id=<?php echo $konu['id']; ?>"><?php echo htmlspecialchars($konu['baslik']); ?></a></td>
                            <td><?php echo htmlspecialchars($konu['kullanici_adi']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($konu['olusturma_tarihi'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">Henüz hiç konu açılmamış.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>