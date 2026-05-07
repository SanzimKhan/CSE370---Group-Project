<?php




declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$pdo = db();
$testUser = '20101001';

echo "Debug: Credit Balance Update Check\n";
echo "===================================\n\n";


$stmt = $pdo->prepare('SELECT BRACU_ID, credit_balance FROM User WHERE BRACU_ID = :id');
$stmt->execute(['id' => $testUser]);
$user = $stmt->fetch();

echo "User: " . $user['BRACU_ID'] . "\n";
echo "Current balance before: ৳" . number_format((float)$user['credit_balance'], 2) . "\n\n";


try {
    $pdo->beginTransaction();
    
    
    $stmt = $pdo->prepare('SELECT credit_balance FROM User WHERE BRACU_ID = :id FOR UPDATE');
    $stmt->execute(['id' => $testUser]);
    $balance_before = (float)($stmt->fetchColumn() ?? 0);
    echo "Balance retrieved with lock: ৳" . number_format($balance_before, 2) . "\n";
    
    
    $stmt = $pdo->prepare('UPDATE User SET credit_balance = credit_balance + 1000 WHERE BRACU_ID = :id');
    $stmt->execute(['id' => $testUser]);
    echo "Update executed\n";
    
    
    $stmt = $pdo->prepare('SELECT credit_balance FROM User WHERE BRACU_ID = :id');
    $stmt->execute(['id' => $testUser]);
    $balance_after = (float)($stmt->fetchColumn() ?? 0);
    echo "Balance after update: ৳" . number_format($balance_after, 2) . "\n";
    
    $pdo->commit();
    echo "Transaction committed\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}


$stmt = $pdo->prepare('SELECT credit_balance FROM User WHERE BRACU_ID = :id');
$stmt->execute(['id' => $testUser]);
$final_balance = (float)($stmt->fetchColumn() ?? 0);
echo "\nFinal balance in database: ৳" . number_format($final_balance, 2) . "\n";


echo "\n\nCredit History:\n";
$stmt = $pdo->prepare('SELECT * FROM Credit_History WHERE BRACU_ID = :id ORDER BY created_at DESC LIMIT 5');
$stmt->execute(['id' => $testUser]);
$history = $stmt->fetchAll();
foreach ($history as $h) {
    echo "- " . $h['transaction_type'] . ": ৳" . number_format((float)$h['amount'], 2) . " → Balance: ৳" . number_format((float)$h['balance_after'], 2) . "\n";
}
?>
