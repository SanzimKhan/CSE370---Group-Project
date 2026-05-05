<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/wallet.php';

$pdo = db();

function ensure_user(string $id, string $emailPrefix): string {
    $existing = find_user_by_bracu_id($id);
    if ($existing) {
        return $id;
    }

    $created = register_user($id, $emailPrefix . '+' . $id . '@example.com', 'TestPass123!', 'Skill Flow User', '01700000000', 'working');
    if (!$created) {
        throw new RuntimeException('Could not create user ' . $id);
    }

    return (string) $created['BRACU_ID'];
}

$clientId = ensure_user('20101111', 'skillflow-client');
$freelancerId = ensure_user('20102222', 'skillflow-freelancer');

$clientBefore = (float) get_user_credit_balance($clientId);
$freelancerBefore = (float) get_user_credit_balance($freelancerId);

if ($clientBefore < 600.0) {
    add_credits($clientId, 600.0, 'bonus', 'FLOW-TOPUP', null, 'Flow test preload');
    $clientBefore = (float) get_user_credit_balance($clientId);
}

$insertGig = $pdo->prepare(
    'INSERT INTO `Gigs` (BRACU_ID, CREDIT_AMOUNT, LIST_OF_GIGS, skill_tags, CATAGORY, DEADLINE, STATUS)
     VALUES (:bracu_id, :credit_amount, :description, :skill_tags, :category, :deadline, :status)'
);
$insertGig->execute([
    'bracu_id' => $clientId,
    'credit_amount' => 200.00,
    'description' => 'Skill flow payment test gig',
    'skill_tags' => 'PHP,SQL',
    'category' => 'IT',
    'deadline' => date('Y-m-d', strtotime('+5 days')),
    'status' => 'listed',
]);
$gigId = (int) $pdo->lastInsertId();

$accepted = accept_gig($gigId, $freelancerId);
if (!$accepted['ok']) {
    throw new RuntimeException('Accept gig failed: ' . $accepted['message']);
}

$released = mark_gig_done_and_release_payment($gigId, $clientId);
if (!$released['ok']) {
    throw new RuntimeException('Release payment failed: ' . $released['message']);
}

$clientAfter = (float) get_user_credit_balance($clientId);
$freelancerAfter = (float) get_user_credit_balance($freelancerId);

if (abs(($clientBefore - 200.0) - $clientAfter) > 0.01) {
    throw new RuntimeException('Client balance not deducted correctly.');
}

if (abs(($freelancerBefore + 200.0) - $freelancerAfter) > 0.01) {
    throw new RuntimeException('Freelancer balance not increased correctly.');
}

echo "OK: Skill payment flow passed\n";
echo "Client {$clientId}: {$clientBefore} -> {$clientAfter}\n";
echo "Freelancer {$freelancerId}: {$freelancerBefore} -> {$freelancerAfter}\n";
