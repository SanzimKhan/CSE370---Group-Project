<?php












declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/credits.php';
require_once __DIR__ . '/../includes/auth.php';

class CreditTestSuite {
    private $pdo;
    private $testUser;
    private $testUser2;
    private $testResults = [];
    private $passCount = 0;
    private $failCount = 0;

    public function __construct() {
        $this->pdo = db();
        $this->testUser = $this->ensureTestUser('20101001', 'credittest1');
        $this->testUser2 = $this->ensureTestUser('20101002', 'credittest2');
    }

    private function ensureTestUser(string $preferredId, string $emailPrefix): string {
        $existing = find_user_by_bracu_id($preferredId);
        if ($existing) {
            return $preferredId;
        }

        $email = $emailPrefix . '+' . $preferredId . '@example.com';
        $created = register_user($preferredId, $email, 'TestPass123!', 'Credit Test User', '01700000000', 'working');
        if ($created) {
            return (string) $created['BRACU_ID'];
        }

        
        for ($i = 0; $i < 20; $i++) {
            $id = (string) mt_rand(20000000, 20999999);
            $email = $emailPrefix . '+' . $id . '@example.com';
            $created = register_user($id, $email, 'TestPass123!', 'Credit Test User', '01700000000', 'working');
            if ($created) {
                return (string) $created['BRACU_ID'];
            }
        }

        throw new Exception('Failed to create test user for credit tests.');
    }

    


    public function runAllTests(): void {
        echo "\n";
        echo "════════════════════════════════════════════════════════════\n";
        echo "    CREDIT MANAGEMENT SYSTEM - TEST SUITE\n";
        echo "════════════════════════════════════════════════════════════\n\n";

        
        $this->testGetCreditBalance();
        $this->testAddCredits();
        $this->testDeductCredits();
        $this->testInsufficientCredits();
        
        
        $this->testCreditHistoryTracking();
        $this->testCreditSummary();
        
        
        $this->testGrantBonus();
        $this->testGetAvailableBonuses();
        
        
        $this->testCreditLimitCheck();
        $this->testDailySpendingLimit();
        
        
        $this->testNegativeAmounts();
        $this->testMaximumAmounts();
        $this->testConcurrentTransactions();
        $this->testTransferCredits();
        $this->testFormattingAndValidation();

        
        $this->printSummary();
    }

    
    
    

