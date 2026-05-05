<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$profileUserId = normalize_bracu_id((string) ($_GET['id'] ?? ''));
if ($profileUserId === '') {
    http_response_code(404);
    echo 'Profile not found.';
    exit;
}

$pdo = db();

$userStatement = $pdo->prepare(
    'SELECT BRACU_ID, full_name, Bracu_mail, bio, skills, avatar_path, created_at
     FROM `User`
     WHERE BRACU_ID = :id
     LIMIT 1'
);
$userStatement->execute(['id' => $profileUserId]);
$profile = $userStatement->fetch();

if (!$profile) {
    http_response_code(404);
    echo 'Profile not found.';
    exit;
}

$ratingsStatement = $pdo->prepare(
    'SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS total_ratings
     FROM `Ratings`
     WHERE ratee_id = :id'
);
$ratingsStatement->execute(['id' => $profileUserId]);
$ratingStats = $ratingsStatement->fetch() ?: ['avg_rating' => 0, 'total_ratings' => 0];

$completedStatement = $pdo->prepare(
    'SELECT g.GID,
            g.LIST_OF_GIGS,
            g.skill_tags,
            g.CATAGORY,
            g.CREDIT_AMOUNT,
            w.done_at,
            c.BRACU_ID AS client_id,
            c.full_name AS client_name
     FROM `Working_on` w
     JOIN `Gigs` g ON g.GID = w.GID
     JOIN `User` c ON c.BRACU_ID = g.BRACU_ID
     WHERE w.BRACU_ID = :id
       AND g.STATUS = "done"
       AND w.payment_released = 1
     ORDER BY w.done_at DESC
     LIMIT 25'
);
$completedStatement->execute(['id' => $profileUserId]);
$completedProjects = $completedStatement->fetchAll() ?: [];

$careerStatement = $pdo->prepare(
    'SELECT transaction_type, amount, description, created_at
     FROM `Credit_History`
     WHERE BRACU_ID = :id
       AND transaction_type IN ("gig_payment", "bonus")
     ORDER BY created_at DESC
     LIMIT 20'
);
$careerStatement->execute(['id' => $profileUserId]);
$careerHistory = $careerStatement->fetchAll() ?: [];

$totalEarnedStatement = $pdo->prepare(
    'SELECT COALESCE(SUM(amount), 0)
     FROM `Credit_History`
     WHERE BRACU_ID = :id
       AND transaction_type = "gig_payment"'
);
$totalEarnedStatement->execute(['id' => $profileUserId]);
$totalGigEarnings = (float) ($totalEarnedStatement->fetchColumn() ?? 0);

$pageTitle = 'Public Career Profile';
require_once __DIR__ . '/includes/header.php';
?>
<section class="card">
    <div class="kicker">Public Profile</div>
    <h1><?= h((string) ($profile['full_name'] ?: $profile['BRACU_ID'])) ?></h1>
    <p class="muted">Student freelance career snapshot and completed project history.</p>

    <div class="profile-grid">
        <div class="card">
            <h2>Identity</h2>
            <p><strong>BRACU ID:</strong> <?= h($profile['BRACU_ID']) ?></p>
            <p><strong>Member Since:</strong> <?= h(date('M d, Y', strtotime((string) $profile['created_at']))) ?></p>
            <p><strong>Skills:</strong> <?= h((string) ($profile['skills'] ?: 'Not listed yet')) ?></p>
            <p><strong>Bio:</strong> <?= h((string) ($profile['bio'] ?: 'No bio added yet.')) ?></p>
        </div>

        <div class="card">
            <h2>Career Stats</h2>
            <p><strong>Completed Projects:</strong> <?= count($completedProjects) ?></p>
            <p><strong>Average Rating:</strong> <?= number_format((float) $ratingStats['avg_rating'], 1) ?> (<?= (int) $ratingStats['total_ratings'] ?> reviews)</p>
            <p><strong>Total Gig Earnings:</strong> <?= h(format_credits($totalGigEarnings)) ?></p>
        </div>
    </div>
</section>

<section class="card">
    <h2>Completed Projects</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Gig</th>
                    <th>Description</th>
                    <th>Skills</th>
                    <th>Category</th>
                    <th>Credits</th>
                    <th>Client</th>
                    <th>Completed</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$completedProjects): ?>
                    <tr>
                        <td colspan="7" class="muted">No completed projects yet.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($completedProjects as $project): ?>
                    <tr>
                        <td>#<?= (int) $project['GID'] ?></td>
                        <td><?= h($project['LIST_OF_GIGS']) ?></td>
                        <td><?= h((string) ($project['skill_tags'] ?: 'Not specified')) ?></td>
                        <td><?= h($project['CATAGORY']) ?></td>
                        <td><?= h(format_credits((float) $project['CREDIT_AMOUNT'])) ?></td>
                        <td><?= h((string) ($project['client_name'] ?: $project['client_id'])) ?></td>
                        <td><?= h(date('M d, Y', strtotime((string) $project['done_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Career Activity</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Credits</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$careerHistory): ?>
                    <tr>
                        <td colspan="4" class="muted">No career activity yet.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($careerHistory as $item): ?>
                    <tr>
                        <td><?= h(date('M d, Y', strtotime((string) $item['created_at']))) ?></td>
                        <td><?= h(get_transaction_type_label((string) $item['transaction_type'])) ?></td>
                        <td><?= h(format_credits((float) $item['amount'])) ?></td>
                        <td><?= h((string) $item['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
