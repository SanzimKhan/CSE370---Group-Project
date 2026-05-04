<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/analytics.php';

// Check authentication
if (!isset($_SESSION['user_bracu_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if user has hiring mode
$pdo = db();
$stmt = $pdo->prepare("SELECT preferred_mode FROM User WHERE BRACU_ID = ?");
$stmt->execute([$_SESSION['user_bracu_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || ($user['preferred_mode'] !== 'hiring' && $_GET['mode'] !== 'hiring')) {
    header('Location: ../index.php');
    exit;
}

$analytics = new Analytics($pdo);

// Get user analytics (from client perspective)
$user_analytics = $analytics->getUserAnalytics($_SESSION['user_bracu_id']);

// Get spending statistics
$query = "SELECT
            COUNT(DISTINCT GID) as total_gigs_posted,
            SUM(CREDIT_AMOUNT) as total_spent,
            COUNT(CASE WHEN STATUS = 'done' THEN 1 END) as completed_gigs,
            COUNT(CASE WHEN STATUS = 'pending' THEN 1 END) as pending_gigs,
            COUNT(CASE WHEN STATUS = 'listed' THEN 1 END) as listed_gigs
          FROM Gigs
          WHERE BRACU_ID = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_bracu_id']]);
$spending = $stmt->fetch(PDO::FETCH_ASSOC);

// Get average per gig
$query = "SELECT
            AVG(CREDIT_AMOUNT) as avg_credit,
            MIN(CREDIT_AMOUNT) as min_credit,
            MAX(CREDIT_AMOUNT) as max_credit
          FROM Gigs
          WHERE BRACU_ID = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_bracu_id']]);
$credits_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get category breakdown
$query = "SELECT CATAGORY, COUNT(*) as count, SUM(CREDIT_AMOUNT) as total_spent
          FROM Gigs
          WHERE BRACU_ID = ?
          GROUP BY CATAGORY";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_bracu_id']]);
$category_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - BRACU Freelance Marketplace</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .analytics-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .stat-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .spending-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .category-breakdown {
            margin-top: 20px;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-name {
            font-weight: 500;
            color: #333;
        }

        .category-stats {
            display: flex;
            gap: 20px;
            color: #666;
        }

        .stat-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #007bff;
        }

        .stat-box-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-box-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="analytics-container">
        <h1>Client Analytics Dashboard</h1>

        <!-- Key Metrics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-label">Total Logins</div>
                <div class="stat-value"><?php echo $user_analytics['total_logins']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👁️</div>
                <div class="stat-label">Profile Views</div>
                <div class="stat-value"><?php echo $user_analytics['total_gig_views']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-label">Gigs Posted</div>
                <div class="stat-value"><?php echo $spending['total_gigs_posted'] ?? 0; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Completed Gigs</div>
                <div class="stat-value"><?php echo $spending['completed_gigs'] ?? 0; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-label">Total Spent</div>
                <div class="stat-value">৳<?php echo number_format($spending['total_spent'] ?? 0, 2); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-label">Last Activity</div>
                <div class="stat-value" style="font-size: 14px;">
                    <?php echo $user_analytics['last_activity'] 
                        ? date('M d, Y H:i', strtotime($user_analytics['last_activity']))
                        : 'No activity'; ?>
                </div>
            </div>
        </div>

        <!-- Spending Analytics -->
        <div class="spending-section">
            <h2 class="section-title">Spending Overview</h2>
            
            <div class="stat-row">
                <div class="stat-box">
                    <div class="stat-box-label">Active Gigs</div>
                    <div class="stat-box-value"><?php echo ($spending['listed_gigs'] ?? 0) + ($spending['pending_gigs'] ?? 0); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-box-label">Average Per Gig</div>
                    <div class="stat-box-value">৳<?php echo $credits_stats['avg_credit'] ? number_format($credits_stats['avg_credit'], 2) : '0.00'; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-box-label">Min - Max Range</div>
                    <div class="stat-box-value">৳<?php echo $credits_stats['min_credit'] ? number_format($credits_stats['min_credit'], 2) : '0.00'; ?> - ৳<?php echo $credits_stats['max_credit'] ? number_format($credits_stats['max_credit'], 2) : '0.00'; ?></div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <?php if (count($category_breakdown) > 0): ?>
                <div class="category-breakdown">
                    <h3 style="margin-bottom: 15px;">Spending by Category</h3>
                    <?php foreach ($category_breakdown as $category): ?>
                        <div class="category-item">
                            <span class="category-name"><?php echo $category['CATAGORY']; ?></span>
                            <div class="category-stats">
                                <span><?php echo $category['count']; ?> gigs</span>
                                <span>৳<?php echo number_format($category['total_spent'], 2); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666; margin-top: 20px;">No gigs posted yet.</p>
            <?php endif; ?>
        </div>

        <!-- Gig Status Overview -->
        <div class="spending-section" style="margin-top: 30px;">
            <h2 class="section-title">Gig Status Overview</h2>
            
            <div class="stat-row">
                <div class="stat-box" style="border-left-color: #28a745;">
                    <div class="stat-box-label">Completed</div>
                    <div class="stat-box-value" style="color: #28a745;"><?php echo $spending['completed_gigs'] ?? 0; ?></div>
                </div>
                <div class="stat-box" style="border-left-color: #ffc107;">
                    <div class="stat-box-label">Pending</div>
                    <div class="stat-box-value" style="color: #ffc107;"><?php echo $spending['pending_gigs'] ?? 0; ?></div>
                </div>
                <div class="stat-box" style="border-left-color: #17a2b8;">
                    <div class="stat-box-label">Listed</div>
                    <div class="stat-box-value" style="color: #17a2b8;"><?php echo $spending['listed_gigs'] ?? 0; ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
