<?php




$dbHost = '127.0.0.1';
$dbName = 'bracu_freelance_marketplace';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    
    
    $tables = ['User', 'Forum_Threads', 'Forum_Replies', 'Credit_History', 'messages', 'analytics_activity'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0 ? '✓' : '✗';
        echo "$exists $table\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
