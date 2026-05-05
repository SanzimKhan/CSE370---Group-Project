<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/credits.php';

$user = require_login();

$message = null;
$message_type = null;
$topup_id = null;
$topup_details = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create_topup') {
        $amount = floatval($_POST['amount'] ?? 0);
        $method = $_POST['payment_method'] ?? 'dummy';

        // Validate amount
        $validation = validate_credit_amount($amount, 1, 100000);
        if (!$validation['valid']) {
            $message = $validation['message'];
            $message_type = 'error';
        } else {
            // Create topup request
            $result = create_topup_request($user['BRACU_ID'], $amount, $method);
            if ($result['ok']) {
                $topup_id = $result['topup_id'];
                $message = 'Top-up request created. Proceed to payment.';
                $message_type = 'success';
            } else {
                $message = $result['message'];
                $message_type = 'error';
            }
        }
    } elseif ($action === 'process_payment') {
        $topup_id = $_POST['topup_id'] ?? null;

        if (!$topup_id) {
            $message = 'Invalid top-up request.';
            $message_type = 'error';
        } else {
            // Get topup details first
            $topup_details = get_topup_details($topup_id);
            
            if (!$topup_details || $topup_details['BRACU_ID'] !== $user['BRACU_ID']) {
                $message = 'Unauthorized access to this top-up.';
                $message_type = 'error';
            } elseif ($topup_details['payment_status'] !== 'pending') {
                $message = 'This top-up has already been processed.';
                $message_type = 'error';
            } else {
                // Process dummy payment
                $result = process_dummy_payment($topup_id);
                if ($result['ok']) {
                    $message = $result['message'];
                    $message_type = 'success';
                    $topup_details = get_topup_details($topup_id);
                } else {
                    $message = $result['message'];
                    $message_type = 'error';
                    $topup_details = get_topup_details($topup_id);
                }
            }
        }
    }
}

// Get topup status if topup_id is in URL
if (isset($_GET['topup_id'])) {
    $requested_topup_id = $_GET['topup_id'];
    $topup_details = get_topup_details($requested_topup_id);
    
    if ($topup_details && $topup_details['BRACU_ID'] === $user['BRACU_ID']) {
        $topup_id = $requested_topup_id;
    }
}

$currentBalance = get_user_credit_balance($user['BRACU_ID']);
$topupHistory = get_user_topup_history($user['BRACU_ID'], 5);

$pageTitle = 'Top-Up Credits';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="card">
    <div class="kicker">💰 Add Credits to Your Wallet</div>
    <h1>Credit Top-Up</h1>
    <p class="muted">Add credits to your account to post gigs, make payments, and more.</p>
    
    <!-- Current Balance -->
    <div class="stats" style="margin: 1.5rem 0;">
        <div class="stat">
            <div class="label">Current Balance</div>
            <div class="value" style="font-size: 2em; color: #28a745;">৳<?= number_format($currentBalance, 2) ?></div>
        </div>
    </div>
</section>

<?php if ($message): ?>
    <section class="card" style="background-color: <?= $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; border-left: 4px solid <?= $message_type === 'success' ? '#28a745' : '#dc3545'; ?>;">
        <p style="color: <?= $message_type === 'success' ? '#155724' : '#721c24'; ?> margin: 0;">
            <?= $message_type === 'success' ? '✓' : '⚠' ?> <?= h($message) ?>
        </p>
    </section>
<?php endif; ?>

<?php if (!$topup_id || ($topup_details && $topup_details['payment_status'] === 'completed')): ?>
    <!-- Top-Up Form -->
    <section class="card">
        <h2>Enter Top-Up Amount</h2>
        <form method="POST" style="max-width: 500px;">
            <input type="hidden" name="action" value="create_topup">
            
            <div style="margin-bottom: 1rem;">
                <label for="amount">Amount (৳)</label>
                <input 
                    type="number" 
                    id="amount" 
                    name="amount" 
                    min="1" 
                    max="100000" 
                    step="0.01" 
                    required
                    placeholder="Enter amount (min: ৳1, max: ৳100,000)"
                    style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;"
                >
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="dummy">Dummy Payment (Testing)</option>
                    <option value="credit_card">Credit Card (Coming Soon)</option>
                    <option value="bkash">bKash (Coming Soon)</option>
                    <option value="nagad">Nagad (Coming Soon)</option>
                    <option value="rocket">Rocket (Coming Soon)</option>
                </select>
            </div>

            <div style="background-color: #e7f3ff; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; border-left: 4px solid #2196F3;">
                <strong>💡 Bonus:</strong> Get 5% bonus credits on every top-up!
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Create Top-Up Request</button>
        </form>
    </section>

    <!-- Quick Top-Up Amounts -->
    <section class="card">
        <h2>Quick Top-Up</h2>
        <p class="muted">Click any amount below for quick top-up</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 0.5rem;">
            <?php foreach ([100, 250, 500, 1000, 2500, 5000] as $quick_amount): ?>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="create_topup">
                    <input type="hidden" name="amount" value="<?= $quick_amount ?>">
                    <input type="hidden" name="payment_method" value="dummy">
                    <button type="submit" class="btn btn-ghost" style="width: 100%; padding: 1rem; text-align: center;">
                        ৳<?= number_format($quick_amount, 0) ?>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

