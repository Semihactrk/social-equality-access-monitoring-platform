<?php
session_start();
include '../includes/db_connect.php';

// Security Check
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$msg = "";
$msg_type = "";

// Handle Reservation Logic (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resource_id'])) {
    $r_id = intval($_POST['resource_id']);
    $u_id = $_SESSION['user_id'];

    // 1. Check Stock
    $check_stmt = $conn->prepare("SELECT quantity FROM resources WHERE id = ?");
    $check_stmt->bind_param("i", $r_id);
    $check_stmt->execute();
    $res = $check_stmt->get_result();
    $row = $res->fetch_assoc();

    if ($row && $row['quantity'] > 0) {
        $conn->begin_transaction();
        try {
            $update_stmt = $conn->prepare("UPDATE resources SET quantity = quantity - 1 WHERE id = ?");
            $update_stmt->bind_param("i", $r_id);
            $update_stmt->execute();

            $ins_stmt = $conn->prepare("INSERT INTO reservations (user_id, resource_id) VALUES (?, ?)");
            $ins_stmt->bind_param("ii", $u_id, $r_id);
            $ins_stmt->execute();

            $conn->commit();
            $msg = "Reservation Successful! Check your profile.";
            $msg_type = "green";
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error processing request.";
            $msg_type = "red";
        }
    } else {
        $msg = "Sorry, this item just went out of stock.";
        $msg_type = "red";
    }
}

// Fetch All Resources
$sql = "SELECT * FROM resources";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - Equality Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <h2>Equality Platform</h2>
            <nav>
                <a href="resources.php" class="active">Available Resources</a>
                <a href="profile.php">My Profile & History</a>
                <a href="../auth/logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <h1 style="color: var(--primary);">Available Resources</h1>
            
            <?php if($msg): ?>
                <div style="padding: 15px; background: <?php echo $msg_type=='green'?'#d4edda':'#f8d7da'; ?>; color: <?php echo $msg_type=='green'?'#155724':'#721c24'; ?>; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Category</th>
                        <th>Item Name</th>
                        <th>Stock Available</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $in_stock = $row['quantity'] > 0;
                        ?>
                        <tr class="<?php echo $in_stock ? '' : 'disabled-row'; ?>">
                            <td style="font-size: 1.5em;"><?php echo $row['icon_name']; ?></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td>
                                <?php if ($in_stock): ?>
                                    <form method="POST" action="resources.php">
                                        <input type="hidden" name="resource_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-accent">Reserve</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #999; font-weight: bold;">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No resources found in database.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
