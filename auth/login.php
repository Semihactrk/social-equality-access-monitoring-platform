<?php
// Session management starts here - DO NOT TOUCH
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi_veya_email = trim($_POST['kullanici_adi_veya_email']);
    $sifre = $_POST['sifre'];

    if (empty($kullanici_adi_veya_email) || empty($sifre)) {
        $errors[] = "All fields are required.";
    } else {
        $sql = "SELECT id, kullanici_adi, sifre, rol FROM kullanicilar WHERE kullanici_adi = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param("ss", $kullanici_adi_veya_email, $kullanici_adi_veya_email);
        
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($sifre, $user['sifre'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['kullanici_adi'] = $user['kullanici_adi'];
                $_SESSION['rol'] = $user['rol'];
                
                header("Location: ../index.php");
                exit();

            } else {
                $errors[] = "Incorrect username or password.";
            }
        } else {
            $errors[] = "Incorrect username or password.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Social Equality Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    
    <nav class="navbar">
        <div class="navbar-brand">Social Equality Map</div>
        <div class="navbar-menu">
            <a href="../index.php">Home</a>
            <a href="register.php">Register</a>
        </div>
    </nav>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p style="color: var(--text-muted);">Login to report issues.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post" novalidate>
                <div class="form-group">
                    <label for="kullanici_adi_veya_email">Username or Email</label>
                    <input type="text" id="kullanici_adi_veya_email" name="kullanici_adi_veya_email" required placeholder="Enter your username">
                </div>
                <div class="form-group">
                    <label for="sifre">Password</label>
                    <input type="password" id="sifre" name="sifre" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register Now</a></p>
            </div>
        </div>
    </div>

</body>
</html>