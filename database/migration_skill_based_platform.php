<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$pdo = db();

$steps = [
    'Add User.skills column' => "ALTER TABLE `User` ADD COLUMN `skills` TEXT NULL AFTER `bio`",
    'Set User.credit_balance default to 500' => "ALTER TABLE `User` ALTER COLUMN `credit_balance` SET DEFAULT 500.00",
    'Add Gigs.skill_tags column' => "ALTER TABLE `Gigs` ADD COLUMN `skill_tags` VARCHAR(255) NULL AFTER `LIST_OF_GIGS`",
    'Backfill initial 500 credits to users with low balance' => "UPDATE `User` SET `credit_balance` = 500.00 WHERE `credit_balance` < 500.00",
];

echo "Skill Platform Migration\n";
echo "========================\n\n";

foreach ($steps as $label => $sql) {
    try {
        $pdo->exec($sql);
        echo "[OK] {$label}\n";
    } catch (Throwable $e) {
        $message = $e->getMessage();
        if (str_contains($message, 'Duplicate column name') || str_contains($message, 'already exists')) {
            echo "[SKIP] {$label} (already applied)\n";
            continue;
        }

        
        if (str_contains($label, 'default to 500')) {
            try {
                $pdo->exec("ALTER TABLE `User` MODIFY `credit_balance` DECIMAL(10,2) NOT NULL DEFAULT 500.00");
                echo "[OK] {$label} (fallback syntax)\n";
                continue;
            } catch (Throwable $inner) {
                echo "[WARN] {$label} failed: " . $inner->getMessage() . "\n";
                continue;
            }
        }

        echo "[WARN] {$label} failed: {$message}\n";
    }
}

echo "\nMigration completed.\n";
