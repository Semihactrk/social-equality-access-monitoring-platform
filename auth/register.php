<?php
// PHP Logic - DO NOT TOUCH
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require_once '../includes/db_connect.php';

    $kullanici_adi = trim($_POST['kullanici_adi']);
    $email = trim($_POST['email']);
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];

    // Validation (Translated errors)
    if (empty($kullanici_adi)) { $errors[] = "Username cannot be empty."; }
    if (empty($email)) { $errors[] = "Email cannot be empty."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Please enter a valid email address."; }
    if (empty($sifre)) { $errors[] = "Password cannot be empty."; }
    if (strlen($sifre) < 6) { $errors[] = "Password must be at least 6 characters."; }
    if ($sifre !== $sifre_tekrar) { $errors[] = "Passwords do not match."; }

    if (empty($errors)) {
        $sql = "SELECT id FROM kullanicilar WHERE kullanici_adi = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $kullanici_adi, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "This username or email is already taken.";
        } else {
            $hashed_sifre = password_hash($sifre, PASSWORD_DEFAULT);

            $sql_insert = "INSERT INTO kullanicilar (kullanici_adi, email, sifre) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sss", $kullanici_adi, $email, $hashed_sifre);
            
            if ($stmt_insert->execute()) {
                $success_message = "Registration successful! You can now login.";
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
            $stmt_insert->close();
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
    <title>Register - Social Equality Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">Social Equality Map</div>
        <div class="navbar-menu">
            <a href="../index.php">Home</a>
            <a href="login.php">Login</a>
        </div>
    </nav>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Create Account</h2>
                <p style="color: var(--text-muted);">Join the community to report and resolve issues.</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <p><?php echo $success_message; ?></p>
                    <div style="margin-top:10px;">
                        <a href="login.php" class="btn btn-primary btn-block">Go to Login</a>
                    </div>
                </div>
            <?php else: ?>
                <form action="register.php" method="post" novalidate>
                    <div class="form-group">
                        <label for="kullanici_adi">Username</label>
                        <input type="text" id="kullanici_adi" name="kullanici_adi" required value="<?php echo isset($_POST['kullanici_adi']) ? htmlspecialchars($_POST['kullanici_adi']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="sifre">Password (Min 6 chars)</label>
                        <input type="password" id="sifre" name="sifre" required>
                    </div>
                    <div class="form-group">
                        <label for="sifre_tekrar">Confirm Password</label>
                        <input type="password" id="sifre_tekrar" name="sifre_tekrar" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                </form>
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Login Here</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
