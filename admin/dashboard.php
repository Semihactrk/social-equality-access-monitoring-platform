<?php
session_start();
include '../includes/db_connect.php';

// Access Control - Basitçe admin mi diye kontrol et (rol sistemi varsa aç)
// if ($_SESSION['role'] !== 'admin') { header("Location: ../index.php"); exit(); }

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $cat = $_POST['category'];
    $qty = $_POST['quantity'];
    $icon = $_POST['icon']; 

    $stmt = $conn->prepare("INSERT INTO resources (name, category, quantity, icon_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $name, $cat, $qty, $icon);
    
    if ($stmt->execute()) {
        $msg = "Resource Added!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar" style="background: #2c3e50;">
            <h2>Admin Panel</h2>
            <nav>
                <a href="dashboard.php" class="active">Add Resources</a>
                <a href="../auth/logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <h1>Add New Resource</h1>
            <?php if($msg) echo "<p style='color: green; font-weight:bold;'>$msg</p>"; ?>
            
            <form method="POST" style="max-width: 500px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div class="form-group">
                    <label>Resource Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Winter Boots">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" class="form-control" required placeholder="e.g. Clothing">
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="quantity" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Icon (Emoji)</label>
                    <input type="text" name="icon" class="form-control" placeholder="e.g. 👢" value="📦">
                </div>
                <button type="submit" class="btn btn-primary">Add Resource</button>
            </form>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
