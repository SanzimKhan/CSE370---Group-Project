<?php





require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = db();
    
    
    $check = $pdo->query("SELECT COUNT(*) as count FROM Messages");
    $result = $check->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "Messages already exist in database. Skipping insertion.\n";
        exit;
    }
    
    
    $messages = [
        
        ['20101001', '20101600', 'Hi there! How are you doing?', 1, 'NOW() - INTERVAL 5 DAY'],
        ['20101600', '20101001', 'I am doing great! How about you?', 1, 'NOW() - INTERVAL 5 DAY'],
        ['20101001', '20101600', 'Just finished a gig. Great client!', 0, 'NOW() - INTERVAL 4 DAY'],
        
        
        ['20101002', '20101600', 'Are you interested in this gig?', 1, 'NOW() - INTERVAL 3 DAY'],
        ['20101600', '20101002', 'Yes! I would like to know more', 1, 'NOW() - INTERVAL 3 DAY'],
        ['20101002', '20101600', 'Great! Let me send you the details', 0, 'NOW() - INTERVAL 2 DAY'],
        ['20101002', '20101600', 'The deadline is next Friday', 0, 'NOW() - INTERVAL 1 DAY'],
        
        
        ['20101003', '20101600', 'Hello! Want to collaborate?', 1, 'NOW() - INTERVAL 2 DAY'],
        ['20101600', '20101003', 'Sure! What did you have in mind?', 1, 'NOW() - INTERVAL 2 DAY'],
        ['20101003', '20101600', 'Check out my profile for details', 0, 'NOW() - INTERVAL 1 DAY'],
        
        
        ['20101600', '20101001', 'When is the next project starting?', 1, 'NOW() - INTERVAL 1 DAY'],
        ['20101001', '20101600', 'Next week. I will update you soon!', 0, 'NOW() - INTERVAL 12 HOUR'],
        ['20101002', '20101600', 'I completed my work on the gig', 1, 'NOW() - INTERVAL 2 HOUR'],
        ['20101600', '20101002', 'Awesome! Let me review and release payment', 1, 'NOW() - INTERVAL 1 HOUR'],
        ['20101003', '20101600', 'Gig completed successfully!', 1, 'NOW() - INTERVAL 30 MINUTE'],
        ['20101600', '20101003', 'Perfect! Payment processed. Thanks!', 0, 'NOW()'],
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO Messages (sender_id, recipient_id, message_text, is_read, created_at)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($messages as $msg) {
        $stmt->execute([
            $msg[0], 
            $msg[1], 
            $msg[2], 
            $msg[3], 
            $msg[4]  
        ]);
    }
    
    echo "Successfully inserted " . count($messages) . " dummy messages!\n";
    
} catch (PDOException $e) {
    echo "Error inserting messages: " . $e->getMessage() . "\n";
    exit(1);
}
