<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';


function get_user_credit_balance(string $bracu_id): float
{
    $stmt = db()->prepare('SELECT credit_balance FROM `User` WHERE BRACU_ID = :id');
    $stmt->execute(['id' => $bracu_id]);
    $result = $stmt->fetchColumn();
    
    return $result !== false ? (float) $result : 0.00;
}




function add_credits(string $bracu_id, float $amount, string $type, ?string $reference_id = null, ?int $gig_id = null, string $description = ''): array
{
    if ($amount <= 0) {
        return ['ok' => false, 'message' => 'Invalid credit amount.'];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        
        $stmt = $pdo->prepare('SELECT credit_balance FROM `User` WHERE BRACU_ID = :id FOR UPDATE');
        $stmt->execute(['id' => $bracu_id]);
        $balance_before = (float) ($stmt->fetchColumn() ?? 0);

        
        $stmt = $pdo->prepare('UPDATE `User` SET credit_balance = credit_balance + :amount WHERE BRACU_ID = :id');
        $stmt->execute(['amount' => $amount, 'id' => $bracu_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'User not found for credit update.'];
        }

        $balance_after = $balance_before + $amount;

        
        $history_id = generate_history_id();
        $stmt = $pdo->prepare(
            'INSERT INTO `Credit_History` (history_id, BRACU_ID, transaction_type, amount, balance_before, balance_after, reference_id, gig_id, description)
             VALUES (:history_id, :bracu_id, :type, :amount, :balance_before, :balance_after, :reference_id, :gig_id, :description)'
        );
        $stmt->execute([
            'history_id' => $history_id,
            'bracu_id' => $bracu_id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balance_before,
            'balance_after' => $balance_after,
            'reference_id' => $reference_id,
            'gig_id' => $gig_id,
            'description' => $description ?: "Added {$amount} credits"
        ]);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => "Successfully added {$amount} credits.",
            'balance' => $balance_after,
            'history_id' => $history_id
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error adding credits: " . $e->getMessage());
        return ['ok' => false, 'message' => 'Failed to add credits. Please try again.'];
    }
}




function deduct_credits(string $bracu_id, float $amount, string $type, ?string $reference_id = null, ?int $gig_id = null, string $description = ''): array
{
    if ($amount <= 0) {
        return ['ok' => false, 'message' => 'Invalid credit amount.'];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        
        $stmt = $pdo->prepare('SELECT credit_balance FROM `User` WHERE BRACU_ID = :id FOR UPDATE');
        $stmt->execute(['id' => $bracu_id]);
        $balance_before = (float) ($stmt->fetchColumn() ?? 0);

        
        if ($balance_before === 0.0) {
            $userCheck = $pdo->prepare('SELECT BRACU_ID FROM `User` WHERE BRACU_ID = :id LIMIT 1');
            $userCheck->execute(['id' => $bracu_id]);
            if (!$userCheck->fetchColumn()) {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'User not found for credit deduction.'];
            }
        }

        if ($balance_before < $amount) {
            $pdo->rollBack();
            return [
                'ok' => false, 
                'message' => 'Insufficient credits. Required: ' . $amount . ', Available: ' . $balance_before,
                'required' => $amount,
                'available' => $balance_before
            ];
        }

        
        $stmt = $pdo->prepare('UPDATE `User` SET credit_balance = credit_balance - :amount WHERE BRACU_ID = :id');
        $stmt->execute(['amount' => $amount, 'id' => $bracu_id]);

        $balance_after = $balance_before - $amount;

        
        $history_id = generate_history_id();
        $stmt = $pdo->prepare(
            'INSERT INTO `Credit_History` (history_id, BRACU_ID, transaction_type, amount, balance_before, balance_after, reference_id, gig_id, description)
             VALUES (:history_id, :bracu_id, :type, :amount, :balance_before, :balance_after, :reference_id, :gig_id, :description)'
        );
        $stmt->execute([
            'history_id' => $history_id,
            'bracu_id' => $bracu_id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balance_before,
            'balance_after' => $balance_after,
            'reference_id' => $reference_id,
            'gig_id' => $gig_id,
            'description' => $description ?: "Deducted {$amount} credits"
        ]);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => "Successfully deducted {$amount} credits.",
            'balance' => $balance_after,
            'history_id' => $history_id
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error deducting credits: " . $e->getMessage());
        return ['ok' => false, 'message' => 'Failed to deduct credits. Please try again.'];
    }
}




