<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/virtual_economy.php';


if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$userId = $_SESSION['bracu_id'];
$economy = new VirtualEconomy($pdo);


$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'redeem_points') {
        $pointsToRedeem = (int)$_POST['points_to_redeem'];
        $userPoints = $economy->getUserPoints($userId);

        if ($pointsToRedeem > 0 && $userPoints['available_points'] >= $pointsToRedeem) {
            $result = $economy->redeemPoints($userId, $pointsToRedeem);

            if ($result) {
                $message = "Successfully redeemed {$pointsToRedeem} points for ৳ {$result['credit']}!";
                $messageType = 'success';
            } else {
                $message = "Error redeeming points. Please try again.";
                $messageType = 'danger';
            }
        } else {
            $message = "Invalid points amount. You have {$userPoints['available_points']} available points.";
            $messageType = 'warning';
        }
    }
}


$userPoints = $economy->getUserPoints($userId);


$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;
$activity = $economy->getPointsActivity($userId, $perPage, $offset);


$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Points_Activity WHERE BRACU_ID = ?");
$stmt->execute([$userId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalActivity = $result['total'];
$totalPages = max(1, ceil($totalActivity / $perPage));

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">⭐ Rewards & Points System</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Points Overview -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title">Available Points</h6>
                            <p class="h2 text-primary"><?php echo number_format($userPoints['available_points']); ?></p>
                            <p class="text-muted small">Ready to redeem</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title">Total Points</h6>
                            <p class="h2 text-success"><?php echo number_format($userPoints['total_points']); ?></p>
                            <p class="text-muted small">Current balance</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title">Lifetime Points</h6>
                            <p class="h2 text-info"><?php echo number_format($userPoints['lifetime_points']); ?></p>
                            <p class="text-muted small">All time earned</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title">Tier</h6>
                            <p class="h2">
                                <?php
                                $tierEmojis = ['bronze' => '🥉', 'silver' => '🥈', 'gold' => '🥇', 'platinum' => '💎'];
                                echo $tierEmojis[$userPoints['points_tier']];
                                ?>
                            </p>
                            <p class="text-muted small"><?php echo ucfirst($userPoints['points_tier']); ?> Member</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tier Information -->
            <div class="card mb-4 bg-light">
                <div class="card-header">
                    <h5 class="mb-0">Reward Tiers</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="tier-info">
                                <span class="h5">🥉 Bronze</span>
                                <p class="text-muted">0 - 499 points</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="tier-info">
                                <span class="h5">🥈 Silver</span>
                                <p class="text-muted">500 - 999 points</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="tier-info">
                                <span class="h5">🥇 Gold</span>
                                <p class="text-muted">1000 - 4999 points</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="tier-info">
                                <span class="h5">💎 Platinum</span>
                                <p class="text-muted">5000+ points</p>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <small><strong>How to earn points:</strong> Complete gigs, build reputation through ratings, participate in forums, and refer friends.</small>
                    </div>
                </div>
            </div>

            <!-- Redeem Points Section -->
            <?php if ($userPoints['available_points'] > 0): ?>
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">💳 Redeem Points</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="redeem_points">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="points_to_redeem">Points to Redeem</label>
                                        <input type="number"
                                               id="points_to_redeem"
                                               name="points_to_redeem"
                                               class="form-control"
                                               min="1"
                                               max="<?php echo $userPoints['available_points']; ?>"
                                               placeholder="Enter points amount"
                                               required>
                                        <small class="form-text text-muted">Max: <?php echo $userPoints['available_points']; ?> points</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="credit_value">Credit Value</label>
                                        <div class="input-group">
                                            <span class="input-group-text">৳</span>
                                            <input type="text"
                                                   id="credit_value"
                                                   class="form-control"
                                                   readonly
                                                   value="0.00">
                                        </div>
                                        <small class="form-text text-muted">1 point = 0.1 credit</small>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <strong>Redemption Rate:</strong> 10 points = ৳ 1.00
                            </div>
                            <button type="submit" class="btn btn-success">Redeem Points</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Activity History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📊 Points Activity</h5>
                </div>
                <div class="card-body">
                    <?php if (count($activity) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Points</th>
                                        <th>Description</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activity as $act): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                $activityIcons = [
                                                    'earned' => '➕',
                                                    'redeemed' => '➖',
                                                    'bonus' => '🎁',
                                                    'expired' => '⏰'
                                                ];
                                                echo $activityIcons[$act['activity_type']] . ' ' . ucfirst(str_replace('_', ' ', $act['activity_type']));
                                                ?>
                                            </td>
                                            <td>
                                                <strong class="<?php echo $act['activity_type'] === 'redeemed' ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo ($act['activity_type'] === 'redeemed' ? '-' : '+') . $act['points_amount']; ?>
                                                </strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($act['description'] ?? ''); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($act['created_at'])); ?></td>
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
                        <div class="alert alert-info text-center py-4">
                            <p>No points activity yet. Start earning points by completing gigs!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pointsInput = document.getElementById('points_to_redeem');
    const creditValue = document.getElementById('credit_value');

    if (pointsInput) {
        pointsInput.addEventListener('input', function() {
            const points = parseInt(this.value) || 0;
            const credit = (points * 0.1).toFixed(2);
            creditValue.value = credit;
        });
    }
});
</script>

<style>
    .tier-info {
        padding: 10px;
        border-bottom: 1px solid #dee2e6;
    }

    .tier-info:last-child {
        border-bottom: none;
    }

    .card {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .card-header {
        border-radius: 8px 8px 0 0;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
