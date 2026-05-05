<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/community.php';
require_once dirname(__DIR__) . '/includes/analytics.php';

// Require login
$user = require_login();

// Get conversation user ID
$contact_id = $_GET['user'] ?? null;
$gig_context = (int) ($_GET['gig'] ?? 0);

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

// Validate gig context if provided (must be owned by current user or freelancer)
$gig_info = null;
if ($gig_context > 0) {
    $query = "SELECT g.*, w.BRACU_ID as freelancer_id FROM Gigs g
              LEFT JOIN Working_on w ON w.GID = g.GID
              WHERE g.GID = ? AND (g.BRACU_ID = ? OR w.BRACU_ID = ?)
              LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$gig_context, $user['BRACU_ID'], $user['BRACU_ID']]);
    $gig_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle message sending
if (is_post_request()) {
    enforce_csrf_or_fail('messages.php?user=' . urlencode($contact_id));

    $message_text = trim($_POST['message_text'] ?? '');
    $gig_id = (int) ($_POST['gig_id'] ?? 0);

    if ($message_text) {
        $community->sendMessage($user['BRACU_ID'], $contact_id, $message_text, $gig_id ?: null);

        // Track message send
        $analytics = new Analytics($pdo);
        $analytics->logActivity($user['BRACU_ID'], 'message_send', $gig_id ?: null, $contact_id);

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
        
        <!-- Gig Context Info -->
        <?php if ($gig_info): ?>
            <div style="background: #f0f4f8; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border-left: 3px solid #667eea;">
                <strong>📋 Related Gig:</strong> Gig #<?= (int) $gig_info['GID'] ?> - <?= h(substr($gig_info['LIST_OF_GIGS'], 0, 60)) ?><?= strlen($gig_info['LIST_OF_GIGS']) > 60 ? '...' : '' ?><br>
                <small style="color: #666;">Category: <?= h($gig_info['CATAGORY']) ?> | Deadline: <?= h($gig_info['DEADLINE']) ?></small>
            </div>
        <?php endif; ?>
        
        <textarea name="message_text" placeholder="Type your message..." required class="message-input"></textarea>
        
        <?php if (count($user_gigs) > 0): ?>
            <div>
                <label>Related Gig (Optional):</label>
                <select name="gig_id" class="message-select">
                    <option value="">-- Select a gig --</option>
                    <?php foreach ($user_gigs as $gig): ?>
                        <option value="<?= $gig['GID'] ?>" <?= $gig_info && $gig_info['GID'] == $gig['GID'] ? 'selected' : '' ?>>
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
