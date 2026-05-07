<?php




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

    
    $stmt = $pdo->query("DESCRIBE User");
    $columns = $stmt->fetchAll();
    echo "User table columns:\n";
    foreach ($columns as $col) {
        if ($col['Field'] === 'BRACU_ID') {
            echo "  " . $col['Field'] . ": " . $col['Type'] . " (Key: " . $col['Key'] . ")\n";
        }
    }

    
    $tables = [
        "CREATE TABLE IF NOT EXISTS `Credit_Topup` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            topup_id VARCHAR(50) NOT NULL UNIQUE,
            BRACU_ID VARCHAR(20) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(20) NOT NULL DEFAULT 'dummy',
            payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            transaction_reference VARCHAR(255) NULL,
            bonus_credits DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            notes TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_topup_user (BRACU_ID),
            INDEX idx_topup_status (payment_status),
            INDEX idx_topup_created (created_at)
        )",
        
        "CREATE TABLE IF NOT EXISTS `Credit_History` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            history_id VARCHAR(50) NOT NULL UNIQUE,
            BRACU_ID VARCHAR(20) NOT NULL,
            transaction_type VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            balance_before DECIMAL(10,2) NOT NULL,
            balance_after DECIMAL(10,2) NOT NULL,
            reference_id VARCHAR(100) NULL,
            gig_id INT NULL,
            description TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'completed',
            initiated_by VARCHAR(20) NULL,
            metadata JSON NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_history_user (BRACU_ID),
            INDEX idx_history_type (transaction_type),
            INDEX idx_history_created (created_at),
            INDEX idx_history_gig (gig_id)
        )",
        
        "CREATE TABLE IF NOT EXISTS `Credit_Bonus` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bonus_id VARCHAR(50) NOT NULL UNIQUE,
            BRACU_ID VARCHAR(20) NOT NULL,
            bonus_amount DECIMAL(10,2) NOT NULL,
            bonus_type VARCHAR(20) NOT NULL,
            reason TEXT NOT NULL,
            expiry_date DATE NULL,
            is_redeemed TINYINT(1) NOT NULL DEFAULT 0,
            redeemed_at TIMESTAMP NULL,
            redeemed_in_topup_id VARCHAR(50) NULL,
            granted_by VARCHAR(20) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_bonus_user (BRACU_ID),
            INDEX idx_bonus_type (bonus_type),
            INDEX idx_bonus_redeemed (is_redeemed)
        )",
        
        "CREATE TABLE IF NOT EXISTS `Credit_Limit` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            BRACU_ID VARCHAR(20) NOT NULL UNIQUE,
            daily_limit DECIMAL(10,2) NOT NULL DEFAULT 100000.00,
            monthly_limit DECIMAL(10,2) NOT NULL DEFAULT 500000.00,
            today_spent DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            month_spent DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            is_restricted TINYINT(1) NOT NULL DEFAULT 0,
            restriction_reason TEXT NULL,
            restricted_until TIMESTAMP NULL,
            last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];

    foreach ($tables as $i => $sql) {
        try {
            $pdo->exec($sql);
            echo "✓ Created table " . ($i + 1) . "/4\n";
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                echo "✓ Table " . ($i + 1) . "/4 already exists\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }

    
    try {
        $pdo->exec("
            INSERT IGNORE INTO `Credit_Limit` (BRACU_ID, daily_limit, monthly_limit)
            SELECT BRACU_ID, 100000.00, 500000.00 FROM User
        ");
        echo "✓ Initialized credit limits for all users\n";
    } catch (PDOException $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }

    
    $stmt = $pdo->query("SHOW TABLES LIKE 'Credit_%'");
    $created_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\n✅ Created tables: " . implode(', ', $created_tables) . "\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
