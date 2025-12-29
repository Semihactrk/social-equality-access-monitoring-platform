<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
require_once '../includes/db_connect.php';

$sql = "SELECT id, kullanici_adi, email, rol FROM kullanicilar WHERE id != " . $_SESSION['user_id'];
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <a href="../index.php"><i class="fas fa-map-marked-alt"></i> Public Map</a>
                <a href="manage_reports.php"><i class="fas fa-list-alt"></i> Manage Reports</a>
                <a href="manage_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a>
                <a href="../auth/logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <h1>User Management</h1>
            <div class="card">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Update Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['kullanici_adi']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <span class="role-badge">
                                    <?php 
                                        if ($row['rol'] == 'vatandas') {
                                            echo 'Citizen';
                                        } else {
                                            
                                            echo ucfirst($row['rol']); 
                                        }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <form action="update_role.php" method="POST" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <select name="new_role" class="small-select">
                                        <option value="vatandas" <?php echo ($row['rol']=='vatandas')?'selected':''; ?>>Citizen</option>
                                        <option value="admin" <?php echo ($row['rol']=='admin')?'selected':''; ?>>Admin</option>
                                    </select>
                                    <button type="submit" class="btn-save">Save</button>
                                </form>
</td>
                            <td>
                                <form action="delete_user.php" method="POST" onsubmit="return confirm('Silmek istediğine emin misin?');">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>