<?php
session_start();
include '../includes/db_connect.php';
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    // Basit sunucu taraflı doğrulama
    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        // E-posta daha önce alınmış mı?
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "This email is already registered.";
        } else {
            // Şifreleme ve Kayıt
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $role = 'student'; // Varsayılan rol

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_pass, $role);
            
            if ($stmt->execute()) {
                $success = "Registration Successful! Redirecting to login...";
                header("refresh:2;url=login.php"); // 2 saniye sonra login'e at
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Equality Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="hero-center">
        <div class="auth-card">
            <h2 style="color: var(--primary);">Join the Platform</h2>
            
            <?php if($error) echo "<p class='error-msg' style='display:block'>$error</p>"; ?>
            <?php if($success) echo "<p style='color: green; text-align:center;'>$success</p>"; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    <small id="pass-error" class="error-msg"></small>
                </div>
                <button type="submit" class="btn btn-accent" style="width: 100%;">Register</button>
            </form>
            <p style="margin-top: 15px;">Already have an account? <a href="login.php" style="color: var(--primary);">Login here</a></p>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
