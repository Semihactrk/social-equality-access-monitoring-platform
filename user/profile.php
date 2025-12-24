<?php
session_start();

// Access Control: Redirect to login if session is missing
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$reports = [];

// Fetch user's reports from database
// Note: We use 'guncelleme_tarihi' assuming the DB schema was updated as per previous instructions.
$sql = "SELECT id, baslik, aciklama, durum, olusturma_tarihi, guncelleme_tarihi, fotograf_yolu 
        FROM raporlar 
        WHERE kullanici_id = ? 
        ORDER BY olusturma_tarihi DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
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
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand"><i class="fas fa-globe-americas"></i> Social Equality Map</div>
       
        <div class="navbar-menu">
            <a href="../index.php">Home</a>
            <a href="../forum/index.php">Forum</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="../user/submit_report.php" class="btn btn-primary">Submit Report</a>
                <a href="../user/profile.php">My Profile</a>
                <a href="../auth/logout.php" style="color:var(--primary-red);">Logout</a>
            <?php else: ?>
                <a href="../auth/login.php">Login</a>
                <a href="../auth/register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>   
        
    </nav>

    <div class="container">
        
        <div class="card" style="text-align: center; padding: 40px;">
            <div style="width: 80px; height: 80px; background: var(--primary-blue); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px;">
                <i class="fas fa-user"></i>
            </div>
            <h2 style="margin: 0;"><?php echo htmlspecialchars($_SESSION['kullanici_adi']); ?></h2>
            <p style="color: #777;">Member since 2024</p>
            
            <div style="margin-top: 20px;">
                <a href="submit_report.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Submit New Report
                </a>
            </div>
        </div>

        <h3 style="margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px;">
            <i class="fas fa-history"></i> My Report History
        </h3>
        
        <?php if (!empty($reports)): ?>
            <?php foreach ($reports as $report): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <div>
                            <h4 style="margin: 0; font-size: 1.2rem;">
                                <a href="../report_details.php?id=<?php echo $report['id']; ?>" style="color: var(--primary-blue);">
                                    <?php echo htmlspecialchars($report['baslik']); ?>
                                </a>
                            </h4>
                            <small style="color: #999;">
                                <i class="far fa-calendar-alt"></i> 
                                <?php echo date('d M Y, H:i', strtotime($report['olusturma_tarihi'])); ?>
                            </small>
                        </div>
                        
                        <?php
                            // Determine status badge class and text
                            $badgeClass = 'pending';
                            $statusText = 'Pending';
                            
                            if ($report['durum'] == 'islemde' || $report['durum'] == 'isleme_alindi') {
                                $badgeClass = 'process';
                                $statusText = 'In Progress';
                            } elseif ($report['durum'] == 'cozuldu') {
                                $badgeClass = 'solved';
                                $statusText = 'Resolved';
                            }
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>">
                            <?php echo $statusText; ?>
                        </span>
                    </div>
                    
                    <div style="display: flex; gap: 20px;">
                        <?php if(!empty($report['fotograf_yolu'])): ?>
                            <div style="flex-shrink: 0;">
                                <?php 
                                    // Fix path relative to user folder
                                    $img = str_replace('admin/uploads/', 'uploads/', $report['fotograf_yolu']); 
                                ?>
                                <img src="../<?php echo $img; ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius); border: 1px solid #eee;">
                            </div>
                        <?php endif; ?>
                        
                        <div style="flex: 1;">
                            <p style="color: #555; margin-bottom: 10px;">
                                <?php echo htmlspecialchars(substr($report['aciklama'], 0, 150)) . '...'; ?>
                            </p>
                            <a href="../report_details.php?id=<?php echo $report['id']; ?>" class="btn btn-primary" style="padding: 5px 15px; font-size: 0.85rem;">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info" style="text-align: center; padding: 40px;">
                <i class="fas fa-folder-open fa-3x" style="color: #b3e5fc; margin-bottom: 15px; display: block;"></i>
                You haven't submitted any reports yet. <br>
                <a href="submit_report.php" style="font-weight: bold; text-decoration: underline;">Report an issue now.</a>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>