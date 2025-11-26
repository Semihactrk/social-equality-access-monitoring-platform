<?php
session_start();
// Sadece giriş yapmış kullanıcılar yeni konu açabilir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Form gönderildiğinde çalışacak kod
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'includes/db_connect.php';

    $baslik = trim($_POST['baslik']);
    $kullanici_id = $_SESSION['user_id'];

    // Başlığın boş olmadığından emin ol
    if (!empty($baslik)) {
        $sql = "INSERT INTO forum_konulari (kullanici_id, baslik) VALUES (?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("is", $kullanici_id, $baslik);
            if ($stmt->execute()) {
                // Başarılı olursa forum ana sayfasına yönlendir
                header("Location: forum.php");
                exit();
            }
        }
    }
    // Hata durumunda (şimdilik basitçe) formu tekrar göster
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Konu Aç - Forum</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 50px auto; padding: 20px; background-color: #fff; border-radius: 8px; }
        textarea { width: 100%; height: 100px; padding: 10px; box-sizing: border-box; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; border-radius: 5px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Yeni Bir Tartışma Başlat</h2>
        <form action="new_topic.php" method="POST">
            <label for="baslik">Konu Başlığı:</label><br>
            <textarea name="baslik" id="baslik" required></textarea><br>
            <button type="submit">Konuyu Aç</button>
        </form>
        <p><a href="forum.php">&laquo; Foruma Geri Dön</a></p>
    </div>
</body>
</html>