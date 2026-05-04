<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/community.php';

// Check authentication
if (!isset($_SESSION['user_bracu_id'])) {
    header('Location: ../index.php');
    exit;
}

$pdo = db();
$community = new Community($pdo);

// Get conversation user ID
$contact_id = $_GET['user'] ?? null;

if (!$contact_id) {
    header('Location: messages_inbox.php');
    exit;
}

// Verify contact exists
$query = "SELECT full_name, avatar_path FROM User WHERE BRACU_ID = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$contact_id]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contact) {
    header('Location: messages_inbox.php');
    exit;
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message_text = trim($_POST['message_text'] ?? '');
    $gig_id = (int) ($_POST['gig_id'] ?? 0);

    if ($message_text) {
        $community->sendMessage($_SESSION['user_bracu_id'], $contact_id, $message_text, $gig_id ?: null);

        // Mark messages as read
        $community->markMessagesAsRead($contact_id, $_SESSION['user_bracu_id']);

        header('Location: messages.php?user=' . urlencode($contact_id));
        exit;
    }
}

// Get conversation messages
$messages = $community->getConversation($_SESSION['user_bracu_id'], $contact_id);

// Mark messages as read
$community->markMessagesAsRead($contact_id, $_SESSION['user_bracu_id']);

// Get user's gigs for context
$query = "SELECT GID, LIST_OF_GIGS FROM Gigs WHERE BRACU_ID = ? LIMIT 10";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_bracu_id']]);
$user_gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo htmlspecialchars($contact['full_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .messaging-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 200px);
        }

        .chat-header {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px 8px 0 0;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .contact-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .contact-info {
            flex: 1;
        }

        .contact-name {
            font-weight: bold;
            color: #333;
        }

        .chat-messages {
            flex: 1;
            background: white;
            border: 1px solid #e0e0e0;
            border-top: none;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .message.sent {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .message-content {
            display: flex;
            flex-direction: column;
            gap: 5px;
            max-width: 60%;
        }

        .message.received .message-content {
            align-items: flex-start;
        }

        .message.sent .message-content {
            align-items: flex-end;
        }

        .message-bubble {
            padding: 12px 15px;
            border-radius: 12px;
            word-wrap: break-word;
            line-height: 1.5;
        }

        .message.received .message-bubble {
            background: #f0f0f0;
            color: #333;
        }

        .message.sent .message-bubble {
            background: #007bff;
            color: white;
        }

        .message-time {
            font-size: 12px;
            color: #999;
        }

        .chat-input-area {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 0 0 8px 8px;
            border-top: none;
            padding: 15px 20px;
        }

        .input-form {
            display: flex;
            gap: 10px;
        }

        .message-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            resize: none;
            max-height: 100px;
        }

        .send-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            align-self: flex-end;
        }

        .send-btn:hover {
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

        .gig-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="messaging-container">
        <a href="messages_inbox.php" class="back-link">← Back to Inbox</a>

        <!-- Chat Header -->
        <div class="chat-header">
            <img src="<?php echo htmlspecialchars($contact['avatar_path'] ?? '/assets/uploads/avatars/default.png'); ?>" 
                 alt="<?php echo htmlspecialchars($contact['full_name']); ?>" 
                 class="contact-avatar">
            <div class="contact-info">
                <div class="contact-name"><?php echo htmlspecialchars($contact['full_name']); ?></div>
            </div>
            <a href="profile.php?id=<?php echo urlencode($contact_id); ?>" style="color: #007bff; text-decoration: none;">View Profile</a>
        </div>

        <!-- Chat Messages -->
        <div class="chat-messages">
            <?php if (count($messages) > 0): ?>
                <?php foreach (array_reverse($messages) as $msg): ?>
                    <div class="message <?php echo $msg['sender_id'] === $_SESSION['user_bracu_id'] ? 'sent' : 'received'; ?>">
                        <?php if ($msg['sender_id'] !== $_SESSION['user_bracu_id']): ?>
                            <img src="<?php echo htmlspecialchars($contact['avatar_path'] ?? '/assets/uploads/avatars/default.png'); ?>" 
                                 class="message-avatar">
                        <?php endif; ?>
                        <div class="message-content">
                            <div class="message-bubble">
                                <?php echo htmlspecialchars($msg['message_text']); ?>
                            </div>
                            <div class="message-time">
                                <?php echo date('M d H:i', strtotime($msg['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: #999; margin: auto;">
                    <p>No messages yet. Start the conversation!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Message Input -->
        <div class="chat-input-area">
            <form method="POST" class="input-form">
                <div style="flex: 1;">
                    <textarea name="message_text" class="message-input" placeholder="Type your message..." required></textarea>
                    <?php if (count($user_gigs) > 0): ?>
                        <div style="margin-top: 10px;">
                            <label style="font-size: 12px; color: #666;">Related Gig (Optional):</label>
                            <select name="gig_id" class="gig-select" style="width: 100%;">
                                <option value="">-- Select a gig --</option>
                                <?php foreach ($user_gigs as $gig): ?>
                                    <option value="<?php echo $gig['GID']; ?>">
                                        Gig #<?php echo $gig['GID']; ?> - <?php echo htmlspecialchars(substr($gig['LIST_OF_GIGS'], 0, 50)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" name="send_message" class="send-btn">Send</button>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Auto-scroll to bottom
        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>
