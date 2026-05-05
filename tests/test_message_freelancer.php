<?php
declare(strict_types=1);

/**
 * Test: Message Freelancer Feature
 * Tests the full flow of adding a message button to my_gigs and sending messages.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/community.php';

session_start();

echo "=== MESSAGE FREELANCER FEATURE TEST ===\n\n";

// Step 1: Create test users
echo "[1] Creating test client and freelancer accounts...\n";

$client_bracu = (string) mt_rand(20000000, 20999999);
$freelancer_bracu = (string) mt_rand(20000000, 20999999);

// Ensure they're different
while ($client_bracu === $freelancer_bracu) {
    $freelancer_bracu = (string) mt_rand(20000000, 20999999);
}

$client = register_user(
    $client_bracu,
    "test_client+{$client_bracu}@example.com",
    'ClientPass123!',
    'Test Client',
    '01700000001',
    'hiring'
);

$freelancer = register_user(
    $freelancer_bracu,
    "test_freelancer+{$freelancer_bracu}@example.com",
    'FreelancerPass123!',
    'Test Freelancer',
    '01700000002',
    'working'
);

if (!$client || !$freelancer) {
    echo "FAILED: Could not create test users\n";
    exit(2);
}

echo "✓ Client: {$client['BRACU_ID']} ({$client['Bracu_mail']})\n";
echo "✓ Freelancer: {$freelancer['BRACU_ID']} ({$freelancer['Bracu_mail']})\n\n";

// Step 2: Create a gig for the client
echo "[2] Creating a gig for the client...\n";

$pdo = db();
$insert = $pdo->prepare(
    'INSERT INTO `Gigs` (BRACU_ID, CREDIT_AMOUNT, LIST_OF_GIGS, CATAGORY, DEADLINE, STATUS)
     VALUES (:bracu_id, :credit, :description, :category, :deadline, :status)'
);

$gig_deadline = date('Y-m-d', strtotime('+7 days'));
$success = $insert->execute([
    'bracu_id' => $client['BRACU_ID'],
    'credit' => 100.00,
    'description' => 'Test Gig: Write a PHP helper function',
    'category' => 'IT',
    'deadline' => $gig_deadline,
    'status' => 'pending',
]);

if (!$success) {
    echo "FAILED: Could not create gig\n";
    exit(3);
}

$gig_id = $pdo->lastInsertId();
echo "✓ Gig #$gig_id created (Status: pending)\n\n";

// Step 3: Assign freelancer to work on gig
echo "[3] Assigning freelancer to work on gig...\n";

$assign = $pdo->prepare(
    'INSERT INTO `Working_on` (BRACU_ID, GID, credit)
     VALUES (:bracu_id, :gid, :credit)'
);

$success = $assign->execute([
    'bracu_id' => $freelancer['BRACU_ID'],
    'gid' => $gig_id,
    'credit' => 100.00,
]);

if (!$success) {
    echo "FAILED: Could not assign freelancer to gig\n";
    exit(4);
}

echo "✓ Freelancer {$freelancer['BRACU_ID']} assigned to Gig #$gig_id\n\n";

// Step 4: Test message creation via Community class
echo "[4] Testing message sending...\n";

$community = new Community($pdo);

// Client sends message to freelancer about the gig
$msg_success = $community->sendMessage(
    $client['BRACU_ID'],
    $freelancer['BRACU_ID'],
    'Hi! Can you start working on this gig? Let me know if you have any questions.',
    (int) $gig_id
);

if (!$msg_success) {
    echo "FAILED: Could not send message\n";
    exit(5);
}

echo "✓ Message sent from client to freelancer\n";

// Freelancer replies
$reply_success = $community->sendMessage(
    $freelancer['BRACU_ID'],
    $client['BRACU_ID'],
    'Yes, I can start right away! Will have it done by the deadline.',
    (int) $gig_id
);

if (!$reply_success) {
    echo "FAILED: Could not send reply\n";
    exit(6);
}

echo "✓ Reply sent from freelancer to client\n\n";

// Step 5: Verify conversation
echo "[5] Verifying conversation in database...\n";

$conversation = $community->getConversation($client['BRACU_ID'], $freelancer['BRACU_ID']);

if (count($conversation) < 2) {
    echo "FAILED: Expected at least 2 messages, got " . count($conversation) . "\n";
    exit(7);
}

echo "✓ Found " . count($conversation) . " messages in conversation\n";
foreach ($conversation as $msg) {
    $sender = $msg['sender_id'] === $client['BRACU_ID'] ? 'Client' : 'Freelancer';
    echo "  - $sender: " . substr($msg['message_text'], 0, 50) . "...\n";
}
echo "\n";

// Step 6: Verify gig lookup works
echo "[6] Verifying gig context lookup...\n";

$query = "SELECT g.*, w.BRACU_ID as freelancer_id FROM Gigs g
          LEFT JOIN Working_on w ON w.GID = g.GID
          WHERE g.GID = ? AND (g.BRACU_ID = ? OR w.BRACU_ID = ?)
          LIMIT 1";
$stmt = $pdo->prepare($query);
$stmt->execute([$gig_id, $client['BRACU_ID'], $client['BRACU_ID']]);
$gig_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gig_info) {
    echo "FAILED: Could not find gig context\n";
    exit(8);
}

echo "✓ Gig context retrieved: Gig #{$gig_info['GID']}\n";
echo "  - Freelancer ID: {$gig_info['freelancer_id']}\n";
echo "  - Status: {$gig_info['STATUS']}\n\n";

// Step 7: Verify message URLs would work
echo "[7] Verifying message URLs...\n";

$message_url = "community/messages.php?user=" . urlencode($freelancer['BRACU_ID']) . "&gig=" . (int) $gig_id;
echo "✓ Message URL would be: $message_url\n";

$freelancer_exists = find_user_by_bracu_id($freelancer['BRACU_ID']);
if (!$freelancer_exists) {
    echo "FAILED: Freelancer lookup failed\n";
    exit(9);
}

echo "✓ Freelancer lookup successful\n\n";

echo "=== ALL TESTS PASSED ===\n";
echo "Summary:\n";
echo "- Created client and freelancer users\n";
echo "- Created pending gig\n";
echo "- Assigned freelancer to gig\n";
echo "- Sent and received messages\n";
echo "- Verified gig context and message URLs\n";

exit(0);
