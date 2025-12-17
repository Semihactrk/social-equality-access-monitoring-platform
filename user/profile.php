<?php
// Backend Logic - DO NOT TOUCH
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../includes/db_connect.php';

$kullanici_id = $_SESSION['user_id'];
$raporlar = [];

// Fetch user's reports
$sql = "SELECT id, baslik, aciklama, durum, olusturma_tarihi, guncelleme_tarihi FROM raporlar WHERE kullanici_id = ? ORDER BY olusturma_tarihi DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $raporlar[] = $row;
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
    <title>My Profile - Social Equality Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Page Specific Styles for Status Badges */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: var(--radius);
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
        }
        /* Renkler style.css'deki SDG paletine uyarlanmıştır */
        .status-beklemede { background-color: #f1c40f; color: #333; } /* Yellow */
        .status-islemde { background-color: var(--primary-blue); } /* Blue */
        .status-cozuldu { background-color: var(--primary-green); } /* Green */

        .report-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .report-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary-blue);
        }
        .report-date {
            color: #999;
            font-size: 0.85rem;
            margin-bottom: 5px;
            display: block;
        }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        
        <nav class="sidebar">
            <div class="sidebar-header">
                User Panel
            </div>
            <div class="sidebar-menu">
                <a href="../index.php">
                    <i class="fas fa-map-marked-alt"></i> &nbsp; Back to Home Map
                </a>
                <a href="profile.php" class="active">
                    <i class="fas fa-chart-line"></i> &nbsp; My Reports
                </a>
                <a href="submit_report.php">
                    <i class="fas fa-plus-circle"></i> &nbsp; Submit New Report
                </a>
                <a href="../forum/index.php">
                    <i class="fas fa-comments"></i> &nbsp; Community Forum
                </a>
                <a href="../auth/logout.php" style="color: #e74c3c;">
                    <i class="fas fa-sign-out-alt"></i> &nbsp; Logout
                </a>
            </div>
        </nav>

        <main class="main-content">
            <h2 style="border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 25px;">
                Welcome, <?php echo htmlspecialchars($_SESSION['kullanici_adi']); ?>
            </h2>

            <h3>My Submitted Reports</h3>
            
            <?php if (!empty($raporlar)): ?>
                <?php foreach ($raporlar as $rapor): ?>
                    <div class="report-card">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <span class="report-date" style="display:block; color:#999;">
                                    Submitted: 
                                    <?php 
                                        // Eğer oluşturma tarihi geçerliyse göster
                                        if (!empty($rapor['olusturma_tarihi']) && strtotime($rapor['olusturma_tarihi'])) {
                                            echo date('d M Y, H:i', strtotime($rapor['olusturma_tarihi']));
                                        } else {
                                            echo 'Tarih Bilgisi Yok';
                                        }
                                    ?>
                                </span>
                                
                                <?php 
                                    // 1. Guncelleme tarihi boş mu? 
                                    // 2. Guncelleme tarihi 1970 mi? (Hata kontrolü) 
                                    // 3. Guncelleme tarihi, oluşturma tarihinden farklı mı?
                                    
                                    $guncelleme_timestamp = strtotime($rapor['guncelleme_tarihi']);
                                    $olusturma_timestamp = strtotime($rapor['olusturma_tarihi']);
                                    
                                    // Sadece geçerli bir zaman damgası varsa VE oluşturma zamanından büyükse göster
                                    if ($guncelleme_timestamp && $guncelleme_timestamp > $olusturma_timestamp): 
                                ?>
                                    <span class="report-date" style="display:block; color:var(--primary-blue); font-weight: 600;">
                                        Updated: <?php echo date('d M Y, H:i', $guncelleme_timestamp); ?>
                                    </span>
                                <?php endif; ?>

                                <h4 style="margin: 0 0 10px 0; color: var(--primary-blue);">
                                



                                    <a href="../report_details.php?id=<?php echo $rapor['id']; ?>">
                                        <?php echo htmlspecialchars($rapor['baslik']); ?>
                                    </a>
                                </h4>
                            </div>
                            
                            <?php
                                $statusClass = 'status-beklemede';
                                $statusLabel = 'Pending';
                                
                                if($rapor['durum'] == 'islemde') {
                                    $statusClass = 'status-islemde';
                                    $statusLabel = 'In Progress';
                                } elseif($rapor['durum'] == 'cozuldu') {
                                    $statusClass = 'status-cozuldu';
                                    $statusLabel = 'Resolved';
                                }
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo $statusLabel; ?>
                            </span>
                        </div>
                        
                        <p style="color: #555; margin-top: 10px;">
                            <?php echo nl2br(htmlspecialchars($rapor['aciklama'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info" style="background: #e3f2fd; color: #0d47a1; padding: 20px;">
                    You haven't submitted any reports yet. 
                    <a href="submit_report.php" style="font-weight: bold; text-decoration: underline;">Submit your first report now.</a>
                </div>
            <?php endif; ?>

        </main>
    </div>

</body>
</html>