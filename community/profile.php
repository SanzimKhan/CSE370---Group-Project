<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/community.php';


require_login();

$conn = getConnection();
$community = new Community($conn);


$profile_user_id = $_GET['id'] ?? null;

if (!$profile_user_id) {
    header('Location: /index.php');
    exit;
}


$query = "SELECT * FROM User WHERE BRACU_ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $profile_user_id);
$stmt->execute();
$user_profile = $stmt->get_result()->fetch_assoc();

if (!$user_profile) {
    header('Location: /index.php');
    exit;
}


$ratings = $community->getUserRatings($profile_user_id);
$rating_stats = $community->getUserRatingAverage($profile_user_id);


$badges = $community->getUserBadges($profile_user_id);


$query = "SELECT COUNT(*) as count FROM Gigs WHERE BRACU_ID = ? AND STATUS = 'done'";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $profile_user_id);
$stmt->execute();
$completed_gigs = $stmt->get_result()->fetch_assoc()['count'];


$viewerId = (string) ($_SESSION['user_bracu_id'] ?? '');
$can_message = $viewerId !== '' && $viewerId !== $profile_user_id;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user_profile['full_name']); ?> - Profile</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .profile-header {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            gap: 30px;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #007bff;
            flex-shrink: 0;
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .profile-bio {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .profile-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .badge {
            display: inline-block;
            padding: 8px 12px;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: #333;
        }

        .badge.verified {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .badge.top-rated {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }

        .badge.responsive {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .ratings-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .rating-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .stat-box {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .star-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .stars {
            color: #ffc107;
        }

        .rating-item {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .rating-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .rating-author {
            font-weight: bold;
            color: #333;
        }

        .rating-date {
            font-size: 12px;
            color: #999;
        }

        .rating-stars {
            color: #ffc107;
            margin-bottom: 8px;
        }

        .rating-text {
            color: #555;
            line-height: 1.5;
        }

        .no-ratings {
            text-align: center;
            padding: 20px;
            color: #999;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }

        .stat-card-value {
            font-size: 22px;
            font-weight: bold;
            color: #007bff;
        }

        .stat-card-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="profile-container">
        <a href="javascript:history.back()" class="back-link">← Back</a>

        <!-- Profile Header -->
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($user_profile['avatar_path'] ?? '/assets/uploads/avatars/default.png'); ?>" 
                 alt="<?php echo htmlspecialchars($user_profile['full_name']); ?>" 
                 class="profile-avatar">
            
            <div class="profile-info">
                <div class="profile-name"><?php echo htmlspecialchars($user_profile['full_name']); ?></div>
                
                <?php if ($user_profile['bio']): ?>
                    <div class="profile-bio">
                        <?php echo htmlspecialchars($user_profile['bio']); ?>
                    </div>
                <?php endif; ?>

                <!-- Badges -->
                <?php if (count($badges) > 0): ?>
                    <div class="profile-badges">
                        <?php foreach ($badges as $badge): ?>
                            <span class="badge <?php echo strtolower($badge['badge_type']); ?>" title="<?php echo htmlspecialchars($badge['badge_description'] ?? ''); ?>">
                                ⭐ <?php echo htmlspecialchars($badge['badge_name']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="profile-actions">
                    <?php if ($can_message): ?>
                        <a href="messages.php?user=<?php echo urlencode($profile_user_id); ?>" class="btn btn-primary">Send Message</a>
                    <?php endif; ?>
                </div>

                <!-- Profile Stats -->
                <div class="profile-stats">
                    <div class="stat-card">
                        <div class="stat-card-value">
                            <?php echo $rating_stats ? (float)$rating_stats['total_ratings'] : 0; ?>
                        </div>
                        <div class="stat-card-label">Total Ratings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-value">
                            <?php echo $rating_stats ? round((float)$rating_stats['avg_rating'], 1) : '0'; ?>⭐
                        </div>
                        <div class="stat-card-label">Average Rating</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-value"><?php echo $completed_gigs; ?></div>
                        <div class="stat-card-label">Jobs Completed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ratings Section -->
        <div class="ratings-section">
            <h2 class="section-title">Reviews & Ratings</h2>

            <?php if ($rating_stats && $rating_stats['total_ratings'] > 0): ?>
                <!-- Rating Stats -->
                <div class="rating-stats">
                    <div class="stat-box">
                        <div class="stat-value" style="font-size: 32px;">
                            <?php echo round((float)$rating_stats['avg_rating'], 1); ?>⭐
                        </div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $rating_stats['five_star']; ?></div>
                        <div class="stat-label">5 Star</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $rating_stats['four_star']; ?></div>
                        <div class="stat-label">4 Star</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $rating_stats['three_star']; ?></div>
                        <div class="stat-label">3 Star</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $rating_stats['two_star'] + $rating_stats['one_star']; ?></div>
                        <div class="stat-label">1-2 Star</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Individual Ratings -->
            <?php if (count($ratings) > 0): ?>
                <?php foreach ($ratings as $rating): ?>
                    <div class="rating-item">
                        <div class="rating-header">
                            <span class="rating-author"><?php echo htmlspecialchars($rating['full_name']); ?></span>
                            <span class="rating-date"><?php echo date('M d, Y', strtotime($rating['created_at'])); ?></span>
                        </div>
                        <div class="rating-stars">
                            <?php echo str_repeat('⭐', $rating['rating']) . str_repeat('☆', 5 - $rating['rating']); ?>
                        </div>
                        <?php if ($rating['review_text']): ?>
                            <div class="rating-text">
                                <?php echo htmlspecialchars($rating['review_text']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-ratings">
                    <p>No ratings yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
