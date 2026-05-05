<?php
declare(strict_types=1);

// Simple CLI test for register_user
require_once __DIR__ . '/../includes/auth.php';

session_start();

function random_bracu_id(): string {
    // generate an 8 digit number starting with 20xxxxxx for realism
    return (string) mt_rand(20000000, 20999999);
}

$attempts = 0;
$created = null;

while ($attempts < 10 && !$created) {
    $bracu = random_bracu_id();
    $email = "test+{$bracu}@example.com";
    $password = 'TestPass123!';
    $full = 'Test User';
    $mobile = '01700000000';

    $created = register_user($bracu, $email, $password, $full, $mobile, 'working');
    $attempts++;
}

if (!$created) {
    echo "FAILED: Could not create test user after {$attempts} attempts\n";
    exit(2);
}

$auth = authenticate_user($created['BRACU_ID'], 'TestPass123!');
if (!$auth) {
    echo "FAILED: Authenticate returned null for created user\n";
    exit(3);
}

echo "OK: User created and authenticated: {$created['BRACU_ID']} ({$created['Bracu_mail']})\n";
exit(0);
