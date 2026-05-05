<?php
/**
 * Database Migration Script - Credit Management System
 * Apply the credit management tables to the database
 */

declare(strict_types=1);

// Use LAMPP's PHP
$dbHost = '127.0.0.1';
$dbName = 'bracu_freelance_marketplace';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Read migration file
    $migrationSql = file_get_contents(__DIR__ . '/migration_add_credit_management.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $migrationSql)),
        fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
    );

    $count = 0;
    foreach ($statements as $statement) {
        // Skip comments and empty lines
        $lines = array_filter(
            array_map('trim', explode("\n", $statement)),
            fn($line) => !empty($line) && !str_starts_with($line, '--')
        );
        
        if (empty($lines)) {
            continue;
        }

        try {
            $pdo->exec($statement);
            $count++;
            echo "✓ Executed statement $count\n";
        } catch (PDOException $e) {
            // Skip if already exists
            if (str_contains($e->getMessage(), 'already exists')) {
                echo "✓ Table already exists (skipped)\n";
            } else {
                echo "⚠ Error: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n✅ Migration completed successfully!\n";
    echo "Tables created: Credit_Topup, Credit_History, Credit_Bonus, Credit_Limit\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
