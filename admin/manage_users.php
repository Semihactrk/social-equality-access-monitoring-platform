<?php
// Backend Logic - DO NOT TOUCH
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once '../includes/db_connect.php';

$current_admin_id = $_SESSION['user_id'];
$kullanicilar = [];
$sql = "SELECT id, kullanici_adi, email, rol FROM kullanicilar WHERE id != ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $current_admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $kullanicilar[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .role-badge {
            display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; text-transform: capitalize;
        }
        .role-admin { background-color: var(--primary-red); color: white; }
        .role-yetkili { background-color: var(--primary-blue); color: white; }
        .role-vatandas { background-color: #ccc; color: var(--text-main); }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        <nav class="sidebar">
            <div class="sidebar-header">Admin Panel</div>
            <div class="sidebar-menu">
                <a href="../index.php">
                    <i class="fas fa-map-marked-alt"></i> &nbsp; Public Map
                </a>
                <a href="index.php">
                    <i class="fas fa-list-alt"></i> &nbsp; Manage Reports
                </a>
                <a href="manage_users.php" class="active">
                    <i class="fas fa-users"></i> &nbsp; Manage Users
                </a>
                <a href="../auth/logout.php" style="color: #e74c3c;">
                    <i class="fas fa-sign-out-alt"></i> &nbsp; Logout
                </a>
            </div>
        </nav>

        <main class="main-content">
            <h2 style="margin-bottom: 20px;">User Management</h2>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th style="width: 250px;">Update Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kullanicilar as $kullanici): ?>
                            <tr>
                                <td><?php echo $kullanici['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($kullanici['kullanici_adi']); ?></strong></td>
                                <td><?php echo htmlspecialchars($kullanici['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $kullanici['rol']; ?>">
                                        <?php echo ucfirst($kullanici['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form action="update_role.php" method="POST" style="display:flex; gap:5px;">
                                        <input type="hidden" name="user_id" value="<?php echo $kullanici['id']; ?>">
                                        <select name="new_role" class="form-control" style="padding: 5px; width: auto; font-size: 0.9rem;">
                                            <option value="vatandas" <?php if($kullanici['rol'] == 'vatandas') echo 'selected'; ?>>Citizen</option>
                                            <option value="yetkili" <?php if($kullanici['rol'] == 'yetkili') echo 'selected'; ?>>Official</option>
                                            <option value="admin" <?php if($kullanici['rol'] == 'admin') echo 'selected'; ?>>Admin</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary" style="padding: 5px 10px; font-size:0.8rem;">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <form action="delete_user.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone!');">
                                        <input type="hidden" name="user_id" value="<?php echo $kullanici['id']; ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size:0.8rem;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>