<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/community.php';

// Require login
$user = require_login();

// Get conversation user ID
$contact_id = $_GET['user'] ?? null;

if (!$contact_id) {
    redirect('messages_inbox.php');
}

$pdo = db();
$community = new Community($pdo);

// Verify contact exists
$query = "SELECT full_name, avatar_path FROM User WHERE BRACU_ID = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$contact_id]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contact) {
    redirect('messages_inbox.php');
}

// Handle message sending
if (is_post_request()) {
    enforce_csrf_or_fail('messages.php?user=' . urlencode($contact_id));

    $message_text = trim($_POST['message_text'] ?? '');
    $gig_id = (int) ($_POST['gig_id'] ?? 0);

    if ($message_text) {
        $community->sendMessage($user['BRACU_ID'], $contact_id, $message_text, $gig_id ?: null);

        // Mark messages as read
        $community->markMessagesAsRead($contact_id, $user['BRACU_ID']);

        redirect('messages.php?user=' . urlencode($contact_id));
    }
}

// Get conversation messages
$messages = $community->getConversation($user['BRACU_ID'], $contact_id);

// Mark messages as read
$community->markMessagesAsRead($contact_id, $user['BRACU_ID']);

// Get user's gigs for context
$query = "SELECT GID, LIST_OF_GIGS FROM Gigs WHERE BRACU_ID = ? LIMIT 10";
$stmt = $pdo->prepare($query);
$stmt->execute([$user['BRACU_ID']]);
$user_gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Chat with ' . $contact['full_name'];

require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="card">
    <div class="kicker">Messaging</div>
    <div class="message-header">
        <a href="messages_inbox.php">← Back to Inbox</a>
        <h1><?= h($contact['full_name']) ?></h1>
        <form method="GET" action="messages.php" class="refresh-form">
            <input type="hidden" name="user" value="<?= urlencode($contact_id) ?>">
            <button type="submit" class="button">🔄 Refresh</button>
        </form>
    </div>

    <!-- Chat Messages -->
    <div class="messages-container">
        <?php if (count($messages) > 0): ?>
            <?php foreach (array_reverse($messages) as $msg): ?>
                <div class="message-row <?= $msg['sender_id'] === $user['BRACU_ID'] ? 'sent' : 'received' ?>">
                    <div class="message-bubble">
                        <?= h($msg['message_text']) ?>
                    </div>
                    <div class="message-time">
                        <?= date('M d H:i', strtotime($msg['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="muted">
                <p style="text-align: center; padding: 2rem;">No messages yet. Start the conversation!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Message Input -->
    <form method="POST" action="" class="message-form">
        <?= csrf_field() ?>
        <textarea name="message_text" placeholder="Type your message..." required class="message-input"></textarea>
        
        <?php if (count($user_gigs) > 0): ?>
            <div>
                <label>Related Gig (Optional):</label>
                <select name="gig_id" class="message-select">
                    <option value="">-- Select a gig --</option>
                    <?php foreach ($user_gigs as $gig): ?>
                        <option value="<?= $gig['GID'] ?>">
                            Gig #<?= $gig['GID'] ?> - <?= h(substr($gig['LIST_OF_GIGS'], 0, 50)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <button type="submit" name="send_message" class="button">Send Message</button>
    </form>
</section>
<?php require_once dirname(__DIR__) . '/includes/footer.php';
