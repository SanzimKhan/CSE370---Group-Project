<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/credits.php';

$user = require_login();
$pageTitle = 'Credits are Circulated';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="card" style="text-align: center;">
    <div class="kicker">Credit Top-Up Disabled</div>
    <h1>Credits Circulate Through Work</h1>
    <p class="muted">Top-ups are disabled. Every student starts with ৳500, and credits move by completing gigs or transferring credits directly.</p>
    <p>
        <a class="btn btn-primary" href="../dashboard.php">Back to Dashboard</a>
        <a class="btn btn-ghost" href="history.php">View History</a>
    </p>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
