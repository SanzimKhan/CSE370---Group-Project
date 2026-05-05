<?php
/**
 * Check database users and their credits
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$pdo = db();

echo "Database User Check\n";
echo "===================\n\n";

$stmt = $pdo->query('SELECT BRACU_ID, Bracu_mail, credit_balance FROM User LIMIT 10');
$users = $stmt->fetchAll();

echo "Total users found: " . count($users) . "\n\n";

foreach ($users as $user) {
    echo "BRACU_ID: " . $user['BRACU_ID'] . "\n";
    echo "Email: " . $user['Bracu_mail'] . "\n";
    echo "Balance: ৳" . number_format((float)$user['credit_balance'], 2) . "\n\n";
}

// Check credit tables
echo "\n\nCredit System Tables Status\n";
echo "===========================\n\n";

$tables = ['Credit_Topup', 'Credit_History', 'Credit_Bonus', 'Credit_Limit'];
foreach ($tables as $table) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table");
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();
    echo "$table: $count records\n";
}

// Check if first user has history
if (!empty($users)) {
    $firstUser = $users[0]['BRACU_ID'];
    echo "\n\nHistory for user $firstUser:\n";
    $stmt = $pdo->prepare('SELECT transaction_type, amount, balance_after, created_at FROM Credit_History WHERE BRACU_ID = ? ORDER BY created_at DESC LIMIT 5');
    $stmt->execute([$firstUser]);
    $history = $stmt->fetchAll();
    
    foreach ($history as $h) {
        echo "- " . date('Y-m-d H:i:s', strtotime($h['created_at'])) . ": " . $h['transaction_type'] . " ৳" . number_format((float)$h['amount'], 2) . " (balance: ৳" . number_format((float)$h['balance_after'], 2) . ")\n";
    }
}
?>
