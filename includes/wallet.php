<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/credits.php';

function accept_gig(int $gigId, string $freelancerId): array
{
    $pdo = db();

    try {
        $pdo->beginTransaction();

        $gigStatement = $pdo->prepare('SELECT * FROM `Gigs` WHERE GID = :gid FOR UPDATE');
        $gigStatement->execute(['gid' => $gigId]);
        $gig = $gigStatement->fetch();

        if (!$gig) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Gig not found.'];
        }

        if ($gig['STATUS'] !== 'listed') {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'This gig is no longer available.'];
        }

        if ($gig['BRACU_ID'] === $freelancerId) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'You cannot accept your own gig.'];
        }

        $assignStatement = $pdo->prepare(
            'INSERT INTO `Working_on` (BRACU_ID, GID, credit) VALUES (:bracu_id, :gid, :credit)'
        );
        $assignStatement->execute([
            'bracu_id' => $freelancerId,
            'gid' => $gigId,
            'credit' => $gig['CREDIT_AMOUNT'],
        ]);

        $statusStatement = $pdo->prepare('UPDATE `Gigs` SET STATUS = :status WHERE GID = :gid');
        $statusStatement->execute([
            'status' => 'pending',
            'gid' => $gigId,
        ]);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => 'Gig accepted successfully.',
            'gig' => $gig,
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'message' => 'Could not accept gig. Please try again.'];
    }
}

function mark_gig_done_and_release_payment(int $gigId, string $clientId): array
{
    $pdo = db();

    try {
        $pdo->beginTransaction();

        $gigStatement = $pdo->prepare('SELECT * FROM `Gigs` WHERE GID = :gid FOR UPDATE');
        $gigStatement->execute(['gid' => $gigId]);
        $gig = $gigStatement->fetch();

        if (!$gig) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Gig not found.'];
        }

        if ($gig['BRACU_ID'] !== $clientId) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Unauthorized action for this gig.'];
        }

        if ($gig['STATUS'] !== 'pending') {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Only pending gigs can be marked as done.'];
        }

        $workingStatement = $pdo->prepare('SELECT * FROM `Working_on` WHERE GID = :gid FOR UPDATE');
        $workingStatement->execute(['gid' => $gigId]);
        $assignment = $workingStatement->fetch();

        if (!$assignment) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'No freelancer is assigned to this gig yet.'];
        }

        $balanceStatement = $pdo->prepare(
            'SELECT credit_balance FROM `User` WHERE BRACU_ID = :id FOR UPDATE'
        );
        $balanceStatement->execute(['id' => $clientId]);
        $clientBalance = $balanceStatement->fetchColumn();

        $balanceStatement->execute(['id' => $assignment['BRACU_ID']]);
        $freelancerBalance = $balanceStatement->fetchColumn();

        if ($clientBalance === false || $freelancerBalance === false) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Could not load wallet balances.'];
        }

        $balanceMap = [
            $clientId => (float) $clientBalance,
            $assignment['BRACU_ID'] => (float) $freelancerBalance,
        ];

        $amount = (float) $gig['CREDIT_AMOUNT'];
        if ($balanceMap[$clientId] < $amount) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Insufficient credit in your wallet.'];
        }

        $deductStatement = $pdo->prepare(
            'UPDATE `User` SET credit_balance = credit_balance - :amount WHERE BRACU_ID = :client'
        );
        $deductStatement->execute([
            'amount' => $amount,
            'client' => $clientId,
        ]);

        $addStatement = $pdo->prepare(
            'UPDATE `User` SET credit_balance = credit_balance + :amount WHERE BRACU_ID = :freelancer'
        );
        $addStatement->execute([
            'amount' => $amount,
            'freelancer' => $assignment['BRACU_ID'],
        ]);

        $doneStatement = $pdo->prepare('UPDATE `Gigs` SET STATUS = :status WHERE GID = :gid');
        $doneStatement->execute([
            'status' => 'done',
            'gid' => $gigId,
        ]);

        $releaseStatement = $pdo->prepare(
            'UPDATE `Working_on` SET payment_released = 1, done_at = NOW() WHERE GID = :gid'
        );
        $releaseStatement->execute(['gid' => $gigId]);

        // Log to credit history
        $ref_id = 'GIG-' . $gigId . '-' . time();
        
        // Client debit history
        $historyIdClient = 'HIS-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $historyStmt = $pdo->prepare(
            'INSERT INTO `Credit_History` (history_id, BRACU_ID, transaction_type, amount, balance_before, balance_after, reference_id, gig_id, description)
             VALUES (:history_id, :bracu_id, :type, :amount, :balance_before, :balance_after, :reference_id, :gig_id, :description)'
        );
        $historyStmt->execute([
            'history_id' => $historyIdClient,
            'bracu_id' => $clientId,
            'type' => 'debit',
            'amount' => $amount,
            'balance_before' => $balanceMap[$clientId],
            'balance_after' => $balanceMap[$clientId] - $amount,
            'reference_id' => $ref_id,
            'gig_id' => $gigId,
            'description' => "Payment released for gig #$gigId"
        ]);

        // Freelancer earning history
        $historyIdFreelancer = 'HIS-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $historyStmt->execute([
            'history_id' => $historyIdFreelancer,
            'bracu_id' => $assignment['BRACU_ID'],
            'type' => 'gig_payment',
            'amount' => $amount,
            'balance_before' => $balanceMap[$assignment['BRACU_ID']],
            'balance_after' => $balanceMap[$assignment['BRACU_ID']] + $amount,
            'reference_id' => $ref_id,
            'gig_id' => $gigId,
            'description' => "Payment received for gig #$gigId"
        ]);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => 'Gig completed and wallet transfer successful.',
            'freelancer_id' => $assignment['BRACU_ID'],
            'amount' => $amount,
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'message' => 'Payment transfer failed. Please try again.'];
    }
}

function delete_gig(int $gigId, string $clientId): array
{
    $pdo = db();

    try {
        $pdo->beginTransaction();

        // Fetch gig with lock
        $gigStatement = $pdo->prepare('SELECT * FROM `Gigs` WHERE GID = :gid FOR UPDATE');
        $gigStatement->execute(['gid' => $gigId]);
        $gig = $gigStatement->fetch();

        if (!$gig) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Gig not found.'];
        }

        if ($gig['BRACU_ID'] !== $clientId) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Unauthorized: You can only delete your own gigs.'];
        }

        if ($gig['STATUS'] !== 'listed') {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Only listed gigs can be deleted. Cannot delete pending or completed gigs.'];
        }

        // Check if anyone has accepted this gig
        $checkAcceptedStatement = $pdo->prepare('SELECT COUNT(*) as count FROM `Working_on` WHERE GID = :gid');
        $checkAcceptedStatement->execute(['gid' => $gigId]);
        $accepted = $checkAcceptedStatement->fetch();

        if ($accepted && (int)$accepted['count'] > 0) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Cannot delete: A freelancer has already accepted this gig.'];
        }

        // Delete the gig
        $deleteStatement = $pdo->prepare('DELETE FROM `Gigs` WHERE GID = :gid');
        $deleteStatement->execute(['gid' => $gigId]);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => 'Gig deleted successfully.',
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'message' => 'Could not delete gig. Please try again.'];
    }
}