function transfer_credits(string $from_user, string $to_user, float $amount, string $reason = ''): array
{
    if ($amount <= 0) {
        return ['ok' => false, 'message' => 'Invalid transfer amount.'];
    }

    if ($from_user === $to_user) {
        return ['ok' => false, 'message' => 'Cannot transfer credits to yourself.'];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        
        $stmt = $pdo->prepare('SELECT credit_balance FROM `User` WHERE BRACU_ID = :id FOR UPDATE');
        $stmt->execute(['id' => $from_user]);
        $from_balance = (float) ($stmt->fetchColumn() ?? 0);

        if ($from_balance < $amount) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Insufficient credits for transfer.'];
        }

        
        $stmt = $pdo->prepare('SELECT BRACU_ID FROM `User` WHERE BRACU_ID = :id FOR UPDATE');
        $stmt->execute(['id' => $to_user]);
        if (!$stmt->fetchColumn()) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Recipient not found.'];
        }

        $stmt = $pdo->prepare('SELECT credit_balance FROM `User` WHERE BRACU_ID = :id FOR UPDATE');
        $stmt->execute(['id' => $to_user]);
        $to_balance = (float) ($stmt->fetchColumn() ?? 0);

        
        $stmt = $pdo->prepare('UPDATE `User` SET credit_balance = credit_balance - :amount WHERE BRACU_ID = :id');
        $stmt->execute(['amount' => $amount, 'id' => $from_user]);

        
        $stmt = $pdo->prepare('UPDATE `User` SET credit_balance = credit_balance + :amount WHERE BRACU_ID = :id');
        $stmt->execute(['amount' => $amount, 'id' => $to_user]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Recipient update failed.'];
        }

        
        $ref_id = 'TRF-' . time() . '-' . mt_rand(1000, 9999);

        $stmt = $pdo->prepare(
            'INSERT INTO `Credit_History` (history_id, BRACU_ID, transaction_type, amount, balance_before, balance_after, reference_id, description)
             VALUES (:history_id, :bracu_id, :type, :amount, :balance_before, :balance_after, :reference_id, :description)'
        );
        $stmt->execute([
            'history_id' => generate_history_id(),
            'bracu_id' => $from_user,
            'type' => 'debit',
            'amount' => $amount,
            'balance_before' => $from_balance,
            'balance_after' => $from_balance - $amount,
            'reference_id' => $ref_id,
            'description' => "Transfer to $to_user: $reason"
        ]);

        $stmt->execute([
            'history_id' => generate_history_id(),
            'bracu_id' => $to_user,
            'type' => 'bonus',
            'amount' => $amount,
            'balance_before' => $to_balance,
            'balance_after' => $to_balance + $amount,
            'reference_id' => $ref_id,
            'description' => "Transfer from $from_user: $reason"
        ]);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => "Successfully transferred {$amount} credits.",
            'reference_id' => $ref_id
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error transferring credits: " . $e->getMessage());
        return ['ok' => false, 'message' => 'Failed to transfer credits.'];
    }
}








