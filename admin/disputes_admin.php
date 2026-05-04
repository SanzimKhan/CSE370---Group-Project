<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/virtual_economy.php';

// Check authentication and admin status
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

// Check admin role
$stmt = $pdo->prepare("SELECT is_admin FROM User WHERE BRACU_ID = ?");
$stmt->execute([$_SESSION['bracu_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['is_admin']) {
    header('Location: ../dashboard.php');
    exit;
}

$userId = $_SESSION['bracu_id'];
$economy = new VirtualEconomy($pdo);

// Handle dispute resolution
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'resolve_dispute') {
        $disputeId = $_POST['dispute_id'] ?? '';
        $resolutionType = $_POST['resolution_type'] ?? '';
        $refundAmount = !empty($_POST['refund_amount']) ? (float)$_POST['refund_amount'] : null;
        $notes = $_POST['admin_notes'] ?? '';

        if ($disputeId && $resolutionType) {
            $result = $economy->resolveDispute(
                $disputeId,
                $resolutionType,
                $userId,
                $refundAmount,
                $notes
            );

            if ($result) {
                $message = "Dispute resolved successfully.";
                $messageType = 'success';
            } else {
                $message = "Error resolving dispute.";
                $messageType = 'danger';
            }
        }
    }
}

// Get open and under-review disputes
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;
$disputes = $economy->getOpenDisputes($perPage, $offset);

// Get total count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM Transaction_Disputes
    WHERE status IN ('open', 'under_review')
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalDisputes = $result['total'];
$totalPages = max(1, ceil($totalDisputes / $perPage));

require_once '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">⚖️ Dispute Management Panel (Admin)</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title">Open Disputes</h6>
                            <p class="h3 text-warning"><?php echo $totalDisputes; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title">Awaiting Review</h6>
                            <p class="h3 text-info">
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Transaction_Disputes WHERE status = 'under_review'");
                                $stmt->execute();
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo $result['total'];
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title">Resolved</h6>
                            <p class="h3 text-success">
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Transaction_Disputes WHERE status = 'resolved'");
                                $stmt->execute();
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo $result['total'];
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title">Total Refunds</h6>
                            <p class="h3 text-danger">
                                ৳
                                <?php
                                $stmt = $pdo->prepare("SELECT COALESCE(SUM(refund_amount), 0) as total FROM Transaction_Disputes WHERE refund_amount IS NOT NULL");
                                $stmt->execute();
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo number_format($result['total'], 2);
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Disputes Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Disputes Queue</h5>
                </div>
                <div class="card-body">
                    <?php if (count($disputes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Dispute ID</th>
                                        <th>Complainant</th>
                                        <th>Respondent</th>
                                        <th>Reason</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Filed</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($disputes as $dispute): ?>
                                        <tr>
                                            <td>
                                                <code><?php echo htmlspecialchars(substr($dispute['dispute_id'], 0, 12)); ?></code>
                                            </td>
                                            <td><?php echo htmlspecialchars($dispute['complainant_name']); ?></td>
                                            <td><?php echo htmlspecialchars($dispute['respondent_name']); ?></td>
                                            <td><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($dispute['dispute_reason']))); ?></td>
                                            <td>
                                                <?php
                                                $stmt = $pdo->prepare("SELECT amount FROM Transaction_Ledger WHERE transaction_id = ?");
                                                $stmt->execute([$dispute['transaction_id']]);
                                                $txn = $stmt->fetch(PDO::FETCH_ASSOC);
                                                echo $txn ? "৳ " . number_format($txn['amount'], 2) : "-";
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">
                                                    <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($dispute['status']))); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($dispute['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#resolveModal-<?php echo $dispute['id']; ?>">
                                                    Review
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Resolution Modal -->
                                        <div class="modal fade" id="resolveModal-<?php echo $dispute['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Resolve Dispute</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="resolve_dispute">
                                                        <input type="hidden" name="dispute_id" value="<?php echo htmlspecialchars($dispute['dispute_id']); ?>">

                                                        <div class="modal-body">
                                                            <!-- Dispute Details -->
                                                            <div class="mb-3 p-3 bg-light">
                                                                <h6>Dispute Details</h6>
                                                                <p><strong>Reason:</strong> <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($dispute['dispute_reason']))); ?></p>
                                                                <p><strong>Description:</strong></p>
                                                                <p><?php echo htmlspecialchars($dispute['dispute_description']); ?></p>
                                                            </div>

                                                            <!-- Resolution Form -->
                                                            <div class="form-group mb-3">
                                                                <label for="resolution_type-<?php echo $dispute['id']; ?>" class="form-label">Resolution *</label>
                                                                <select id="resolution_type-<?php echo $dispute['id']; ?>"
                                                                        name="resolution_type"
                                                                        class="form-control resolution-type"
                                                                        required
                                                                        data-target="<?php echo $dispute['id']; ?>">
                                                                    <option value="">Select resolution...</option>
                                                                    <option value="refund">Issue Full Refund</option>
                                                                    <option value="partial_refund">Issue Partial Refund</option>
                                                                    <option value="accepted">Accept Complaint (No Refund)</option>
                                                                    <option value="rejected">Reject Complaint</option>
                                                                </select>
                                                            </div>

                                                            <div id="refund-section-<?php echo $dispute['id']; ?>" style="display: none;" class="form-group mb-3">
                                                                <label for="refund_amount-<?php echo $dispute['id']; ?>" class="form-label">Refund Amount (৳)</label>
                                                                <input type="number"
                                                                       id="refund_amount-<?php echo $dispute['id']; ?>"
                                                                       name="refund_amount"
                                                                       class="form-control"
                                                                       step="0.01"
                                                                       min="0"
                                                                       placeholder="0.00">
                                                            </div>

                                                            <div class="form-group mb-3">
                                                                <label for="admin_notes-<?php echo $dispute['id']; ?>" class="form-label">Admin Notes</label>
                                                                <textarea id="admin_notes-<?php echo $dispute['id']; ?>"
                                                                          name="admin_notes"
                                                                          class="form-control"
                                                                          rows="3"
                                                                          placeholder="Internal notes about this dispute resolution..."></textarea>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Resolve Dispute</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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
                        <div class="alert alert-success text-center py-5">
                            <h5>✅ All Disputes Resolved</h5>
                            <p>There are no pending disputes to review.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resolutionSelects = document.querySelectorAll('.resolution-type');

    resolutionSelects.forEach(select => {
        select.addEventListener('change', function() {
            const target = this.dataset.target;
            const refundSection = document.getElementById('refund-section-' + target);
            const refundInput = document.getElementById('refund_amount-' + target);

            if (this.value === 'partial_refund' || this.value === 'refund') {
                refundSection.style.display = 'block';
                if (this.value === 'refund') {
                    refundInput.focus();
                }
            } else {
                refundSection.style.display = 'none';
                refundInput.value = '';
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
