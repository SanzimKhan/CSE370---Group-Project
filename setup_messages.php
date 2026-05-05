<?php
/**
 * Script to insert dummy message data for testing
 * This file inserts test conversations between users
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Allow anyone logged in for demo purposes
$user = current_user();
// Removed admin check for development

try {
    $pdo = db();
    
    // Delete existing messages for this demo
    $pdo->exec("DELETE FROM Messages");
    
    // Check how many we deleted
    $messages_deleted = $pdo->exec("DELETE FROM Messages");  // Will be -1 if no rows, but that's ok
    
    // Insert dummy messages using prepared statements with date manipulation in PHP
    $messages = [
        // User 20101001 and 20101600
        ['20101001', '20101600', 'Hi there! How are you doing?', 1, '-5 days'],
        ['20101600', '20101001', 'I am doing great! How about you?', 1, '-5 days'],
        ['20101001', '20101600', 'Just finished a gig. Great client!', 0, '-4 days'],
        
        // User 20101002 and 20101600
        ['20101002', '20101600', 'Are you interested in this gig?', 1, '-3 days'],
        ['20101600', '20101002', 'Yes! I would like to know more', 1, '-3 days'],
        ['20101002', '20101600', 'Great! Let me send you the details', 0, '-2 days'],
        ['20101002', '20101600', 'The deadline is next Friday', 0, '-1 days'],
        
        // User 20101003 and 20101600
        ['20101003', '20101600', 'Hello! Want to collaborate?', 1, '-2 days'],
        ['20101600', '20101003', 'Sure! What did you have in mind?', 1, '-2 days'],
        ['20101003', '20101600', 'Check out my profile for details', 0, '-1 days'],
        
        // More messages
        ['20101600', '20101001', 'When is the next project starting?', 1, '-1 days'],
        ['20101001', '20101600', 'Next week. I will update you soon!', 0, '-12 hours'],
        ['20101002', '20101600', 'I completed my work on the gig', 1, '-2 hours'],
        ['20101600', '20101002', 'Awesome! Let me review and release payment', 1, '-1 hours'],
        ['20101003', '20101600', 'Gig completed successfully!', 1, '-30 minutes'],
        ['20101600', '20101003', 'Perfect! Payment processed. Thanks!', 0, 'now'],
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO Messages (sender_id, recipient_id, message_text, is_read, created_at)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($messages as $msg) {
        if ($msg[4] === 'now') {
            $created_at = date('Y-m-d H:i:s');
        } else {
            $created_at = date('Y-m-d H:i:s', strtotime($msg[4]));
        }
        $stmt->execute([
            $msg[0], // sender_id
            $msg[1], // recipient_id
            $msg[2], // message_text
            $msg[3], // is_read
            $created_at  // created_at
        ]);
    }
    
    echo "<h2>Success!</h2>";
    echo "<p>Inserted " . count($messages) . " dummy messages to the database.</p>";
    echo "<p><a href='community/messages_inbox.php'>Go to Messages Inbox</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>Error</h2>";
    echo "<p>Error inserting messages: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit(1);
}
