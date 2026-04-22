<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

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
            'SELECT BRACU_ID, credit_balance FROM `User` WHERE BRACU_ID IN (:client, :freelancer) FOR UPDATE'
        );
        $balanceStatement->execute([
            'client' => $clientId,
            'freelancer' => $assignment['BRACU_ID'],
        ]);
        $balances = $balanceStatement->fetchAll();

        if (count($balances) !== 2) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Could not load wallet balances.'];
        }

        $balanceMap = [];
        foreach ($balances as $balanceRow) {
            $balanceMap[$balanceRow['BRACU_ID']] = (float) $balanceRow['credit_balance'];
        }

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
