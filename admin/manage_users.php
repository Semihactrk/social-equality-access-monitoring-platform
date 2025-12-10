<?php
session_start();
// Admin koruması
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once '..includes/db_connect.php';

// Adminin kendi kendini silmesini veya rolünü değiştirmesini engellemek için
// o anki admin hariç tüm kullanıcıları listele
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
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - Admin Paneli</title>
    <link rel="stylesheet" href="assets/css/style.css"> <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { border-bottom: 2px solid #333; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #333; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        select, button { padding: 5px; }
        .actions-form { display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin.php">&laquo; Rapor Yönetimine Geri Dön</a>
        <h1>Kullanıcı Yönetimi</h1>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kullanıcı Adı</th>
                    <th>E-posta</th>
                    <th>Rol</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kullanicilar as $kullanici): ?>
                    <tr>
                        <td><?php echo $kullanici['id']; ?></td>
                        <td><?php echo htmlspecialchars($kullanici['kullanici_adi']); ?></td>
                        <td><?php echo htmlspecialchars($kullanici['email']); ?></td>
                        <td>
                            <form class="actions-form" action="update_role.php" method="POST">
                                <input type="hidden" name="user_id" value="<?php echo $kullanici['id']; ?>">
                                <select name="new_role">
                                    <option value="vatandas" <?php if($kullanici['rol'] == 'vatandas') echo 'selected'; ?>>Vatandaş</option>
                                    <option value="yetkili" <?php if($kullanici['rol'] == 'yetkili') echo 'selected'; ?>>Yetkili</option>
                                    <option value="admin" <?php if($kullanici['rol'] == 'admin') echo 'selected'; ?>>Admin</option>
                                </select>
                                <button type="submit">Değiştir</button>
                            </form>
                        </td>
                        <td>
                            <form action="delete_user.php" method="POST" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!');">
                                <input type="hidden" name="user_id" value="<?php echo $kullanici['id']; ?>">
                                <button type="submit">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>