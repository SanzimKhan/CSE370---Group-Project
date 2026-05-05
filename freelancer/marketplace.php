<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/wallet.php';
require_once dirname(__DIR__) . '/includes/mail.php';
require_once dirname(__DIR__) . '/includes/analytics.php';

$user = require_login();
$analytics = new Analytics(db());

if (is_post_request()) {
    enforce_csrf_or_fail('freelancer/marketplace.php');

    $action = $_POST['action'] ?? '';
    $gigId = (int) ($_POST['gid'] ?? 0);

    if ($action === 'accept' && $gigId > 0) {
        $result = accept_gig($gigId, $user['BRACU_ID']);

        if ($result['ok']) {
            // Track gig application
            $analytics->logActivity($user['BRACU_ID'], 'gig_apply', $gigId);
            
            $client = find_user_by_bracu_id($result['gig']['BRACU_ID']);

            if ($client) {
                $subject = 'Gig #' . $gigId . ' has a freelancer now';
                $clientMessage = "A freelancer ({$user['BRACU_ID']}) accepted your gig #{$gigId}. Status is now pending.";
                $freelancerMessage = "You accepted gig #{$gigId}. The client has been notified by mail.";

                send_notification_email($client['Bracu_mail'], $subject, $clientMessage);
                send_notification_email($user['Bracu_mail'], $subject, $freelancerMessage);
            }

            set_flash('success', $result['message'] . ' Confirmation mails sent/logged.');
        } else {
            set_flash('error', $result['message']);
        }
    }

    redirect('freelancer/marketplace.php');
}

$searchTerm = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$validCategories = ['IT', 'Writing', 'Others'];

$sql = 'SELECT g.*, u.Bracu_mail AS client_mail
        FROM `Gigs` g
        JOIN `User` u ON u.BRACU_ID = g.BRACU_ID
    WHERE g.STATUS = :status';
$params = [
    'status' => 'listed',
];

if ($category !== '' && in_array($category, $validCategories, true)) {
    $sql .= ' AND g.CATAGORY = :category';
    $params['category'] = $category;
}

if ($searchTerm !== '') {
    $sql .= ' AND (g.LIST_OF_GIGS LIKE :search OR g.skill_tags LIKE :search)';
    $params['search'] = '%' . $searchTerm . '%';
}

$sql .= ' ORDER BY g.created_at DESC';

$statement = db()->prepare($sql);
$statement->execute($params);
$gigs = $statement->fetchAll();

// Track gig views for displayed gigs
foreach ($gigs as $gig) {
    $analytics->logGigView((int) $gig['GID'], $user['BRACU_ID']);
}

$pageTitle = 'Available Gig List';
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="card">
    <div class="kicker">Freelancer Tool</div>
    <h1>Available Gig List</h1>
    <p class="muted">Search and pick listed gigs. Accepting a gig changes it to pending and sends confirmation mail.</p>

    <form method="get" action="marketplace.php" class="grid cols-2" style="margin-bottom: 1rem;">
        <div class="form-row">
            <label for="q">Search Description</label>
            <input type="text" id="q" name="q" value="<?= h($searchTerm) ?>" placeholder="Search gigs...">
        </div>
        <div class="form-row">
            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">All categories</option>
                <?php foreach ($validCategories as $item): ?>
                    <option value="<?= h($item) ?>" <?= $category === $item ? 'selected' : '' ?>>
                        <?= h($item) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit">Apply Filters</button>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>GID</th>
                    <th>Description</th>
                    <th>Skills</th>
                    <th>Category</th>
                    <th>Deadline</th>
                    <th>Credit</th>
                    <th>Client</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$gigs): ?>
                    <tr>
                        <td colspan="8" class="muted">No listed gigs match your current filter.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($gigs as $gig): ?>
                    <?php $isOwnGig = $gig['BRACU_ID'] === $user['BRACU_ID']; ?>
                    <tr>
                        <td>#<?= (int) $gig['GID'] ?></td>
                        <td><?= h($gig['LIST_OF_GIGS']) ?></td>
                        <td><?= h((string) ($gig['skill_tags'] ?? 'Not specified')) ?></td>
                        <td><?= h($gig['CATAGORY']) ?></td>
                        <td><?= h($gig['DEADLINE']) ?></td>
                        <td><?= h(format_credit((float) $gig['CREDIT_AMOUNT'])) ?></td>
                        <td><?= h($gig['BRACU_ID']) ?></td>
                        <td>
                            <?php if ($isOwnGig): ?>
                                <span class="muted">Your posted gig</span>
                            <?php else: ?>
                                <form class="inline-form" method="post" action="marketplace.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="accept">
                                    <input type="hidden" name="gid" value="<?= (int) $gig['GID'] ?>">
                                    <button type="submit">Accept Gig</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
