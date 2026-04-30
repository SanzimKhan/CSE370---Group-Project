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

$thread_id = (int) ($_GET['id'] ?? 0);

if (!$thread_id) {
    header('Location: forum.php');
    exit;
}

// Handle new reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reply'])) {
    $reply_text = trim($_POST['reply_text'] ?? '');
    
    if ($reply_text) {
        $community->addForumReply($thread_id, $_SESSION['user_id'], $reply_text);
        header('Location: forum_view.php?id=' . $thread_id);
        exit;
    }
}

// Get thread with replies
$thread_data = $community->getForumThreadWithReplies($thread_id);

if (empty($thread_data)) {
    header('Location: forum.php');
    exit;
}

$thread = $thread_data['thread'];
$replies = $thread_data['replies'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($thread['title']); ?> - BRACU Freelance Marketplace</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .thread-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .thread-header {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .thread-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .thread-meta {
            color: #666;
            font-size: 14px;
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .thread-creator {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .thread-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .thread-description {
            color: #444;
            line-height: 1.6;
            font-size: 15px;
        }

        .replies-section {
            margin-top: 30px;
        }

        .replies-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .reply-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
        }

        .reply-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .reply-content {
            flex: 1;
        }

        .reply-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .reply-author {
            font-weight: bold;
            color: #333;
        }

        .reply-time {
            font-size: 12px;
            color: #999;
        }

        .reply-text {
            color: #555;
            line-height: 1.6;
            font-size: 14px;
        }

        .add-reply-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .submit-btn:hover {
            background: #0056b3;
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

        .thread-stats {
            display: flex;
            gap: 20px;
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            margin-left: 10px;
        }

        .badge-pinned {
            background: #ffc107;
            color: #333;
        }

        .badge-locked {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="thread-container">
        <a href="forum.php" class="back-link">← Back to Forum</a>

        <!-- Thread Header -->
        <div class="thread-header">
            <div class="thread-title">
                <?php echo htmlspecialchars($thread['title']); ?>
                <?php if ($thread['is_pinned']): ?>
                    <span class="badge badge-pinned">PINNED</span>
                <?php endif; ?>
                <?php if ($thread['is_locked']): ?>
                    <span class="badge badge-locked">LOCKED</span>
                <?php endif; ?>
            </div>

            <div class="thread-meta">
                <span><?php echo date('M d, Y \a\t H:i', strtotime($thread['created_at'])); ?></span>
                <span>Category: <strong><?php echo htmlspecialchars($thread['category']); ?></strong></span>
            </div>

            <div class="thread-creator">
                <img src="<?php echo htmlspecialchars($thread['avatar_path'] ?? '/assets/uploads/avatars/default.png'); ?>" 
                     alt="<?php echo htmlspecialchars($thread['full_name']); ?>" 
                     class="thread-avatar">
                <div>
                    <div style="font-weight: bold;"><?php echo htmlspecialchars($thread['full_name']); ?></div>
                    <div style="font-size: 12px; color: #999;">Thread Starter</div>
                </div>
            </div>

            <div class="thread-description">
                <?php echo nl2br(htmlspecialchars($thread['description'])); ?>
            </div>

            <div class="thread-stats">
                <span>👁️ <?php echo $thread['view_count']; ?> Views</span>
                <span>💬 <?php echo $thread['reply_count']; ?> Replies</span>
            </div>
        </div>

        <!-- Replies Section -->
        <div class="replies-section">
            <h2 class="replies-title">Replies (<?php echo count($replies); ?>)</h2>

            <?php if (count($replies) > 0): ?>
                <?php foreach ($replies as $reply): ?>
                    <div class="reply-item">
                        <img src="<?php echo htmlspecialchars($reply['avatar_path'] ?? '/assets/uploads/avatars/default.png'); ?>" 
                             alt="<?php echo htmlspecialchars($reply['full_name']); ?>" 
                             class="reply-avatar">
                        <div class="reply-content">
                            <div class="reply-header">
                                <div>
                                    <div class="reply-author"><?php echo htmlspecialchars($reply['full_name']); ?></div>
                                    <div class="reply-time"><?php echo date('M d, Y \a\t H:i', strtotime($reply['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="reply-text">
                                <?php echo nl2br(htmlspecialchars($reply['reply_text'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #999; text-align: center; padding: 20px;">No replies yet. Be the first to reply!</p>
            <?php endif; ?>
        </div>

        <!-- Add Reply Section -->
        <?php if (!$thread['is_locked']): ?>
            <div class="add-reply-section">
                <h3 style="margin-bottom: 15px;">Add Your Reply</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="reply_text">Your Reply *</label>
                        <textarea id="reply_text" name="reply_text" required placeholder="Share your thoughts..."></textarea>
                    </div>

                    <button type="submit" name="add_reply" class="submit-btn">Post Reply</button>
                </form>
            </div>
        <?php else: ?>
            <div class="add-reply-section" style="text-align: center; color: #999;">
                <p>This thread is locked and no new replies can be added.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
