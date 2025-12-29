<?php
session_start();

// Access Control
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$message = "";

// --- NEW PART: STOCK DECREMENT LOGIC ---
if (isset($_POST['talep_et'])) {
    // Check stock and decrement if available
    $sql_update = "UPDATE yardim_kaynaklari SET stok = stok - 1 WHERE id = 1 AND stok > 0";
    $conn->query($sql_update);

    if ($conn->affected_rows > 0) {
        // Success message
        $message = "Request successful! Food package stock updated.";
        // Refresh page to show new stock
        header("Location: profile.php"); 
        exit();
    }
}

// Fetch current stock
$stok_sorgu = $conn->query("SELECT stok FROM yardim_kaynaklari WHERE id = 1");
$stok_veri = $stok_sorgu->fetch_assoc();
$kalan_stok = $stok_veri['stok'];
// --------------------------------------------------

// Fetch user's reports
$reports = [];
$sql = "SELECT id, baslik, aciklama, durum, olusturma_tarihi, fotograf_yolu 
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
        
        <div class="card" style="text-align: center; padding: 40px; margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; background: var(--primary-blue); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px;">
                <i class="fas fa-user"></i>
            </div>
            <h2 style="margin: 0;"><?php echo htmlspecialchars($_SESSION['kullanici_adi']); ?></h2>
            <p style="color: #777;">Citizen Account</p>
        </div>

        <div class="card" style="border-left: 5px solid #4C9F38; padding: 30px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="color: #4C9F38; margin-top: 0;">📦 Social Aid: Food Package</h2>
                    <p style="color: #555;">Food support provided by the municipality for those in need.</p>
                    
                    <?php if ($message): ?>
                        <p style="color: green; font-weight: bold;"><?php echo $message; ?></p>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: center; min-width: 150px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #333;">
                        <?php echo $kalan_stok; ?>
                    </div>
                    <div style="font-size: 0.8rem; color: #777; margin-bottom: 10px; font-weight:bold;">REMAINING STOCK</div>
                    
                    <form method="POST">
                        <button type="submit" name="talep_et" class="btn" 
                            style="width: 100%; <?php echo $kalan_stok > 0 ? 'background-color: #4C9F38; color: white;' : 'background-color: #ccc; cursor: not-allowed;'; ?>"
                            <?php echo $kalan_stok <= 0 ? 'disabled' : ''; ?>>
                            
                            <?php echo $kalan_stok > 0 ? 'Claim' : 'Out of Stock'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <h3 style="margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px;">
            <i class="fas fa-history"></i> My Report History
        </h3>
        
        <?php if (!empty($reports)): ?>
            <?php foreach ($reports as $report): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <h4 style="margin: 0; font-size: 1.2rem;">
                            <a href="../report_details.php?id=<?php echo $report['id']; ?>" style="color: var(--primary-blue);">
                                <?php echo htmlspecialchars($report['baslik']); ?>
                            </a>
                        </h4>
                        
                        <?php
                            $badgeClass = 'pending';
                            $statusText = 'Pending';
                            if ($report['durum'] == 'islemde' || $report['durum'] == 'isleme_alindi') {
                                $badgeClass = 'process'; $statusText = 'In Progress';
                            } elseif ($report['durum'] == 'cozuldu') {
                                $badgeClass = 'solved'; $statusText = 'Resolved';
                            }
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                    </div>
                    <p style="color: #555;">
                        <?php echo htmlspecialchars(substr($report['aciklama'], 0, 150)) . '...'; ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info" style="text-align: center;">
                You haven't submitted any reports yet.
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
