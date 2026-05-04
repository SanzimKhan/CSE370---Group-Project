<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/virtual_economy.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$userId = $_SESSION['bracu_id'];
$economy = new VirtualEconomy($pdo);

// Get pagination parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get transaction ledger
$transactions = $economy->getTransactionLedger($userId, $perPage, $offset);

// Get total count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM Transaction_Ledger
    WHERE from_user = ? OR to_user = ?
");
$stmt->execute([$userId, $userId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalTransactions = $result['total'];
$totalPages = max(1, ceil($totalTransactions / $perPage));

// Get user info
$stmt = $pdo->prepare("SELECT credit_balance FROM User WHERE BRACU_ID = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">💰 Transaction Ledger</h2>

            <!-- Balance Card -->
            <div class="card mb-4 bg-light">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="card-title">Current Balance</h5>
                            <p class="h3 text-success">৳ <?php echo number_format($user['credit_balance'], 2); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5 class="card-title">Total Transactions</h5>
                            <p class="h3 text-info"><?php echo $totalTransactions; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Options -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="form-inline">
                        <div class="form-group mr-3">
                            <label for="type" class="mr-2">Filter by Type:</label>
                            <select name="type" id="type" class="form-control form-control-sm">
                                <option value="">All Types</option>
                                <option value="gig_payment">Gig Payment</option>
                                <option value="points_redemption">Points Redemption</option>
                                <option value="refund">Refund</option>
                                <option value="bonus">Bonus</option>
                                <option value="withdrawal">Withdrawal</option>
                            </select>
                        </div>
                        <div class="form-group mr-3">
                            <label for="status" class="mr-2">Status:</label>
                            <select name="status" id="status" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                                <option value="reversed">Reversed</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    </form>
                </div>
            </div>

            <!-- Transactions Table -->
            <?php if (count($transactions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Transaction ID</th>
                                <th>Type</th>
                                <th>From / To</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars(substr($txn['transaction_id'], 0, 12) . '...'); ?></code></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($txn['transaction_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($txn['from_user'] === $userId) {
                                            echo "→ " . htmlspecialchars($txn['to_user_name'] ?? $txn['to_user']);
                                        } else {
                                            echo "← " . htmlspecialchars($txn['from_user_name'] ?? $txn['from_user']);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <strong>
                                            <?php
                                            $sign = ($txn['from_user'] === $userId) ? '-' : '+';
                                            $color = ($txn['from_user'] === $userId) ? 'text-danger' : 'text-success';
                                            echo "<span class='$color'>" . $sign . "৳ " . number_format($txn['amount'], 2) . "</span>";
                                            ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'reversed' => 'secondary'
                                        ];
                                        $color = $statusColors[$txn['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo htmlspecialchars($txn['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($txn['description'] ?? '', 0, 30)); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($txn['created_at'])); ?></td>
                                    <td>
                                        <a href="transaction_detail.php?id=<?php echo urlencode($txn['transaction_id']); ?>" class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1">First</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?>">Last</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info text-center py-5">
                    <h5>No transactions found</h5>
                    <p>Your transaction history will appear here once you complete your first gig or earn points.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    code {
        background-color: #f0f0f0;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 0.85em;
    }

    .table-hover tbody tr:hover {
        background-color: #f5f5f5;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