function create_topup_request(string $bracu_id, float $amount, string $payment_method = 'dummy'): array
{
    if ($amount <= 0) {
        return ['ok' => false, 'message' => 'Invalid top-up amount.'];
    }

    if ($amount > 1000000) {
        return ['ok' => false, 'message' => 'Top-up amount exceeds maximum limit (1,000,000).'];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $topup_id = generate_topup_id();
        
        $stmt = $pdo->prepare(
            'INSERT INTO `Credit_Topup` (topup_id, BRACU_ID, amount, payment_method, payment_status, ip_address, user_agent)
             VALUES (:topup_id, :bracu_id, :amount, :method, :status, :ip, :ua)'
        );
        $stmt->execute([
            'topup_id' => $topup_id,
            'bracu_id' => $bracu_id,
            'amount' => $amount,
            'method' => $payment_method,
            'status' => 'pending',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => 'Top-up request created. Proceed to payment.',
            'topup_id' => $topup_id,
            'amount' => $amount
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error creating topup: " . $e->getMessage());
        return ['ok' => false, 'message' => 'Failed to create top-up request.'];
    }
}




function process_dummy_payment(string $topup_id): array
{
    $pdo = db();
    try {
        $pdo->beginTransaction();

        
        $stmt = $pdo->prepare('SELECT * FROM `Credit_Topup` WHERE topup_id = :id FOR UPDATE');
        $stmt->execute(['id' => $topup_id]);
        $topup = $stmt->fetch();

        if (!$topup) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Top-up request not found.'];
        }

        if ($topup['payment_status'] !== 'pending') {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'This top-up has already been processed.'];
        }

        
        $success = (mt_rand(0, 100) <= 95);
        
        if (!$success) {
            $stmt = $pdo->prepare('UPDATE `Credit_Topup` SET payment_status = :status, updated_at = NOW() WHERE topup_id = :id');
            $stmt->execute(['status' => 'failed', 'id' => $topup_id]);
            $pdo->commit();
            return ['ok' => false, 'message' => 'Payment processing failed. Please retry.'];
        }

        
        $txn_ref = 'TXN-' . time() . '-' . strtoupper(bin2hex(random_bytes(3)));

        
        $bonus = round($topup['amount'] * 0.05, 2);

        
        $stmt = $pdo->prepare('SELECT credit_balance FROM `User` WHERE BRACU_ID = :id FOR UPDATE');
        $stmt->execute(['id' => $topup['BRACU_ID']]);
        $balance_before = (float) ($stmt->fetchColumn() ?? 0);

        
        $stmt = $pdo->prepare(
            'UPDATE `Credit_Topup` 
             SET payment_status = :status, transaction_reference = :ref, bonus_credits = :bonus, completed_at = NOW(), updated_at = NOW() 
             WHERE topup_id = :id'
        );
        $stmt->execute([
            'status' => 'completed',
            'ref' => $txn_ref,
            'bonus' => $bonus,
            'id' => $topup_id
        ]);

        
        $total_credits = $topup['amount'] + $bonus;
        $stmt = $pdo->prepare('UPDATE `User` SET credit_balance = credit_balance + :amount WHERE BRACU_ID = :id');
        $stmt->execute(['amount' => $total_credits, 'id' => $topup['BRACU_ID']]);

        $balance_after = $balance_before + $total_credits;

        
        $history_id = generate_history_id();
        $stmt = $pdo->prepare(
            'INSERT INTO `Credit_History` (history_id, BRACU_ID, transaction_type, amount, balance_before, balance_after, reference_id, description)
             VALUES (:history_id, :bracu_id, :type, :amount, :balance_before, :balance_after, :reference_id, :description)'
        );
        $stmt->execute([
            'history_id' => $history_id,
            'bracu_id' => $topup['BRACU_ID'],
            'type' => 'topup',
            'amount' => $total_credits,
            'balance_before' => $balance_before,
            'balance_after' => $balance_after,
            'reference_id' => $topup_id,
            'description' => "Top-up via {$topup['payment_method']} (+ {$bonus} bonus)"
        ]);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => "Payment successful! {$total_credits} credits added ({$bonus} bonus included).",
            'topup_id' => $topup_id,
            'transaction_reference' => $txn_ref,
            'amount' => $topup['amount'],
            'bonus' => $bonus,
            'total' => $total_credits,
            'new_balance' => $balance_after
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error processing payment: " . $e->getMessage());
        return ['ok' => false, 'message' => 'Payment processing error. Please contact support.'];
    }
}




function get_topup_details(string $topup_id): ?array
{
    $stmt = db()->prepare('SELECT * FROM `Credit_Topup` WHERE topup_id = :id');
    $stmt->execute(['id' => $topup_id]);
    return $stmt->fetch() ?: null;
}




function get_user_topup_history(string $bracu_id, int $limit = 20, int $offset = 0): array
{
    $stmt = db()->prepare(
        'SELECT * FROM `Credit_Topup` 
         WHERE BRACU_ID = :bracu_id 
         ORDER BY created_at DESC 
         LIMIT :limit OFFSET :offset'
    );
    $stmt->execute(['bracu_id' => $bracu_id, 'limit' => $limit, 'offset' => $offset]);
    return $stmt->fetchAll() ?: [];
}








function get_credit_history(string $bracu_id, int $limit = 50, int $offset = 0, ?string $type = null): array
{
    if ($type) {
        $stmt = db()->prepare(
            'SELECT * FROM `Credit_History` 
             WHERE BRACU_ID = :bracu_id AND transaction_type = :type
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset'
        );
        $stmt->execute(['bracu_id' => $bracu_id, 'type' => $type, 'limit' => $limit, 'offset' => $offset]);
    } else {
        $stmt = db()->prepare(
            'SELECT * FROM `Credit_History` 
             WHERE BRACU_ID = :bracu_id
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset'
        );
        $stmt->execute(['bracu_id' => $bracu_id, 'limit' => $limit, 'offset' => $offset]);
    }
    return $stmt->fetchAll() ?: [];
}




