<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/credits.php';

$user = require_login();

$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$filterType = $_GET['type'] ?? null;

$history = get_credit_history($user['BRACU_ID'], $limit + 1, $offset, $filterType);
$summary = get_credit_summary($user['BRACU_ID']);
$hasMore = count($history) > $limit;
$history = array_slice($history, 0, $limit);

$pageTitle = 'Credit History';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <div class="kicker">📊 Account Activity</div>
    <h1>Credit History</h1>
    <p class="muted">Complete audit trail of credits earned, spent, transferred, and awarded through project work.</p>
</section>

<section class="card">
    <h2>Account Summary</h2>
    <div class="stats">
        <div class="stat">
            <div class="label">Current Balance</div>
            <div class="value" style="font-size: 1.8em; color: #28a745;">৳<?= number_format($summary['balance'], 2) ?></div>
        </div>
        <div class="stat">
            <div class="label">Total Earned</div>
            <div class="value" style="color: #007bff;">৳<?= number_format($summary['total_earned'], 2) ?></div>
        </div>
        <div class="stat">
            <div class="label">Total Spent</div>
            <div class="value" style="color: #dc3545;">৳<?= number_format($summary['total_spent'], 2) ?></div>
        </div>
        <div class="stat">
            <div class="label">Net Change</div>
            <div class="value" style="color: <?= $summary['net_change'] >= 0 ? '#28a745' : '#dc3545' ?>;">
                <?= $summary['net_change'] >= 0 ? '+' : '' ?>৳<?= number_format($summary['net_change'], 2) ?>
            </div>
        </div>
    </div>
</section>

<section class="card">
    <h2>Filter Transactions</h2>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="history.php" class="btn <?= !$filterType ? 'btn-primary' : 'btn-ghost' ?>">All Transactions</a>
        <a href="?type=debit" class="btn <?= $filterType === 'debit' ? 'btn-primary' : 'btn-ghost' ?>">Debits</a>
        <a href="?type=gig_payment" class="btn <?= $filterType === 'gig_payment' ? 'btn-primary' : 'btn-ghost' ?>">Gig Payments</a>
        <a href="?type=refund" class="btn <?= $filterType === 'refund' ? 'btn-primary' : 'btn-ghost' ?>">Refunds</a>
        <a href="?type=bonus" class="btn <?= $filterType === 'bonus' ? 'btn-primary' : 'btn-ghost' ?>">Bonuses</a>
    </div>
</section>

<?php if (!empty($history)): ?>
    <section class="card">
        <h2>Transaction History</h2>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
                <thead>
                    <tr style="border-bottom: 2px solid #ddd; background-color: #f8f9fa;">
                        <th style="padding: 0.75rem; text-align: left;">Date & Time</th>
                        <th style="padding: 0.75rem; text-align: left;">Type</th>
                        <th style="padding: 0.75rem; text-align: right;">Amount</th>
                        <th style="padding: 0.75rem; text-align: right;">Balance After</th>
                        <th style="padding: 0.75rem; text-align: left;">Description</th>
                        <th style="padding: 0.75rem; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $txn): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 0.75rem; white-space: nowrap;">
                                <small><?= date('M d, Y', strtotime($txn['created_at'])) ?></small><br>
                                <small style="color: #666;"><?= date('H:i', strtotime($txn['created_at'])) ?></small>
                            </td>
                            <td style="padding: 0.75rem;">
                                <span style="display: inline-block; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85em; background-color: <?= $txn['transaction_type'] === 'bonus' ? '#d4edda' : ($txn['transaction_type'] === 'refund' ? '#fff3cd' : '#f8d7da') ?>; color: <?= $txn['transaction_type'] === 'bonus' ? '#155724' : ($txn['transaction_type'] === 'refund' ? '#856404' : '#721c24') ?>;">
                                    <?= get_transaction_type_label($txn['transaction_type']) ?>
                                </span>
                            </td>
                            <td style="padding: 0.75rem; text-align: right; font-weight: bold; color: <?= in_array($txn['transaction_type'], ['bonus', 'refund']) ? '#28a745' : '#dc3545' ?>;">
                                <?= in_array($txn['transaction_type'], ['bonus', 'refund']) ? '+' : '-' ?>৳<?= number_format((float) $txn['amount'], 2) ?>
                            </td>
                            <td style="padding: 0.75rem; text-align: right;">
                                <strong>৳<?= number_format((float) $txn['balance_after'], 2) ?></strong>
                            </td>
                            <td style="padding: 0.75rem; max-width: 300px;">
                                <small><?= h($txn['description']) ?></small>
                                <?php if ($txn['reference_id']): ?>
                                    <br><small style="color: #999;">Ref: <?= h($txn['reference_id']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem; text-align: center;">
                                <span style="display: inline-block; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.8em; background-color: <?= $txn['status'] === 'completed' ? '#d4edda' : '#fff3cd' ?>; color: <?= $txn['status'] === 'completed' ? '#155724' : '#856404' ?>;">
                                    <?= ucfirst($txn['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: center;">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= $filterType ? '&type=' . h($filterType) : '' ?>" class="btn btn-ghost">← Previous</a>
            <?php endif; ?>

            <div style="padding: 0.5rem 1rem; border: 1px solid #ddd; border-radius: 4px;">
                Page <?= $page ?>
            </div>

            <?php if ($hasMore): ?>
                <a href="?page=<?= $page + 1 ?><?= $filterType ? '&type=' . h($filterType) : '' ?>" class="btn btn-ghost">Next →</a>
            <?php endif; ?>
        </div>
    </section>
<?php else: ?>
    <section class="card" style="text-align: center; padding: 2rem;">
        <p style="font-size: 3em; margin: 0;">📭</p>
        <h2>No Transactions Yet</h2>
        <p class="muted">You haven't had any credit transactions yet. Earn credits by completing projects or receiving transfers.</p>
        <p>
            <a class="btn btn-primary" href="../freelancer/marketplace.php">Browse Projects</a>
        </p>
    </section>
<?php endif; ?>

<section class="card">
    <h2>Quick Actions</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
        <a href="../freelancer/marketplace.php" class="btn btn-primary">Browse Projects</a>
        <a href="<?= str_contains($_SERVER['HTTP_REFERER'] ?? '', 'profile') ? '../profile.php' : '../dashboard.php' ?>" class="btn btn-ghost">← Back</a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
