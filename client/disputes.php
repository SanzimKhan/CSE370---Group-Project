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

// Handle new dispute creation
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_dispute') {
        $transactionId = $_POST['transaction_id'] ?? '';
        $respondentId = $_POST['respondent_id'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $description = $_POST['description'] ?? '';
        $gigId = !empty($_POST['gig_id']) ? (int)$_POST['gig_id'] : null;

        if ($transactionId && $respondentId && $reason && $description) {
            $disputeId = $economy->createDispute(
                $transactionId,
                $userId,
                $respondentId,
                $reason,
                $description,
                $gigId
            );

            if ($disputeId) {
                $message = "Dispute created successfully. Dispute ID: $disputeId";
                $messageType = 'success';
            } else {
                $message = "Error creating dispute. Please try again.";
                $messageType = 'danger';
            }
        } else {
            $message = "Please fill in all required fields.";
            $messageType = 'warning';
        }
    }
}

// Get user disputes
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$disputes = $economy->getUserDisputes($userId, $perPage, $offset);

// Get total disputes count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM Transaction_Disputes
    WHERE complainant_id = ? OR respondent_id = ?
");
$stmt->execute([$userId, $userId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalDisputes = $result['total'];
$totalPages = max(1, ceil($totalDisputes / $perPage));

// Check if admin for admin panel link
$stmt = $pdo->prepare("SELECT is_admin FROM User WHERE BRACU_ID = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$isAdmin = $user['is_admin'];

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>⚖️ Disputes & Complaints</h2>
                <?php if ($isAdmin): ?>
                    <a href="../admin/disputes_admin.php" class="btn btn-outline-danger">
                        Admin Panel
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Create Dispute Form -->
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">📝 File a Dispute</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_dispute">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="transaction_id" class="form-label">Transaction ID *</label>
                                    <input type="text"
                                           id="transaction_id"
                                           name="transaction_id"
                                           class="form-control"
                                           placeholder="TXN-..."
                                           required>
                                    <small class="form-text text-muted">Enter the transaction ID from your transaction ledger</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="respondent_id" class="form-label">Against User ID *</label>
                                    <input type="text"
                                           id="respondent_id"
                                           name="respondent_id"
                                           class="form-control"
                                           placeholder="BRACU ID"
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="gig_id" class="form-label">Related Gig (Optional)</label>
                                    <input type="number"
                                           id="gig_id"
                                           name="gig_id"
                                           class="form-control"
                                           placeholder="Gig ID (if applicable)">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="reason" class="form-label">Dispute Reason *</label>
                                    <select id="reason" name="reason" class="form-control" required>
                                        <option value="">Select a reason...</option>
                                        <option value="payment_error">Payment Error</option>
                                        <option value="work_not_completed">Work Not Completed</option>
                                        <option value="quality_issue">Quality Issue</option>
                                        <option value="duplicate_charge">Duplicate Charge</option>
                                        <option value="unauthorized">Unauthorized Transaction</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea id="description"
                                      name="description"
                                      class="form-control"
                                      rows="4"
                                      placeholder="Provide detailed information about your dispute..."
                                      required></textarea>
                            <small class="form-text text-muted">Be as specific as possible to help us resolve this quickly</small>
                        </div>

                        <button type="submit" class="btn btn-warning">
                            📤 Submit Dispute
                        </button>
                    </form>
                </div>
            </div>

            <!-- Disputes List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Your Disputes</h5>
                </div>
                <div class="card-body">
                    <?php if (count($disputes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Dispute ID</th>
                                        <th>Against</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Filed</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($disputes as $dispute): ?>
                                        <tr>
                                            <td>
                                                <code><?php echo htmlspecialchars(substr($dispute['dispute_id'], 0, 15)); ?></code>
                                            </td>
                                            <td>
                                                <?php
                                                if ($dispute['complainant_id'] === $userId) {
                                                    echo htmlspecialchars($dispute['respondent_name'] ?? $dispute['respondent_id']);
                                                    echo " <span class='badge bg-info'>Respondent</span>";
                                                } else {
                                                    echo htmlspecialchars($dispute['complainant_name'] ?? $dispute['complainant_id']);
                                                    echo " <span class='badge bg-warning'>Complainant</span>";
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($dispute['dispute_reason']))); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'open' => 'warning',
                                                    'under_review' => 'info',
                                                    'resolved' => 'success',
                                                    'closed' => 'secondary'
                                                ];
                                                $color = $statusColors[$dispute['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>">
                                                    <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($dispute['status']))); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                if ($dispute['refund_amount']) {
                                                    echo "৳ " . number_format($dispute['refund_amount'], 2);
                                                } else {
                                                    echo "-";
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($dispute['created_at'])); ?></td>
                                            <td>
                                                <a href="dispute_detail.php?id=<?php echo urlencode($dispute['dispute_id']); ?>"
                                                   class="btn btn-sm btn-outline-primary">
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
                            <nav aria-label="Page navigation" class="mt-3">
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
                            <h5>No disputes filed</h5>
                            <p>You haven't filed any disputes yet. If you need to report an issue, use the form above.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dispute Guidelines -->
            <div class="card mt-4 bg-light">
                <div class="card-header">
                    <h5 class="mb-0">📋 Dispute Guidelines</h5>
                </div>
                <div class="card-body">
                    <ul>
                        <li><strong>Be specific:</strong> Provide clear details about what went wrong</li>
                        <li><strong>Include transaction ID:</strong> Reference the exact transaction in question</li>
                        <li><strong>Document evidence:</strong> Keep records of communications and agreements</li>
                        <li><strong>Response time:</strong> Our team will review within 48 hours</li>
                        <li><strong>Resolution:</strong> Disputes are resolved through review and investigation</li>
                        <li><strong>Appeals:</strong> You can appeal a decision within 7 days of resolution</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    code {
        background-color: #f0f0f0;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 0.85em;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