function get_credit_summary(string $bracu_id): array
{
    $pdo = db();
    
    $balance = get_user_credit_balance($bracu_id);
    
    
    $stmt = $pdo->prepare(
        'SELECT SUM(amount) FROM `Credit_History` 
         WHERE BRACU_ID = :id AND transaction_type IN ("topup", "bonus", "gig_payment")'
    );
    $stmt->execute(['id' => $bracu_id]);
    $total_earned = (float) ($stmt->fetchColumn() ?? 0);
    
    
    $stmt = $pdo->prepare(
        'SELECT SUM(amount) FROM `Credit_History` 
         WHERE BRACU_ID = :id AND transaction_type IN ("debit", "gig_payment")'
    );
    $stmt->execute(['id' => $bracu_id]);
    $total_spent = (float) ($stmt->fetchColumn() ?? 0);
    
    
    $stmt = $pdo->prepare(
        'SELECT * FROM `Credit_History` 
         WHERE BRACU_ID = :id
         ORDER BY created_at DESC 
         LIMIT 10'
    );
    $stmt->execute(['id' => $bracu_id]);
    $recent = $stmt->fetchAll() ?: [];
    
    return [
        'balance' => $balance,
        'total_earned' => $total_earned,
        'total_spent' => $total_spent,
        'net_change' => $total_earned - $total_spent,
        'recent_transactions' => $recent
    ];
}








function grant_bonus(string $bracu_id, float $amount, string $bonus_type, string $reason, ?string $granted_by = null): array
{
    if ($amount <= 0) {
        return ['ok' => false, 'message' => 'Invalid bonus amount.'];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $bonus_id = 'BONUS-' . strtoupper(bin2hex(random_bytes(8)));
        
        $stmt = $pdo->prepare(
            'INSERT INTO `Credit_Bonus` (bonus_id, BRACU_ID, bonus_amount, bonus_type, reason, granted_by)
             VALUES (:bonus_id, :bracu_id, :amount, :type, :reason, :granted_by)'
        );
        $stmt->execute([
            'bonus_id' => $bonus_id,
            'bracu_id' => $bracu_id,
            'amount' => $amount,
            'type' => $bonus_type,
            'reason' => $reason,
            'granted_by' => $granted_by ?? 'system'
        ]);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => "Bonus of {$amount} credits granted.",
            'bonus_id' => $bonus_id
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error granting bonus: " . $e->getMessage());
        return ['ok' => false, 'message' => 'Failed to grant bonus.'];
    }
}




function get_available_bonuses(string $bracu_id): array
{
    $stmt = db()->prepare(
        'SELECT * FROM `Credit_Bonus` 
         WHERE BRACU_ID = :bracu_id AND is_redeemed = 0 AND (expiry_date IS NULL OR expiry_date > NOW())
         ORDER BY created_at DESC'
    );
    $stmt->execute(['bracu_id' => $bracu_id]);
    return $stmt->fetchAll() ?: [];
}




function get_total_available_bonus(string $bracu_id): float
{
    $stmt = db()->prepare(
        'SELECT SUM(bonus_amount) FROM `Credit_Bonus` 
         WHERE BRACU_ID = :bracu_id AND is_redeemed = 0 AND (expiry_date IS NULL OR expiry_date > NOW())'
    );
    $stmt->execute(['bracu_id' => $bracu_id]);
    $total = $stmt->fetchColumn();
    return $total !== null ? (float) $total : 0.00;
}