<?php elseif ($topup_details): ?>
    <!-- Payment Processing/Verification -->
    <section class="card">
        <h2>Verify & Process Payment</h2>
        <div style="background-color: #f8f9fa; padding: 1.5rem; border-radius: 4px; margin-bottom: 1rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <p class="muted">Top-Up ID</p>
                    <p style="font-size: 1.1em; font-weight: bold;"><?= h($topup_details['topup_id']) ?></p>
                </div>
                <div>
                    <p class="muted">Amount</p>
                    <p style="font-size: 1.1em; font-weight: bold; color: #28a745;">৳<?= number_format((float)$topup_details['amount'], 2) ?></p>
                </div>
                <div>
                    <p class="muted">Bonus (5%)</p>
                    <p style="font-size: 1.1em; font-weight: bold; color: #007bff;">৳<?= number_format(round($topup_details['amount'] * 0.05, 2), 2) ?></p>
                </div>
                <div>
                    <p class="muted">Total to Receive</p>
                    <p style="font-size: 1.1em; font-weight: bold; color: #6f42c1;">৳<?= number_format($topup_details['amount'] + round($topup_details['amount'] * 0.05, 2), 2) ?></p>
                </div>
            </div>
        </div>

        <?php if ($topup_details['payment_status'] === 'pending'): ?>
            <div style="background-color: #fff3cd; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; border-left: 4px solid #ffc107;">
                <strong>⏳ Payment Pending</strong><br>
                <p>Click the button below to complete the payment for this top-up request.</p>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="process_payment">
                <input type="hidden" name="topup_id" value="<?= h($topup_details['topup_id']) ?>">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">💳 Process Payment Now</button>
            </form>
        <?php elseif ($topup_details['payment_status'] === 'completed'): ?>
            <div style="background-color: #d4edda; padding: 1rem; border-radius: 4px; border-left: 4px solid #28a745;">
                <strong style="color: #155724;">✓ Payment Successful!</strong>
                <p style="color: #155724;">
                    <?= number_format($topup_details['amount'] + $topup_details['bonus_credits'], 2) ?> credits have been added to your account.
                </p>
                <p style="color: #155724; font-size: 0.9em; margin: 0.5rem 0 0 0;">
                    Transaction Reference: <?= h($topup_details['transaction_reference']) ?>
                </p>
            </div>
            <p style="margin-top: 1rem;">
                <a class="btn btn-primary" href="<?= str_contains($_SERVER['HTTP_REFERER'] ?? '', 'dashboard') ? '../dashboard.php' : 'history.php' ?>">← Back</a>
            </p>
        <?php elseif ($topup_details['payment_status'] === 'failed'): ?>
            <div style="background-color: #f8d7da; padding: 1rem; border-radius: 4px; border-left: 4px solid #dc3545;">
                <strong style="color: #721c24;">⚠ Payment Failed</strong>
                <p style="color: #721c24;">Your payment could not be processed. Please try again.</p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="process_payment">
                <input type="hidden" name="topup_id" value="<?= h($topup_details['topup_id']) ?>">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; margin-top: 1rem;">Retry Payment</button>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<!-- Recent Top-Up History -->
<?php if (!empty($topupHistory)): ?>
    <section class="card">
        <h2>Recent Top-Ups</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #ddd;">
                    <th style="padding: 0.5rem; text-align: left;">Date</th>
                    <th style="padding: 0.5rem; text-align: left;">Amount</th>
                    <th style="padding: 0.5rem; text-align: left;">Bonus</th>
                    <th style="padding: 0.5rem; text-align: left;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topupHistory as $topup): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 0.5rem;"><?= date('M d, Y H:i', strtotime($topup['created_at'])) ?></td>
                        <td style="padding: 0.5rem; color: #28a745; font-weight: bold;">৳<?= number_format((float)$topup['amount'], 2) ?></td>
                        <td style="padding: 0.5rem; color: #007bff;">৳<?= number_format((float)$topup['bonus_credits'], 2) ?></td>
                        <td style="padding: 0.5rem;">
                            <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85em; 
                                background-color: <?= $topup['payment_status'] === 'completed' ? '#d4edda' : 
                                    ($topup['payment_status'] === 'failed' ? '#f8d7da' : '#fff3cd') ?>;
                                color: <?= $topup['payment_status'] === 'completed' ? '#155724' : 
                                    ($topup['payment_status'] === 'failed' ? '#721c24' : '#856404') ?>;">
                                <?= ucfirst($topup['payment_status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin-top: 1rem;">
            <a class="btn btn-ghost" href="history.php">View Full History →</a>
        </p>
    </section>
<?php endif; ?>

<!-- Information Section -->
<section class="card">
    <h2>❓ Frequently Asked Questions</h2>
    
    <div style="margin-bottom: 1.5rem;">
        <h3 style="margin-top: 1rem; color: #333;">What are credits?</h3>
        <p class="muted">Credits are virtual currency used in our marketplace to post gigs, make payments, and participate in transactions. When you complete gigs, you earn credits that can be used for future transactions.</p>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <h3 style="color: #333;">Why do I get bonus credits?</h3>
        <p class="muted">We offer a 5% bonus on every top-up to encourage participation and reward our users for their engagement on the platform.</p>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <h3 style="color: #333;">Can I get a refund?</h3>
        <p class="muted">Credits are generally non-refundable, but if there's an issue with your transaction, please contact our support team. For failed payments, your top-up request will remain in pending status and you can retry.</p>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <h3 style="color: #333;">What payment methods are available?</h3>
        <p class="muted">Currently, we support dummy payments for testing. Full payment integration with credit cards, bKash, Nagad, and Rocket is coming soon.</p>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <h3 style="color: #333;">Is there a minimum top-up amount?</h3>
        <p class="muted">The minimum top-up amount is ৳1 and the maximum is ৳100,000 per transaction. You can perform multiple transactions if needed.</p>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
