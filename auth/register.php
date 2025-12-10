<?php
// Gerekirse hataları görmek için bu bölümü projenin sonunda silebilirsiniz.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Hata ve başarı mesajları için değişkenler
$errors = [];
$success_message = '';

// Form sadece POST metodu ile gönderildiğinde bu blok çalışır.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require_once 'includes/db_connect.php';

    // Form verilerini al
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $email = trim($_POST['email']);
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];

    // Doğrulama (Validation)
    if (empty($kullanici_adi)) { $errors[] = "Kullanıcı adı boş bırakılamaz."; }
    if (empty($email)) { $errors[] = "E-posta boş bırakılamaz."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Geçerli bir e-posta adresi giriniz."; }
    if (empty($sifre)) { $errors[] = "Şifre boş bırakılamaz."; }
    if (strlen($sifre) < 6) { $errors[] = "Şifre en az 6 karakter olmalıdır."; }
    if ($sifre !== $sifre_tekrar) { $errors[] = "Şifreler uyuşmuyor."; }


    // Hiç hata yoksa veritabanı işlemlerine geç
    if (empty($errors)) {
        // Kullanıcı adı veya e-posta zaten var mı diye kontrol et
        $sql = "SELECT id FROM kullanicilar WHERE kullanici_adi = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $kullanici_adi, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Bu kullanıcı adı veya e-posta zaten kullanılıyor.";
        } else {
            // Şifreyi güvenli bir şekilde hash'le
            $hashed_sifre = password_hash($sifre, PASSWORD_DEFAULT);

            // Yeni kullanıcıyı veritabanına ekle
            $sql_insert = "INSERT INTO kullanicilar (kullanici_adi, email, sifre) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sss", $kullanici_adi, $email, $hashed_sifre);
            
            if ($stmt_insert->execute()) {
                $success_message = "Kayıt başarılı! Artık giriş yapabilirsiniz.";
            } else {
                $errors[] = "Kayıt sırasında bir hata oluştu: " . $conn->error;
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
    
    // Bağlantıyı TÜM veritabanı işlemleri bittikten sonra kapat
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Şehrin Nabzı</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f4f4f4; line-height: 1.6; }
        .container { max-width: 400px; margin: 50px auto; padding: 20px 30px; border: 1px solid #ddd; border-radius: 8px; background-color: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .error { color: #D8000C; background-color: #FFD2D2; padding: 10px; margin: 10px 0; border-radius: 4px; border: 1px solid #D8000C; }
        .success { color: #4F8A10; background-color: #DFF2BF; padding: 10px; margin: 10px 0; border-radius: 4px; border: 1px solid #4F8A10; }
        .form-footer { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Kayıt Ol</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p style="margin: 0;"><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success">
                <p><?php echo $success_message; ?></p>
                <a href="login.php">Giriş Yapmak için Tıklayın</a>
            </div>
        <?php else: ?>
            <form action="register.php" method="post" novalidate>
                <div class="form-group">
                    <label for="kullanici_adi">Kullanıcı Adı:</label>
                    <input type="text" id="kullanici_adi" name="kullanici_adi" required value="<?php echo isset($_POST['kullanici_adi']) ? htmlspecialchars($_POST['kullanici_adi']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="email">E-posta:</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="sifre">Şifre (En az 6 karakter):</label>
                    <input type="password" id="sifre" name="sifre" required>
                </div>
                <div class="form-group">
                    <label for="sifre_tekrar">Şifre Tekrar:</label>
                    <input type="password" id="sifre_tekrar" name="sifre_tekrar" required>
                </div>
                <button type="submit">Kayıt Ol</button>
            </form>
            <div class="form-footer">
                <p>Zaten bir hesabın var mı? <a href="login.php">Giriş Yap</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>