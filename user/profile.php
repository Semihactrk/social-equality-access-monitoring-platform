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
$sql = "SELECT baslik, aciklama, durum, olusturma_tarihi FROM raporlar WHERE kullanici_id = ? ORDER BY olusturma_tarihi DESC";

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
    <style>
        /* Page Specific Styles for Status Badges */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
        }
        .status-beklemede { background-color: #f1c40f; color: #fff; } /* Yellow */
        .status-islemde { background-color: #3498db; } /* Blue */
        .status-cozuldu { background-color: #27ae60; } /* Green */

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
                    &larr; Back to Home Map
                </a>
                <a href="profile.php" class="active">
                    My Reports
                </a>
                <a href="submit_report.php">
                    + Submit New Report
                </a>
                <a href="../forum/index.php">
                    Community Forum
                </a>
                <a href="../auth/logout.php" style="color: #e74c3c;">
                    Logout
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
                                <span class="report-date">
                                    <?php echo date('d M Y, H:i', strtotime($rapor['olusturma_tarihi'])); ?>
                                </span>
                                <h4 style="margin: 0 0 10px 0; color: var(--primary-blue);">
                                    <?php echo htmlspecialchars($rapor['baslik']); ?>
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
