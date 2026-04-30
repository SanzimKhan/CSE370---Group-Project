<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/community.php';

// Check authentication
require_login();

$conn = getConnection();
$community = new Community($conn);

// Get conversations
$conversations = $community->getUserConversations($_SESSION['user_id']);

// Get unread count
$unread_count = $community->getUnreadMessageCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - BRACU Freelance Marketplace</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .messages-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .messages-header {
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }

        .messages-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .unread-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 12px;
            margin-left: 10px;
        }

        .search-bar {
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .conversation-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .conversation-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            gap: 15px;
            color: inherit;
        }

        .conversation-item:hover {
            background: #f9f9f9;
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
        }

        .conversation-item.unread {
            background: #f0f8ff;
            border-left: 4px solid #007bff;
        }

        .conversation-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .conversation-content {
            flex: 1;
            min-width: 0;
        }

        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .conversation-name {
            font-weight: bold;
            color: #333;
            font-size: 15px;
        }

        .conversation-time {
            font-size: 12px;
            color: #999;
        }

        .conversation-message {
            color: #666;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 5px;
        }

        .conversation-item.unread .conversation-message {
            font-weight: bold;
            color: #007bff;
        }

        .conversation-meta {
            font-size: 12px;
            color: #999;
        }

        .unread-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #007bff;
            flex-shrink: 0;
        }

        .no-conversations {
            text-align: center;
            padding: 40px;
            color: #999;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
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
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="messages-container">
        <a href="../index.php" class="back-link">← Back Home</a>

        <div class="messages-header">
            <h1 class="messages-title">
                Messages
                <?php if ($unread_count > 0): ?>
                    <span class="unread-badge"><?php echo $unread_count; ?> Unread</span>
                <?php endif; ?>
            </h1>
        </div>

        <!-- Conversations List -->
        <div class="conversation-list">
            <?php if (count($conversations) > 0): ?>
                <?php foreach ($conversations as $conv): ?>
                    <a href="messages.php?user=<?php echo urlencode($conv['contact_id']); ?>" class="conversation-item <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($conv['avatar_path'] ?? '/assets/uploads/avatars/default.png'); ?>" 
                             alt="<?php echo htmlspecialchars($conv['full_name']); ?>" 
                             class="conversation-avatar">
                        
                        <div class="conversation-content">
                            <div class="conversation-header">
                                <span class="conversation-name"><?php echo htmlspecialchars($conv['full_name']); ?></span>
                                <span class="conversation-time"><?php echo date('M d H:i', strtotime($conv['last_message_time'])); ?></span>
                            </div>
                            <div class="conversation-message">
                                <?php 
                                    $is_sent = $conv['last_message'] && strpos($conv['last_message'], $_SESSION['user_id']) !== false;
                                    echo ($conv['is_read'] === '0' ? '• ' : '') . htmlspecialchars(substr($conv['last_message'], 0, 60));
                                ?>
                            </div>
                        </div>

                        <?php if ($conv['unread_count'] > 0): ?>
                            <div class="unread-indicator"></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-conversations">
                    <p>No messages yet. Start a conversation by messaging someone!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
