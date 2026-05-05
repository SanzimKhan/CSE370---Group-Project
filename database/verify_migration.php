<?php
/**
 * Database Migration Verification Script
 */

declare(strict_types=1);

$dbHost = '127.0.0.1';
$dbName = 'bracu_freelance_marketplace';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Check which tables exist
    $tables = ['Credit_Topup', 'Credit_History', 'Credit_Bonus', 'Credit_Limit'];
    
    echo "Checking tables...\n";
    echo "==================\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = (bool)$stmt->fetchColumn();
        echo ($exists ? '✓' : '✗') . " $table\n";
        
        if ($exists) {
            $stmt = $pdo->prepare("DESCRIBE $table");
            $stmt->execute();
            $columns = $stmt->fetchAll();
            echo "  Columns: " . count($columns) . "\n";
        }
    }
    
    // Get user count
    $stmt = $pdo->query("SELECT COUNT(*) FROM User");
    $userCount = (int)$stmt->fetchColumn();
    echo "\nTotal users in database: $userCount\n";
    
    // Check for credit_balance column
    $stmt = $pdo->query("DESCRIBE User");
    $columns = $stmt->fetchAll();
    $hasCreditBalance = (bool)array_filter($columns, fn($c) => $c['Field'] === 'credit_balance');
    echo ($hasCreditBalance ? '✓' : '✗') . " User.credit_balance exists\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
