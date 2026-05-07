<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/community.php';


$user = require_login();
$pageTitle = 'Messages';

$pdo = db();
$community = new Community($pdo);


$conversations = $community->getUserConversations($user['BRACU_ID']);


$unread_count = $community->getUnreadMessageCount($user['BRACU_ID']);

require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="card">
    <div class="kicker">Community</div>
    <h1>Messages</h1>
    <?php if ($unread_count > 0): ?>
        <p class="muted"><?= $unread_count ?> unread message<?= $unread_count !== 1 ? 's' : '' ?></p>
    <?php else: ?>
        <p class="muted">Your message inbox</p>
    <?php endif; ?>

    <!-- Conversations List -->
    <?php if (count($conversations) > 0): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Last Message</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($conversations as $conv): ?>
                        <tr>
                            <td><?= h($conv['full_name']) ?></td>
                            <td><?= h(substr($conv['last_message'], 0, 60)) ?></td>
                            <td><?= date('M d H:i', strtotime($conv['last_message_time'])) ?></td>
                            <td>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="badge-unread"><?= $conv['unread_count'] ?> unread</span>
                                <?php else: ?>
                                    <span class="muted">Read</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="messages.php?user=<?= urlencode($conv['contact_id']) ?>" class="button-secondary">Open</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="muted" style="text-align: center; padding: 2rem;">No messages yet. Start a conversation by messaging someone!</p>
    <?php endif; ?>
</section>
<?php require_once dirname(__DIR__) . '/includes/footer.php';
