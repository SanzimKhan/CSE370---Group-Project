<?php




declare(strict_types=1);

$dbHost = '127.0.0.1';
$dbName = 'bracu_freelance_marketplace';
$dbUser = 'root';
$dbPass = '';

try {
    $connection = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }

    
    $sql1 = "CREATE TABLE IF NOT EXISTS `Forum_Threads` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        creator_id VARCHAR(20) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        category ENUM('General', 'Tips', 'Help', 'Showcase') NOT NULL DEFAULT 'General',
        view_count INT NOT NULL DEFAULT 0,
        reply_count INT NOT NULL DEFAULT 0,
        is_pinned TINYINT(1) NOT NULL DEFAULT 0,
        is_locked TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_thread_creator (creator_id),
        CONSTRAINT fk_thread_creator FOREIGN KEY (creator_id)
            REFERENCES `User` (BRACU_ID)
            ON UPDATE CASCADE
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($connection->query($sql1)) {
        echo "✓ Created Forum_Threads table\n";
    } else {
        echo "✗ Forum_Threads: " . $connection->error . "\n";
    }

    
    $sql2 = "CREATE TABLE IF NOT EXISTS `Forum_Replies` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        thread_id INT NOT NULL,
        author_id VARCHAR(20) NOT NULL,
        reply_text TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_reply_thread (thread_id),
        KEY idx_reply_author (author_id),
        CONSTRAINT fk_reply_thread FOREIGN KEY (thread_id)
            REFERENCES `Forum_Threads` (id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
        CONSTRAINT fk_reply_author FOREIGN KEY (author_id)
            REFERENCES `User` (BRACU_ID)
            ON UPDATE CASCADE
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($connection->query($sql2)) {
        echo "✓ Created Forum_Replies table\n";
    } else {
        echo "✗ Forum_Replies: " . $connection->error . "\n";
    }

    echo "\n✅ Forum tables setup complete!\n";
    $connection->close();

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
