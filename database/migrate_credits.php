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

    $createStatements = [
        
        "CREATE TABLE IF NOT EXISTS `Credit_Topup` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            topup_id VARCHAR(50) NOT NULL UNIQUE,
            BRACU_ID VARCHAR(20) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('credit_card', 'bkash', 'nagad', 'rocket', 'dummy') NOT NULL DEFAULT 'dummy',
            payment_status ENUM('pending', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
            transaction_reference VARCHAR(255) NULL,
            bonus_credits DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            notes TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_topup_user FOREIGN KEY (BRACU_ID) REFERENCES User(BRACU_ID) ON DELETE CASCADE,
            INDEX idx_topup_user (BRACU_ID),
            INDEX idx_topup_status (payment_status),
            INDEX idx_topup_created (created_at),
            INDEX idx_topup_reference (transaction_reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        
        "CREATE TABLE IF NOT EXISTS `Credit_History` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            history_id VARCHAR(50) NOT NULL UNIQUE,
            BRACU_ID VARCHAR(20) NOT NULL,
            transaction_type ENUM('topup', 'debit', 'refund', 'bonus', 'gig_payment', 'dispute_refund') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            balance_before DECIMAL(10,2) NOT NULL,
            balance_after DECIMAL(10,2) NOT NULL,
            reference_id VARCHAR(100) NULL,
            gig_id INT NULL,
            description TEXT NOT NULL,
            status ENUM('pending', 'completed', 'failed', 'reversed') NOT NULL DEFAULT 'completed',
            initiated_by VARCHAR(20) NULL,
            metadata JSON NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_history_user FOREIGN KEY (BRACU_ID) REFERENCES User(BRACU_ID) ON DELETE CASCADE,
            INDEX idx_history_user (BRACU_ID),
            INDEX idx_history_type (transaction_type),
            INDEX idx_history_created (created_at),
            INDEX idx_history_gig (gig_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        
        "CREATE TABLE IF NOT EXISTS `Credit_Bonus` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bonus_id VARCHAR(50) NOT NULL UNIQUE,
            BRACU_ID VARCHAR(20) NOT NULL,
            bonus_amount DECIMAL(10,2) NOT NULL,
            bonus_type ENUM('signup', 'referral', 'promotion', 'adjustment') NOT NULL,
            reason TEXT NOT NULL,
            expiry_date DATE NULL,
            is_redeemed TINYINT(1) NOT NULL DEFAULT 0,
            redeemed_at TIMESTAMP NULL,
            redeemed_in_topup_id VARCHAR(50) NULL,
            granted_by VARCHAR(20) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_bonus_user FOREIGN KEY (BRACU_ID) REFERENCES User(BRACU_ID) ON DELETE CASCADE,
            INDEX idx_bonus_user (BRACU_ID),
            INDEX idx_bonus_type (bonus_type),
            INDEX idx_bonus_redeemed (is_redeemed)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        
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
            last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_limit_user FOREIGN KEY (BRACU_ID) REFERENCES User(BRACU_ID) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    ];

    foreach ($createStatements as $i => $sql) {
        try {
            $pdo->exec($sql);
            echo "✓ Created table " . ($i + 1) . "/4\n";
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                echo "✓ Table " . ($i + 1) . "/4 already exists\n";
            } else {
                echo "✗ Error creating table " . ($i + 1) . ": " . $e->getMessage() . "\n";
            }
        }
    }

    
    try {
        $pdo->exec("
            INSERT INTO `Credit_Limit` (BRACU_ID, daily_limit, monthly_limit)
            SELECT BRACU_ID, 100000.00, 500000.00 FROM User
            WHERE BRACU_ID NOT IN (SELECT BRACU_ID FROM Credit_Limit)
        ");
        $count = $pdo->exec("SELECT COUNT(*) FROM Credit_Limit");
        echo "✓ Initialized credit limits\n";
    } catch (PDOException $e) {
        echo "⚠ Could not initialize limits: " . $e->getMessage() . "\n";
    }

    
    try {
        $pdo->exec("
            INSERT IGNORE INTO `Credit_Bonus` (bonus_id, BRACU_ID, bonus_amount, bonus_type, reason, granted_by)
            SELECT 
                CONCAT('BONUS-SIGNUP-', BRACU_ID, '-', UNIX_TIMESTAMP()),
                BRACU_ID,
                100.00,
                'signup',
                'Initial signup bonus',
                'system'
            FROM User
            WHERE BRACU_ID NOT IN (SELECT BRACU_ID FROM Credit_Bonus WHERE bonus_type = 'signup')
        ");
        echo "✓ Granted signup bonuses\n";
    } catch (PDOException $e) {
        echo "⚠ Could not grant bonuses: " . $e->getMessage() . "\n";
    }

    echo "\n✅ Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
