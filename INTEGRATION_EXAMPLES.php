<?php






?>

<!--
================================================================================
                        INTEGRATION CODE SNIPPETS
================================================================================
-->

<?php





?>
require_once '../includes/virtual_economy.php';
$economy = new VirtualEconomy($pdo);


<?php






?>
// After marking gig as done:
if ($gigUpdated) {
    // Award points to freelancer (1 point per credit amount / 4)
    $pointsForFreelancer = (int)floor($gigAmount / 4);
    $economy->awardPoints(
        $freelancerId,
        $pointsForFreelancer,
        'earned',
        $gigId,
        "Completed gig for ৳{$gigAmount}"
    );

    // Award points to client (fixed bonus for using platform)
    $economy->awardPoints(
        $clientId,
        5,
        'earned',
        $gigId,
        'Posted and completed gig'
    );
}


<?php





?>
// Record transaction
$transactionId = $economy->recordTransaction(
    $clientId,              // from_user (payer)
    $freelancerId,          // to_user (receiver)
    'gig_payment',          // transaction_type
    $gigAmount,             // amount
    $gigId,                 // gig_id
    "Payment for gig: $gigTitle"  // description
);


<?php





?>
// Award points for rating
if ($ratingSubmitted) {
    $economy->awardPoints(
        $raterId,
        2,  // 2 points for leaving a rating
        'earned',
        $gigId,
        'Left a rating/review'
    );
}


<?php






?>
// Award points for creating forum thread
if ($threadCreated) {
    $economy->awardPoints(
        $userId,
        3,  // 3 points per thread
        'earned',
        null,
        'Created forum thread'
    );
}

// Award points for forum reply
if ($replyCreated) {
    $economy->awardPoints(
        $userId,
        1,  // 1 point per reply
        'earned',
        null,
        'Posted forum reply'
    );
}


<?php






?>
// In admin/batch_processor.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_batch'])) {
    // Create batch
    $batchId = $economy->createBatch('daily_settlements', $_SESSION['bracu_id']);

    // Process all pending transactions
    $result = $economy->processBatch($batchId);

    if ($result['success']) {
        $message = "Successfully settled {$result['successful']} transactions";
        if ($result['failed'] > 0) {
            $message .= " ({$result['failed']} failed)";
        }
    } else {
        $message = "Error processing batch: " . $result['error'];
    }
}

// Display batch processing form
?>
<form method="POST">
    <button type="submit" name="run_batch" class="btn btn-primary">
        Run Daily Settlements
    </button>
</form>


<?php





?>
// Award bonus to all active users
$stmt = $pdo->prepare("SELECT BRACU_ID FROM User");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $economy->awardPoints(
        $user['BRACU_ID'],
        10,  // 10 bonus points each
        'bonus',
        null,
        'Monthly activity bonus'
    );
}


<?php






?>
function updateUserTier($userId, $economy) {
    $userPoints = $economy->getUserPoints($userId);

    if (!$userPoints) return;

    $currentPoints = $userPoints['lifetime_points'];
    $newTier = 'bronze';

    if ($currentPoints >= 5000) {
        $newTier = 'platinum';
    } elseif ($currentPoints >= 1000) {
        $newTier = 'gold';
    } elseif ($currentPoints >= 500) {
        $newTier = 'silver';
    }

    if ($newTier !== $userPoints['points_tier']) {
        $stmt = $pdo->prepare("
            UPDATE User_Points
            SET points_tier = ?
            WHERE BRACU_ID = ?
        ");
        $stmt->execute([$newTier, $userId]);
    }
}


<?php





?>
<?php
$userPoints = $economy->getUserPoints($userId);
$tierEmojis = ['bronze' => '🥉', 'silver' => '🥈', 'gold' => '🥇', 'platinum' => '💎'];
?>
<span class="badge bg-info">
    <?php echo $tierEmojis[$userPoints['points_tier']]; ?>
    <?php echo ucfirst($userPoints['points_tier']); ?> Member
</span>
<small>(<?php echo $userPoints['total_points']; ?> points)</small>


<?php





?>
<?php

$pointsEarned = (int)floor($creditAmount / 4);
?>
<div class="d-flex justify-content-between align-items-center">
    <div>
        <strong>৳ <?php echo number_format($creditAmount, 2); ?></strong>
        <small class="text-muted d-block">
            💰 + ⭐ <?php echo $pointsEarned; ?> points
        </small>
    </div>
</div>


<?php





?>
<?php

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) as total_transactions,
        SUM(amount) as total_amount,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        SUM(CASE WHEN to_user = ? AND status = 'completed' THEN amount ELSE 0 END) as total_earned
    FROM Transaction_Ledger
    WHERE from_user = ? OR to_user = ?
