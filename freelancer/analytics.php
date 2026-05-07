<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/analytics.php';


if (!isset($_SESSION['user_bracu_id'])) {
    header('Location: ../index.php');
    exit;
}


$pdo = db();
$stmt = $pdo->prepare("SELECT preferred_mode FROM User WHERE BRACU_ID = ?");
$stmt->execute([$_SESSION['user_bracu_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user || ($user['preferred_mode'] !== 'working' && (!isset($_GET['mode']) || $_GET['mode'] !== 'working'))) {
    header('Location: ../index.php');
    exit;
}

$analytics = new Analytics($pdo);


$user_analytics = $analytics->getUserAnalytics($_SESSION['user_bracu_id']);


$gig_analytics = [];
$query = "SELECT GID FROM Gigs WHERE BRACU_ID = ? LIMIT 10";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_bracu_id']]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $gig_analytics[$row['GID']] = $analytics->getGigAnalytics($row['GID']);
}
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

        .gigs-analytics {
            margin-top: 30px;
        }

        .gig-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .gig-info {
            flex: 1;
        }

        .gig-metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            text-align: right;
        }

        .metric {
            text-align: center;
            padding: 10px;
        }

        .metric-value {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
        }

        .metric-label {
            font-size: 12px;
            color: #666;
        }

        .activity-breakdown {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-type {
            color: #333;
            font-weight: 500;
        }

        .activity-count {
            color: #007bff;
            font-weight: bold;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="analytics-container">
        <h1>Analytics Dashboard</h1>

        <!-- Key Metrics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Logins</div>
                <div class="stat-value"><?php echo $user_analytics['total_logins']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Gig Views</div>
                <div class="stat-value"><?php echo $user_analytics['total_gig_views']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Gigs Created</div>
                <div class="stat-value"><?php echo $user_analytics['gigs_created']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Gigs Applied</div>
                <div class="stat-value"><?php echo $user_analytics['gigs_applied']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total Earnings</div>
                <div class="stat-value">৳<?php echo number_format($user_analytics['total_earnings'], 2); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Pending Earnings</div>
                <div class="stat-value">৳<?php echo number_format($user_analytics['pending_earnings'], 2); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Completion Rate</div>
                <div class="stat-value"><?php echo $user_analytics['completion_rate']; ?>%</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Last Activity</div>
                <div class="stat-value" style="font-size: 14px;">
                    <?php echo $user_analytics['last_activity'] 
                        ? date('M d, Y H:i', strtotime($user_analytics['last_activity']))
                        : 'No activity'; ?>
                </div>
            </div>
        </div>

        <!-- Gig Analytics -->
        <div class="gigs-analytics">
            <h2 class="section-title">Gig Performance</h2>
            <?php if (count($gig_analytics) > 0): ?>
                <?php foreach ($gig_analytics as $gid => $metrics): ?>
                    <div class="gig-card">
                        <div class="gig-info">
                            <strong>Gig #<?php echo $gid; ?></strong>
                            <p style="color: #666; margin: 5px 0;">Status: <span style="color: #28a745;"><?php echo ucfirst($metrics['completion_status']); ?></span></p>
                        </div>
                        <div class="gig-metrics">
                            <div class="metric">
                                <div class="metric-value"><?php echo $metrics['views']; ?></div>
                                <div class="metric-label">Views</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value"><?php echo $metrics['applications']; ?></div>
                                <div class="metric-label">Applications</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value">৳<?php echo number_format($metrics['earned_amount'], 2); ?></div>
                                <div class="metric-label">Earned</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #666;">No gigs created yet.</p>
            <?php endif; ?>
        </div>

        <!-- Activity Breakdown -->
        <?php if (count($user_analytics['activity_breakdown']) > 0): ?>
            <div class="activity-breakdown">
                <h2 class="section-title">Activity Breakdown</h2>
                <?php foreach ($user_analytics['activity_breakdown'] as $activity_type => $count): ?>
                    <div class="activity-item">
                        <span class="activity-type"><?php echo ucfirst(str_replace('_', ' ', $activity_type)); ?></span>
                        <span class="activity-count"><?php echo $count; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
