<?php
declare(strict_types=1);

class VirtualEconomy {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Points System Methods

    /**
     * Award points to a user for completing an activity
     */
    public function awardPoints(string $userId, int $points, string $activityType, ?int $gigId = null, string $description = ''): bool {
        try {
            $this->pdo->beginTransaction();

            // Update user points
            $stmt = $this->pdo->prepare("
                UPDATE User_Points
                SET total_points = total_points + ?,
                    available_points = available_points + ?,
                    lifetime_points = lifetime_points + ?,
                    last_points_earned_at = NOW(),
                    updated_at = NOW()
                WHERE BRACU_ID = ?
            ");
            $stmt->execute([$points, $points, $points, $userId]);

            // Log activity
            $stmt = $this->pdo->prepare("
                INSERT INTO Points_Activity (BRACU_ID, activity_type, points_amount, related_gig, description, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $activityType, $points, $gigId, $description]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error awarding points: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Redeem points for credit
     */
    public function redeemPoints(string $userId, int $points): ?array {
        try {
            $this->pdo->beginTransaction();

            // Check available points
            $stmt = $this->pdo->prepare("SELECT available_points FROM User_Points WHERE BRACU_ID = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || $user['available_points'] < $points) {
                $this->pdo->rollBack();
                return null;
            }

            // Calculate credit (1 point = 0.1 credit by default)
            $creditAmount = $points * 0.1;
            $redemptionId = 'RED-' . time() . '-' . bin2hex(random_bytes(4));

            // Deduct points
            $stmt = $this->pdo->prepare("
                UPDATE User_Points
                SET available_points = available_points - ?,
                    points_redeemed = points_redeemed + ?,
                    last_points_redeemed_at = NOW(),
                    updated_at = NOW()
                WHERE BRACU_ID = ?
            ");
            $stmt->execute([$points, $points, $userId]);

            // Update user credit
            $stmt = $this->pdo->prepare("
                UPDATE User
                SET credit_balance = credit_balance + ?
                WHERE BRACU_ID = ?
            ");
            $stmt->execute([$creditAmount, $userId]);

            // Record redemption
            $stmt = $this->pdo->prepare("
                INSERT INTO Redemption_History (redemption_id, BRACU_ID, points_redeemed, credit_received, redemption_rate, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'completed', NOW())
            ");
            $stmt->execute([$redemptionId, $userId, $points, $creditAmount, 0.1]);

            // Log activity
            $stmt = $this->pdo->prepare("
                INSERT INTO Points_Activity (BRACU_ID, activity_type, points_amount, description, created_at)
                VALUES (?, 'redeemed', ?, ?, NOW())
            ");
            $stmt->execute([$userId, $points, "Redeemed $points points for $creditAmount credits"]);

            $this->pdo->commit();
            return [
                'redemption_id' => $redemptionId,
                'points' => $points,
                'credit' => $creditAmount,
                'new_balance' => $user['available_points'] - $points
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error redeeming points: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user points summary
     */
    public function getUserPoints(string $userId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM User_Points WHERE BRACU_ID = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get points activity history
     */
    public function getPointsActivity(string $userId, int $limit = 20, int $offset = 0): array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM Points_Activity
            WHERE BRACU_ID = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Transaction Ledger Methods

    /**
     * Record a transaction in the ledger
     */
    public function recordTransaction(
        string $fromUser,
        string $toUser,
        string $transactionType,
        float $amount,
        ?int $gigId = null,
        string $description = '',
        ?int $pointsTransferred = null
    ): ?string {
        try {
            $transactionId = 'TXN-' . time() . '-' . bin2hex(random_bytes(4));

            $stmt = $this->pdo->prepare("
                INSERT INTO Transaction_Ledger
                (transaction_id, from_user, to_user, transaction_type, amount, points_transferred, gig_id, description, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $stmt->execute([
                $transactionId,
                $fromUser,
                $toUser,
                $transactionType,
                $amount,
                $pointsTransferred,
                $gigId,
                $description
            ]);

            return $transactionId;
        } catch (Exception $e) {
            error_log("Error recording transaction: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get transaction ledger for a user
     */
    public function getTransactionLedger(string $userId, int $limit = 50, int $offset = 0): array {
        $stmt = $this->pdo->prepare("
            SELECT tl.*,
                   u1.full_name as from_user_name,
                   u2.full_name as to_user_name,
                   g.LIST_OF_GIGS as gig_title
            FROM Transaction_Ledger tl
            LEFT JOIN User u1 ON tl.from_user = u1.BRACU_ID
            LEFT JOIN User u2 ON tl.to_user = u2.BRACU_ID
            LEFT JOIN Gigs g ON tl.gig_id = g.GID
            WHERE tl.from_user = ? OR tl.to_user = ?
            ORDER BY tl.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $userId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verify a transaction for settlement
     */
    public function verifyTransaction(string $transactionId): bool {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM Transaction_Ledger WHERE transaction_id = ?
            ");
            $stmt->execute([$transactionId]);
            $txn = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$txn || $txn['status'] !== 'pending') {
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log("Error verifying transaction: " . $e->getMessage());
            return false;
        }
    }

    // Batch Processing Methods

    /**
     * Create a new transaction batch
     */
    public function createBatch(string $batchType, string $initiatedBy): ?string {
        try {
            $batchId = 'BATCH-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

            $stmt = $this->pdo->prepare("
                INSERT INTO Transaction_Batch (batch_id, batch_type, initiated_by, status, created_at)
                VALUES (?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$batchId, $batchType, $initiatedBy]);

            return $batchId;
        } catch (Exception $e) {
            error_log("Error creating batch: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process a batch of transactions
     */
    public function processBatch(string $batchId): array {
        try {
            $this->pdo->beginTransaction();

            // Get batch details
            $stmt = $this->pdo->prepare("SELECT * FROM Transaction_Batch WHERE batch_id = ?");
            $stmt->execute([$batchId]);
            $batch = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$batch) {
                return ['success' => false, 'error' => 'Batch not found'];
            }

            // Update batch status
            $stmt = $this->pdo->prepare("
                UPDATE Transaction_Batch
                SET status = 'processing', started_at = NOW()
                WHERE batch_id = ?
            ");
            $stmt->execute([$batchId]);

            // Get all pending transactions for this batch
            $stmt = $this->pdo->prepare("
                SELECT * FROM Transaction_Ledger
                WHERE batch_id = ? AND status = 'pending'
            ");
            $stmt->execute([$batchId]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $successful = 0;
            $failed = 0;
            $totalAmount = 0;
            $errors = [];

            foreach ($transactions as $txn) {
                try {
                    // Process the transaction
                    $result = $this->settleTransaction($txn['transaction_id']);

                    if ($result) {
                        $successful++;
                        $totalAmount += $txn['amount'];
                    } else {
                        $failed++;
                        $errors[] = "Transaction {$txn['transaction_id']} failed";
                    }
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Transaction {$txn['transaction_id']}: " . $e->getMessage();
                }
            }

            // Update batch completion
            $stmt = $this->pdo->prepare("
                UPDATE Transaction_Batch
                SET status = 'completed',
                    successful_transactions = ?,
                    failed_transactions = ?,
                    total_transactions = ?,
                    total_amount = ?,
                    completed_at = NOW(),
                    error_log = ?
                WHERE batch_id = ?
            ");
            $stmt->execute([
                $successful,
                $failed,
                $successful + $failed,
                $totalAmount,
                json_encode($errors),
                $batchId
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'batch_id' => $batchId,
                'successful' => $successful,
                'failed' => $failed,
                'total_amount' => $totalAmount,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error processing batch: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Settle a single transaction
     */
    private function settleTransaction(string $transactionId): bool {
        try {
            $this->pdo->beginTransaction();

            // Get transaction
            $stmt = $this->pdo->prepare("SELECT * FROM Transaction_Ledger WHERE transaction_id = ?");
            $stmt->execute([$transactionId]);
            $txn = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$txn || $txn['status'] !== 'pending') {
                return false;
            }

            // Deduct from sender
            $stmt = $this->pdo->prepare("
                UPDATE User
                SET credit_balance = credit_balance - ?
                WHERE BRACU_ID = ?
            ");
            $stmt->execute([$txn['amount'], $txn['from_user']]);

            // Add to recipient
            $stmt = $this->pdo->prepare("
                UPDATE User
                SET credit_balance = credit_balance + ?
                WHERE BRACU_ID = ?
            ");
            $stmt->execute([$txn['amount'], $txn['to_user']]);

            // Mark transaction as completed
            $stmt = $this->pdo->prepare("
                UPDATE Transaction_Ledger
                SET status = 'completed', completed_at = NOW()
                WHERE transaction_id = ?
            ");
            $stmt->execute([$transactionId]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error settling transaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get batch details
     */
    public function getBatch(string $batchId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM Transaction_Batch WHERE batch_id = ?");
        $stmt->execute([$batchId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get batch history
     */
    public function getBatchHistory(int $limit = 20, int $offset = 0): array {
        $stmt = $this->pdo->prepare("
            SELECT tb.*, u.full_name as initiated_by_name
            FROM Transaction_Batch tb
            LEFT JOIN User u ON tb.initiated_by = u.BRACU_ID
            ORDER BY tb.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Dispute Methods

    /**
     * Create a dispute
     */
    public function createDispute(
        string $transactionId,
        string $complainantId,
        string $respondentId,
        string $reason,
        string $description,
        ?int $gigId = null
    ): ?string {
        try {
            $disputeId = 'DSP-' . time() . '-' . bin2hex(random_bytes(4));

            $stmt = $this->pdo->prepare("
                INSERT INTO Transaction_Disputes
                (dispute_id, transaction_id, complainant_id, respondent_id, gig_id, dispute_reason, dispute_description, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW())
            ");

            $stmt->execute([
                $disputeId,
                $transactionId,
                $complainantId,
                $respondentId,
                $gigId,
                $reason,
                $description
            ]);

            return $disputeId;
        } catch (Exception $e) {
            error_log("Error creating dispute: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get disputes for a user
     */
    public function getUserDisputes(string $userId, int $limit = 20, int $offset = 0): array {
        $stmt = $this->pdo->prepare("
            SELECT td.*,
                   u1.full_name as complainant_name,
                   u2.full_name as respondent_name,
                   u3.full_name as resolved_by_name
            FROM Transaction_Disputes td
            LEFT JOIN User u1 ON td.complainant_id = u1.BRACU_ID
            LEFT JOIN User u2 ON td.respondent_id = u2.BRACU_ID
            LEFT JOIN User u3 ON td.resolved_by = u3.BRACU_ID
            WHERE td.complainant_id = ? OR td.respondent_id = ?
            ORDER BY td.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $userId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resolve a dispute with refund or rejection
     */
    public function resolveDispute(
        string $disputeId,
        string $resolutionType,
        string $adminId,
        ?float $refundAmount = null,
        string $notes = ''
    ): bool {
        try {
            $this->pdo->beginTransaction();

            // Get dispute
            $stmt = $this->pdo->prepare("SELECT * FROM Transaction_Disputes WHERE dispute_id = ?");
            $stmt->execute([$disputeId]);
            $dispute = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dispute) {
                return false;
            }

            // If refund, process it
            if ($resolutionType === 'refund' && $refundAmount) {
                // Add refund to complainant
                $stmt = $this->pdo->prepare("
                    UPDATE User
                    SET credit_balance = credit_balance + ?
                    WHERE BRACU_ID = ?
                ");
                $stmt->execute([$refundAmount, $dispute['complainant_id']]);

                // Record refund transaction
                $this->recordTransaction(
                    $dispute['respondent_id'],
                    $dispute['complainant_id'],
                    'refund',
                    $refundAmount,
                    $dispute['gig_id'],
                    "Dispute refund: {$dispute['dispute_id']}"
                );
            }

            // Update dispute resolution
            $stmt = $this->pdo->prepare("
                UPDATE Transaction_Disputes
                SET status = 'resolved',
                    resolution_type = ?,
                    refund_amount = ?,
                    admin_notes = ?,
                    resolved_by = ?,
                    resolved_at = NOW(),
                    updated_at = NOW()
                WHERE dispute_id = ?
            ");
            $stmt->execute([
                $resolutionType,
                $refundAmount,
                $notes,
                $adminId,
                $disputeId
            ]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error resolving dispute: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all open disputes (admin view)
     */
    public function getOpenDisputes(int $limit = 50, int $offset = 0): array {
        $stmt = $this->pdo->prepare("
            SELECT td.*,
                   u1.full_name as complainant_name,
                   u2.full_name as respondent_name,
                   g.LIST_OF_GIGS as gig_title
            FROM Transaction_Disputes td
            LEFT JOIN User u1 ON td.complainant_id = u1.BRACU_ID
            LEFT JOIN User u2 ON td.respondent_id = u2.BRACU_ID
            LEFT JOIN Gigs g ON td.gig_id = g.GID
            WHERE td.status IN ('open', 'under_review')
            ORDER BY td.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
