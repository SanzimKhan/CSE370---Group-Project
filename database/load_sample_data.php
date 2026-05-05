<?php
/**
 * Load sample/test data into the database
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

    // Read sample data file
    $sampleDataFile = __DIR__ . '/sample_data.sql';
    if (!file_exists($sampleDataFile)) {
        echo "❌ sample_data.sql not found\n";
        exit(1);
    }

    $sqlContent = file_get_contents($sampleDataFile);
    
    // Split by semicolon and filter
    $statements = array_filter(
        array_map('trim', explode(';', $sqlContent)),
        fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
    );

    $count = 0;
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $count++;
            echo "✓ Executed statement $count\n";
        } catch (PDOException $e) {
            echo "⚠ Error: " . $e->getMessage() . "\n";
        }
    }

    echo "\n✅ Sample data loaded successfully!\n";
    echo "Total statements executed: $count\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