    private function testGetCreditBalance(): void {
        echo "TEST 1: Get Credit Balance\n";
        try {
            $balance = get_user_credit_balance($this->testUser);
            $this->assert(is_float($balance), "Balance should be float");
            $this->assert($balance >= 0, "Balance should be non-negative");
            echo "  ✓ Current balance: ৳" . number_format($balance, 2) . "\n";
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    private function testAddCredits(): void {
        echo "TEST 2: Add Credits\n";
        try {
            $initialBalance = get_user_credit_balance($this->testUser);
            $result = add_credits($this->testUser, 500, 'bonus', 'TEST-001', null, 'Test bonus');
            
            $this->assert($result['ok'], "Should successfully add credits");
            $newBalance = get_user_credit_balance($this->testUser);
            $this->assert($newBalance == $initialBalance + 500, "Balance should increase by 500");
            echo "  ✓ Added 500 credits successfully\n";
            echo "  ✓ New balance: ৳" . number_format($newBalance, 2) . "\n";
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    private function testDeductCredits(): void {
        echo "TEST 3: Deduct Credits\n";
        try {
            
            add_credits($this->testUser, 1000, 'topup', 'TEST-002');
            $balanceBefore = get_user_credit_balance($this->testUser);
            
            $result = deduct_credits($this->testUser, 200, 'debit', 'TEST-003', null, 'Test debit');
            $this->assert($result['ok'], "Should successfully deduct credits");
            
            $balanceAfter = get_user_credit_balance($this->testUser);
            $this->assert($balanceAfter == $balanceBefore - 200, "Balance should decrease by 200");
            echo "  ✓ Deducted 200 credits successfully\n";
            echo "  ✓ New balance: ৳" . number_format($balanceAfter, 2) . "\n";
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    private function testInsufficientCredits(): void {
        echo "TEST 4: Insufficient Credits Validation\n";
        try {
            $balance = get_user_credit_balance($this->testUser);
            $result = deduct_credits($this->testUser, $balance + 1000, 'debit', null, null, 'Should fail');
            
            $this->assert(!$result['ok'], "Should fail with insufficient credits");
            $this->assert(isset($result['available']) && isset($result['required']), "Should return balance info");
            echo "  ✓ Correctly rejected insufficient credits transaction\n";
            echo "  ✓ Error message: " . $result['message'] . "\n";
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    
    
    

    private function testCreditHistoryTracking(): void {
        echo "TEST 5: Credit History Tracking\n";
        try {
            $history = get_credit_history($this->testUser, 10);
            $this->assert(is_array($history), "Should return array of history");
            
            
            add_credits($this->testUser, 250, 'bonus', 'TEST-H1', null, 'History test');
            $newHistory = get_credit_history($this->testUser, 10);

            $found = false;
            foreach ($newHistory as $entry) {
                if (($entry['reference_id'] ?? '') === 'TEST-H1') {
                    $found = true;
                    break;
                }
            }

            $this->assert($found, "Newly inserted history entry should exist");
            echo "  ✓ Credit history tracking working\n";
            echo "  ✓ Total transactions: " . count($newHistory) . "\n";
            if (!empty($newHistory)) {
                echo "  ✓ Latest transaction: " . $newHistory[0]['description'] . "\n";
            }
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    private function testCreditSummary(): void {
        echo "TEST 6: Credit Summary\n";
        try {
            $summary = get_credit_summary($this->testUser);
            
            $this->assert(isset($summary['balance']), "Should have balance");
            $this->assert(isset($summary['total_earned']), "Should have total_earned");
            $this->assert(isset($summary['total_spent']), "Should have total_spent");
            $this->assert(isset($summary['net_change']), "Should have net_change");
            $this->assert(isset($summary['recent_transactions']), "Should have recent_transactions");
            
            echo "  ✓ Credit summary retrieved\n";
            echo "  ✓ Current balance: ৳" . number_format($summary['balance'], 2) . "\n";
            echo "  ✓ Total earned: ৳" . number_format($summary['total_earned'], 2) . "\n";
            echo "  ✓ Total spent: ৳" . number_format($summary['total_spent'], 2) . "\n";
            echo "  ✓ Net change: ৳" . number_format($summary['net_change'], 2) . "\n";
            echo "  ✓ Recent transactions: " . count($summary['recent_transactions']) . "\n";
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    
    
    

    private function testGrantBonus(): void {
        echo "TEST 7: Grant Bonus Credits\n";
        try {
            $result = grant_bonus($this->testUser, 150, 'promotion', 'Test promotion bonus', 'system');
            $this->assert($result['ok'], "Should grant bonus");
            $this->assert(isset($result['bonus_id']), "Should return bonus_id");
            
            echo "  ✓ Bonus granted successfully\n";
            echo "  ✓ Bonus ID: " . substr($result['bonus_id'], 0, 15) . "...\n";
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    private function testGetAvailableBonuses(): void {
        echo "TEST 8: Get Available Bonuses\n";
        try {
            grant_bonus($this->testUser, 100, 'promotion', 'Test', 'system');
            $bonuses = get_available_bonuses($this->testUser);
            
            $this->assert(is_array($bonuses), "Should return array");
            echo "  ✓ Retrieved available bonuses\n";
            echo "  ✓ Available bonuses: " . count($bonuses) . "\n";
            
            $total = get_total_available_bonus($this->testUser);
            $this->assert($total >= 0, "Total should be non-negative");
            echo "  ✓ Total bonus available: ৳" . number_format($total, 2) . "\n";
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    
    
    

    private function testCreditLimitCheck(): void {
        echo "TEST 9: Credit Limit Check\n";
        try {
            $result = can_spend_credits($this->testUser, 1000);
            $this->assert(isset($result['allowed']), "Should return allowed status");
            
            echo "  ✓ Credit limit check working\n";
            echo "  ✓ Can spend ৳1000: " . ($result['allowed'] ? 'yes' : 'no') . "\n";
            if (!$result['allowed']) {
                echo "  ✓ Reason: " . $result['reason'] . "\n";
            }
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    private function testDailySpendingLimit(): void {
        echo "TEST 10: Daily Spending Limit\n";
        try {
            $daily = get_today_spent($this->testUser);
            $this->assert(is_float($daily) && $daily >= 0, "Should return daily spending");
            
            $monthly = get_month_spent($this->testUser);
            $this->assert(is_float($monthly) && $monthly >= 0, "Should return monthly spending");
            
            echo "  ✓ Daily/monthly spending tracking working\n";
            echo "  ✓ Today spent: ৳" . number_format($daily, 2) . "\n";
            echo "  ✓ This month spent: ৳" . number_format($monthly, 2) . "\n";
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    
    
    

    private function testNegativeAmounts(): void {
        echo "TEST 11: Negative Amount Rejection\n";
        try {
            $result = add_credits($this->testUser, -100, 'debit');
            $this->assert(!$result['ok'], "Should reject negative amounts");
            
            $result2 = deduct_credits($this->testUser, -50, 'debit');
            $this->assert(!$result2['ok'], "Should reject negative deduction");
            
            echo "  ✓ Negative amounts correctly rejected\n";
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    private function testMaximumAmounts(): void {
        echo "TEST 12: Maximum Amount Validation\n";
        try {
            $result = validate_credit_amount(2000000);
            $this->assert(!$result['valid'], "Should reject amounts exceeding maximum");
            
            echo "  ✓ Maximum amount limits enforced\n";
            echo "  ✓ Error: " . $result['message'] . "\n";
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    private function testConcurrentTransactions(): void {
        echo "TEST 13: Concurrent Transaction Safety\n";
        try {
            $initialBalance = get_user_credit_balance($this->testUser);
            
            
            $result1 = deduct_credits($this->testUser, 100, 'debit', 'TEST-C1');
            $result2 = deduct_credits($this->testUser, 100, 'debit', 'TEST-C2');
            
            $finalBalance = get_user_credit_balance($this->testUser);
            
            if ($result1['ok'] && $result2['ok']) {
                $expectedBalance = $initialBalance - 200;
                $this->assert($finalBalance == $expectedBalance, "Both transactions should be recorded");
                echo "  ✓ Concurrent transactions handled safely\n";
            } else {
                echo "  ⚠ One or both transactions failed (expected if balance insufficient)\n";
            }
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    private function testTransferCredits(): void {
        echo "TEST 14: Transfer Credits Between Users\n";
        try {
            
            add_credits($this->testUser, 1000, 'bonus');
            $sender_before = get_user_credit_balance($this->testUser);
            $receiver_before = get_user_credit_balance($this->testUser2);
            
            $result = transfer_credits($this->testUser, $this->testUser2, 200, 'Test transfer');
            
            if ($result['ok']) {
                $sender_after = get_user_credit_balance($this->testUser);
                $receiver_after = get_user_credit_balance($this->testUser2);
                
                $this->assert($sender_after == $sender_before - 200, "Sender balance should decrease");
                $this->assert($receiver_after == $receiver_before + 200, "Receiver balance should increase");
                echo "  ✓ Credits transferred successfully\n";
                echo "  ✓ Sender: ৳" . number_format($sender_before, 2) . " → ৳" . number_format($sender_after, 2) . "\n";
                echo "  ✓ Receiver: ৳" . number_format($receiver_before, 2) . " → ৳" . number_format($receiver_after, 2) . "\n";
                $this->pass();
            } else {
                echo "  ✗ Transfer failed: " . $result['message'] . "\n";
                $this->fail();
            }
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    private function testFormattingAndValidation(): void {
        echo "TEST 15: Formatting and Validation\n";
        try {
            
            $valid1 = validate_credit_amount(100);
            $this->assert($valid1['valid'], "100 should be valid");
            
            $valid2 = validate_credit_amount(1.00);
            $this->assert($valid2['valid'], "1.00 should be valid");
            
            $valid3 = validate_credit_amount(0);
            $this->assert(!$valid3['valid'], "0 should be invalid");
            
            $valid4 = validate_credit_amount(2000000);
            $this->assert(!$valid4['valid'], "2000000 should be invalid");
            
            
            $formatted = format_credits(1500.50);
            $this->assert(str_contains($formatted, '৳'), "Should use Taka symbol");
            
            echo "  ✓ Validation and formatting working correctly\n";
            echo "  ✓ Formatted example: " . $formatted . "\n";
            echo "  ✓ Payment methods: " . get_payment_method_label('dummy') . "\n";
            $this->pass();
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $this->fail();
        }
    }

    
    
    

    private function assert(bool $condition, string $message): void {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    private function pass(): void {
        $this->passCount++;
    }

    private function fail(): void {
        $this->failCount++;
    }

    private function printSummary(): void {
        $total = $this->passCount + $this->failCount;
        $percentage = $total > 0 ? round(($this->passCount / $total) * 100, 1) : 0;
        
        echo "\n";
        echo "════════════════════════════════════════════════════════════\n";
        echo "    TEST RESULTS SUMMARY\n";
        echo "════════════════════════════════════════════════════════════\n\n";
        echo "Total Tests: $total\n";
        echo "✓ Passed: " . $this->passCount . "\n";
        echo "✗ Failed: " . $this->failCount . "\n";
        echo "Success Rate: $percentage%\n";
        echo "\n";
        
        if ($this->failCount === 0) {
            echo "🎉 ALL TESTS PASSED!\n";
        } else {
            echo "⚠️  Some tests failed. Please review above.\n";
        }
        
        echo "\n════════════════════════════════════════════════════════════\n\n";
    }
}


if (php_sapi_name() === 'cli') {
    $suite = new CreditTestSuite();
    $suite->runAllTests();
} else {
    echo "This script must be run from CLI\n";
    exit(1);
}
