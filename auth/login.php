<?php
session_start();
include '../includes/db_connect.php';
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Secure Login Logic
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role']; 
            header("Location: ../user/resources.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Equality Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="hero-center">
        <div class="auth-card">
            <h2 style="color: var(--primary);">Welcome Back</h2>
            <?php if($error) echo "<p class='error-msg' style='display:block'>$error</p>"; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
            <p style="margin-top: 15px;">No account? <a href="register.php" style="color: var(--accent);">Register here</a></p>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
