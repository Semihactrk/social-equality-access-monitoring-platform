<?php
// Session (oturum) yönetimi her zaman en üstte başlatılmalıdır!
session_start();

// Eğer kullanıcı zaten giriş yapmışsa, onu ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Gerekirse hataları görmek için
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db_connect.php';

$errors = [];

// Form gönderilmiş mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi_veya_email = trim($_POST['kullanici_adi_veya_email']);
    $sifre = $_POST['sifre'];

    if (empty($kullanici_adi_veya_email) || empty($sifre)) {
        $errors[] = "Tüm alanlar doldurulmalıdır.";
    } else {
        // Kullanıcıyı veritabanında ara (kullanıcı adı VEYA email ile)
        $sql = "SELECT id, kullanici_adi, sifre, rol FROM kullanicilar WHERE kullanici_adi = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        
        // --- DÜZELTİLEN SATIR BURASI ---
        // Sorguda 2 tane '?' olduğu için, 2 tane string ('ss') ve 2 tane değişken göndermeliyiz.
        $stmt->bind_param("ss", $kullanici_adi_veya_email, $kullanici_adi_veya_email);
        
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Gönderilen şifre ile veritabanındaki hash'lenmiş şifreyi doğrula
            if (password_verify($sifre, $user['sifre'])) {
                // Giriş Başarılı! Session bilgilerini ayarla
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['kullanici_adi'] = $user['kullanici_adi'];
                $_SESSION['rol'] = $user['rol'];
                
                // Kullanıcıyı ana sayfaya yönlendir
                header("Location: index.php");
                exit();

            } else {
                $errors[] = "Kullanıcı adı veya şifre hatalı.";
            }
        } else {
            $errors[] = "Kullanıcı adı veya şifre hatalı.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Şehrin Nabzı</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f4f4f4; line-height: 1.6; }
        .container { max-width: 400px; margin: 50px auto; padding: 20px 30px; border: 1px solid #ddd; border-radius: 8px; background-color: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .error { color: #D8000C; background-color: #FFD2D2; padding: 10px; margin: 10px 0; border-radius: 4px; border: 1px solid #D8000C; }
        .form-footer { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Giriş Yap</h2>


        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p style="margin: 0;"><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post" novalidate>
            <div class="form-group">
                <label for="kullanici_adi_veya_email">Kullanıcı Adı veya E-posta:</label>
                <input type="text" id="kullanici_adi_veya_email" name="kullanici_adi_veya_email" required>
            </div>
            <div class="form-group">
                <label for="sifre">Şifre:</label>
                <input type="password" id="sifre" name="sifre" required>
            </div>
            <button type="submit">Giriş Yap</button>
        </form>
        <div class="form-footer">
            <p>Hesabın yok mu? <a href="register.php">Hemen Kayıt Ol</a></p>
        </div>
    </div>
</body>
</html>