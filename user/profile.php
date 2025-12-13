<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
$u_id = $_SESSION['user_id'];

// Fetch History
$stmt = $conn->prepare("
    SELECT r.name, r.category, r.icon_name, res.reservation_date 
    FROM reservations res
    JOIN resources r ON res.resource_id = r.id
    WHERE res.user_id = ?
    ORDER BY res.reservation_date DESC
");
$stmt->bind_param("i", $u_id);
$stmt->execute();
$history = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - Equality Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <h2>Equality Platform</h2>
            <nav>
                <a href="resources.php">Available Resources</a>
                <a href="profile.php" class="active">My Profile & History</a>
                <a href="../auth/logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <h1 style="color: var(--primary);">My Reservation History</h1>
            <p>Here is a list of items you have successfully reserved.</p>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Date Reserved</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history->num_rows > 0): ?>
                        <?php while($row = $history->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo $row['icon_name']; ?> 
                                <?php echo htmlspecialchars($row['name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><?php echo date("F j, Y, g:i a", strtotime($row['reservation_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No reservations found. Go reserve something!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