function can_spend_credits(string $bracu_id, float $amount): array
{
    $pdo = db();
    
    
    $stmt = $pdo->prepare(
        'SELECT is_restricted, restriction_reason, restricted_until, daily_limit, monthly_limit FROM `Credit_Limit` 
         WHERE BRACU_ID = :id'
    );
    $stmt->execute(['id' => $bracu_id]);
    $limit = $stmt->fetch();
    
    if (!$limit) {
        
        init_credit_limit($bracu_id);
        $limit = [
            'is_restricted' => 0,
            'restriction_reason' => null,
            'restricted_until' => null,
            'daily_limit' => 100000.00,
            'monthly_limit' => 500000.00
        ];
    }
    
    if ($limit['is_restricted']) {
        if ($limit['restricted_until'] && strtotime($limit['restricted_until']) > time()) {
            return [
                'allowed' => false,
                'reason' => $limit['restriction_reason'] ?? 'Account restricted.'
            ];
        }
    }
    
    
    $today_spent = get_today_spent($bracu_id);
    $daily_limit = (float) ($limit['daily_limit'] ?? 100000.00);
    
    if ($today_spent + $amount > $daily_limit) {
        return [
            'allowed' => false,
            'reason' => "Daily limit exceeded. Spent: {$today_spent}, Limit: {$daily_limit}"
        ];
    }
    
    
    $month_spent = get_month_spent($bracu_id);
    $monthly_limit = (float) ($limit['monthly_limit'] ?? 500000.00);
    
    if ($month_spent + $amount > $monthly_limit) {
        return [
            'allowed' => false,
            'reason' => "Monthly limit exceeded. Spent: {$month_spent}, Limit: {$monthly_limit}"
        ];
    }
    
    return ['allowed' => true];
}




function get_today_spent(string $bracu_id): float
{
    $stmt = db()->prepare(
        'SELECT SUM(amount) FROM `Credit_History` 
         WHERE BRACU_ID = :id AND transaction_type = "debit" AND DATE(created_at) = CURDATE()'
    );
    $stmt->execute(['id' => $bracu_id]);
    $total = $stmt->fetchColumn();
    return $total !== null ? (float) $total : 0.00;
}




function get_month_spent(string $bracu_id): float
{
    $stmt = db()->prepare(
        'SELECT SUM(amount) FROM `Credit_History` 
         WHERE BRACU_ID = :id AND transaction_type = "debit" AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())'
    );
    $stmt->execute(['id' => $bracu_id]);
    $total = $stmt->fetchColumn();
    return $total !== null ? (float) $total : 0.00;
}




function init_credit_limit(string $bracu_id): bool
{
    try {
        $stmt = db()->prepare(
            'INSERT IGNORE INTO `Credit_Limit` (BRACU_ID, daily_limit, monthly_limit) 
             VALUES (:bracu_id, 100000, 500000)'
        );
        return $stmt->execute(['bracu_id' => $bracu_id]);
    } catch (Throwable $e) {
        error_log("Error initializing credit limit: " . $e->getMessage());
        return false;
    }
}




function restrict_user_credits(string $bracu_id, string $reason, ?string $until = null): bool
{
    try {
        $stmt = db()->prepare(
            'UPDATE `Credit_Limit` 
             SET is_restricted = 1, restriction_reason = :reason, restricted_until = :until
             WHERE BRACU_ID = :bracu_id'
        );
        return $stmt->execute([
            'bracu_id' => $bracu_id,
            'reason' => $reason,
            'until' => $until
        ]);
    } catch (Throwable $e) {
        error_log("Error restricting user: " . $e->getMessage());
        return false;
    }
}








function generate_topup_id(): string
{
    return 'TOP-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(4)));
}




function generate_history_id(): string
{
    return 'HIS-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
}




function format_credits(float $amount): string
{
    return '৳' . number_format($amount, 2);
}




function get_transaction_type_label(string $type): string
{
    $labels = [
        'topup' => 'Credit Top-up',
        'debit' => 'Credit Deduction',
        'refund' => 'Refund',
        'bonus' => 'Bonus Credits',
        'gig_payment' => 'Gig Payment',
        'dispute_refund' => 'Dispute Refund'
    ];
    return $labels[$type] ?? $type;
}




function validate_credit_amount(float $amount, float $min = 1, float $max = 1000000): array
{
    if ($amount < $min) {
        return ['valid' => false, 'message' => "Minimum credit amount is {$min}."];
    }
    if ($amount > $max) {
        return ['valid' => false, 'message' => "Maximum credit amount is {$max}."];
    }
    if ($amount !== (float) number_format($amount, 2)) {
        return ['valid' => false, 'message' => 'Invalid decimal precision. Use up to 2 decimal places.'];
    }
    return ['valid' => true];
}




function get_payment_method_label(string $method): string
{
    $labels = [
        'credit_card' => 'Credit Card',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'rocket' => 'Rocket',
        'dummy' => 'Dummy Payment (Testing)'
    ];
    return $labels[$method] ?? $method;
}