");
$stmt->execute([$userId, $userId, $userId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<div class="row">
    <div class="col-md-3">
        <h6>Total Transactions</h6>
        <p class="h4"><?php echo $stats['total_transactions']; ?></p>
    </div>
    <div class="col-md-3">
        <h6>Earned</h6>
        <p class="h4 text-success">৳ <?php echo number_format($stats['total_earned'], 2); ?></p>
    </div>
    <div class="col-md-3">
        <h6>Points</h6>
        <p class="h4 text-info">⭐ <?php echo $userPoints['total_points']; ?></p>
    </div>
    <div class="col-md-3">
        <h6>Tier</h6>
        <p class="h4"><?php echo $tierEmojis[$userPoints['points_tier']]; ?></p>
    </div>
</div>


<?php





?>
<?php
function getPointsBonus($gigAmount) {
    $basePoints = (int)floor($gigAmount / 4);
    $bonusMultiplier = 1;

    
    if ($gigAmount >= 500) {
        $bonusMultiplier = 1.5;  
    } elseif ($gigAmount >= 200) {
        $bonusMultiplier = 1.25;  
    }

    return (int)($basePoints * $bonusMultiplier);
}

$bonusPoints = getPointsBonus($creditAmount);
?>
<?php if ($bonusPoints > (int)floor($creditAmount / 4)): ?>
    <span class="badge bg-success">+<?php echo $bonusPoints - (int)floor($creditAmount / 4); ?> Bonus Points!</span>
<?php endif; ?>


<?php





?>
<?php

if ($economy->verifyTransaction($transactionId)) {
    
    $settled = settleTransaction($transactionId);
} else {
    
    error_log("Invalid transaction: $transactionId");
}
?>


<?php





?>
<?php

$ledger = $economy->getTransactionLedger($userId, 1000, 0);


header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="transactions.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Transaction ID', 'From', 'To', 'Type', 'Amount', 'Status', 'Date']);

foreach ($ledger as $txn) {
    fputcsv($output, [
        $txn['transaction_id'],
        $txn['from_user_name'],
        $txn['to_user_name'],
        $txn['transaction_type'],
        $txn['amount'],
        $txn['status'],
        $txn['created_at']
    ]);
}

fclose($output);
?>


<?php





?>
<?php
function checkPointMilestones($userId, $economy) {
    $userPoints = $economy->getUserPoints($userId);
    $milestones = [100, 500, 1000, 5000];

    foreach ($milestones as $milestone) {
        if ($userPoints['lifetime_points'] === $milestone) {
            
            $message = "Congratulations! You've reached $milestone lifetime points! 🎉";
            
        }
    }
}
?>

================================================================================
                        BEST PRACTICES
================================================================================

✅ DO:
- Always require the virtual_economy.php class when needed
- Call updateUserTier() after awarding points
- Use transaction IDs from recordTransaction() for tracking
- Check transaction status before processing payments
- Log errors with error_log() for debugging
- Test with sample data before production
- Batch process transactions daily

❌ DON'T:
- Manually update User credit_balance without using VirtualEconomy class
- Award points without logging activity
- Process transactions without verifying them first
- Trust user input for amounts (always validate/sanitize)
- Update User_Points directly (use awardPoints() instead)
- Process disputed transactions before admin review

================================================================================
                        COMMON PATTERNS
================================================================================

Pattern 1: Complete Gig Flow
1. Mark gig as done (client/my_gigs.php)
2. Record transaction with recordTransaction()
3. Award points to freelancer with awardPoints()
4. Award points to client with awardPoints()
5. Update user tiers with updateUserTier()

Pattern 2: Daily Settlement
1. Create batch with createBatch()
2. Process batch with processBatch()
3. Check results and log errors
4. Send admin report

Pattern 3: Dispute Resolution
1. File dispute with createDispute()
2. Admin reviews in admin/disputes_admin.php
3. Resolve with resolveDispute()
4. If refund: credit returned to user

Pattern 4: Point Redemption
1. User submits redemption form on rewards_system.php
2. redeemPoints() converts points to credit
3. User credit_balance increased
4. Redemption_History logged
5. Points_Activity updated
