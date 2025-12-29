<?php
session_start();

// 1. Veritabanı Bağlantısını Çağır 
require_once '../includes/db_connect.php'; 

// Durum güncelleme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'deliver' && isset($_GET['id'])) {
    $move_id = $_GET['id'];
    $update_sql = "UPDATE kullanici_hareketleri SET durum = 'delivered' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $move_id);
    $stmt->execute();
    
    // Sayfayı yenile ki değişiklik görünsün
    header("Location: manage_aid.php");
    exit();
}

// 2. Admin Yetki Kontrolü
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Sadece paket alım hareketlerini çekiyoruz
$sql_aid = "SELECT h.id, k.kullanici_adi, h.ad_soyad, h.tc_no, h.tarih, h.durum
            FROM kullanici_hareketleri h 
            JOIN kullanicilar k ON h.kullanici_id = k.id 
            WHERE h.islem_tipi = 'ALIM' 
            ORDER BY h.tarih DESC";

$result_aid = $conn->query($sql_aid);
?>

<style>
    .styled-table {
        width: 100%;
        border-collapse: collapse; /* İnce çizgiler için şart */
        margin: 25px 0;
        font-size: 0.9em;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    }

    .styled-table thead tr {
        background-color: #2c3e50;
        color: #ffffff;
        text-align: left;
    }

    .styled-table th,
    .styled-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eeeeee; /* Aradığın ince satır çizgisi */
    }

    .styled-table tbody tr:hover {
        background-color: #fcfcfc; /* Satır üzerine gelince hafif renk */
    }

    .badge {
        padding: 6px 12px;
        border-radius: 4px;
        color: white;
        font-weight: bold;
        font-size: 0.75rem;
        display: inline-block;
        min-width: 130px;
        text-align: center;
    }

    .btn-deliver {
        background: #2980b9;
        color: white !important;
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.8rem;
        transition: 0.3s;
    }

    .btn-deliver:hover {
        background: #1f6391;
    }
</style>

<div class="card" style="margin-top: 40px; border-top: 5px solid #2980b9; padding: 20px; background: white; border-radius: 8px;">
    <h2 style="color: #2c3e50; margin-bottom: 10px;">
        <i class="fas fa-clipboard-list"></i> Aid Distribution & Verification List
    </h2>
    <p style="color: #666; font-size: 0.9rem; margin-bottom: 20px;">
        Verify the identity of the citizen before delivering the food package.
    </p>

    <table class="styled-table">
        <thead>
            <tr>
                <th>System Username</th>
                <th>Real Full Name</th>
                <th>TC Identity No</th>
                <th>Request Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result_aid->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['kullanici_adi']); ?></td>
                    <td><strong><?php echo htmlspecialchars($row['ad_soyad']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['tc_no']); ?></td>
                    <td><?php echo date('d M Y, H:i', strtotime($row['tarih'])); ?></td>
                    <td>
                        <?php if($row['durum'] == 'delivered'): ?>
                            <span class="badge" style="background:#34495e;">DELIVERED</span>
                        <?php else: ?>
                            <span class="badge" style="background:#27ae60;">READY FOR PICKUP</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($row['durum'] != 'delivered'): ?>
                            <a href="manage_aid.php?action=deliver&id=<?php echo $row['id']; ?>" 
                               class="btn-deliver" 
                               onclick="return confirm('Mark this as delivered?')">
                                <i class="fas fa-check"></i> Mark as Delivered
                            </a>
                        <?php else: ?>
                            <span style="color: #bdc3c7; font-style: italic;"><i class="fas fa-check-double"></i> Completed</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>