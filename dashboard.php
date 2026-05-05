<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/credits.php';

$user = require_login();

$clientStats = [
    'listed' => 0,
    'pending' => 0,
    'done' => 0,
];
$clientStatement = db()->prepare('SELECT STATUS, COUNT(*) AS total FROM `Gigs` WHERE BRACU_ID = :id GROUP BY STATUS');
$clientStatement->execute(['id' => $user['BRACU_ID']]);
foreach ($clientStatement->fetchAll() as $row) {
    $clientStats[$row['STATUS']] = (int) $row['total'];
}

$freelancerStats = [
    'pending' => 0,
    'done' => 0,
];
$freelancerStatement = db()->prepare(
    'SELECT g.STATUS, COUNT(*) AS total
     FROM `Working_on` w
     JOIN `Gigs` g ON g.GID = w.GID
     WHERE w.BRACU_ID = :id
     GROUP BY g.STATUS'
);
$freelancerStatement->execute(['id' => $user['BRACU_ID']]);
foreach ($freelancerStatement->fetchAll() as $row) {
    $freelancerStats[$row['STATUS']] = (int) $row['total'];
}

$availableStatement = db()->prepare(
    'SELECT COUNT(*) FROM `Gigs` WHERE STATUS = :status'
);
$availableStatement->execute([
    'status' => 'listed',
]);
$availableCount = (int) $availableStatement->fetchColumn();

// Get credit information
$creditBalance = get_user_credit_balance($user['BRACU_ID']);
$creditSummary = get_credit_summary($user['BRACU_ID']);

$mode = active_user_mode($user);

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<section class="card">
    <div class="kicker">Dual-Role Workspace</div>
    <h1>Hello, <?= h($user['BRACU_ID']) ?></h1>
    <p class="muted">Use the same account as a client and as a freelancer. Your e-wallet updates automatically after a gig is completed.</p>
    <p class="muted">Current login mode: <strong><?= h($mode === 'hiring' ? 'Hiring (Post jobs)' : 'Working (Apply to jobs)') ?></strong></p>
    <p class="muted">All accounts start with ৳500. Credits circulate through project payments and transfers only.</p>
</section>

<!-- Credit Management Section -->
<section class="card">
    <div class="kicker">💰 Credit Wallet</div>
    <h2>Your Credits</h2>
    <div class="stats">
        <div class="stat">
            <div class="label">Available Balance</div>
            <div class="value" style="font-size: 2.5em; color: #28a745;">৳<?= number_format($creditBalance, 2) ?></div>
        </div>
        <div class="stat">
            <div class="label">Total Earned</div>
            <div class="value" style="color: #007bff;">৳<?= number_format($creditSummary['total_earned'], 2) ?></div>
        </div>
        <div class="stat">
            <div class="label">Total Spent</div>
            <div class="value" style="color: #dc3545;">৳<?= number_format($creditSummary['total_spent'], 2) ?></div>
        </div>
    </div>
    <p style="margin-top: 1rem;">
        <a class="btn btn-ghost" href="credits/history.php">📊 View History</a>
        <a class="btn btn-ghost" href="profile.php">👤 Wallet Details</a>
    </p>
</section>

<section class="grid cols-2">
    <article class="card">
        <h2>Client Panel</h2>
        <p class="muted">Post new requests, track status, and release payment when work is complete.</p>
        <div class="stats">
            <div class="stat">
                <div class="label">Listed</div>
                <div class="value"><?= $clientStats['listed'] ?></div>
            </div>
            <div class="stat">
                <div class="label">Pending</div>
                <div class="value"><?= $clientStats['pending'] ?></div>
            </div>
            <div class="stat">
                <div class="label">Done</div>
                <div class="value"><?= $clientStats['done'] ?></div>
            </div>
        </div>
        <p>
            <a class="btn btn-primary" href="client/create_gig.php">Request Your Gig</a>
            <a class="btn btn-ghost" href="client/my_gigs.php">Listed Gigs</a>
        </p>
    </article>

    <article class="card">
        <h2>Freelancer Panel</h2>
        <p class="muted">Browse available gigs, accept work, and track your active assignments.</p>
        <div class="stats">
            <div class="stat">
                <div class="label">Available</div>
                <div class="value"><?= $availableCount ?></div>
            </div>
            <div class="stat">
                <div class="label">In Progress</div>
                <div class="value"><?= $freelancerStats['pending'] ?></div>
            </div>
            <div class="stat">
                <div class="label">Completed</div>
                <div class="value"><?= $freelancerStats['done'] ?></div>
            </div>
        </div>
        <p>
            <a class="btn btn-primary" href="freelancer/marketplace.php">Available Gig List</a>
            <a class="btn btn-ghost" href="freelancer/my_work.php">My Work</a>
        </p>
    </article>
</section>

<?php if (current_user_is_admin($user)): ?>
    <section class="card" style="margin-top: 1rem;">
        <div class="kicker">Admin Access</div>
        <h2>Admin Management</h2>
        <p class="muted">Manage who can access admin tools from the admin console.</p>
        <p>
            <a class="btn btn-primary" href="admin/manage_admins.php">Open Admin Console</a>
        </p>
    </section>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
