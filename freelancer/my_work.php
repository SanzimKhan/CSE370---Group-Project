<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

$user = require_login();

$statement = db()->prepare(
    'SELECT g.GID, g.LIST_OF_GIGS, g.CATAGORY, g.CREDIT_AMOUNT, g.STATUS, g.DEADLINE,
            g.BRACU_ID AS client_id, w.payment_released, w.accepted_at, w.done_at
     FROM `Working_on` w
     JOIN `Gigs` g ON g.GID = w.GID
     WHERE w.BRACU_ID = :id
     ORDER BY w.accepted_at DESC'
);
$statement->execute(['id' => $user['BRACU_ID']]);
$works = $statement->fetchAll();

$pageTitle = 'My Work';
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="card">
    <div class="kicker">Freelancer Tool</div>
    <h1>My Accepted Gigs</h1>
    <p class="muted">Track gigs you accepted and payment release status.</p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>GID</th>
                    <th>Description</th>
                    <th>Client</th>
                    <th>Category</th>
                    <th>Credit</th>
                    <th>Status</th>
                    <th>Payment</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$works): ?>
                    <tr>
                        <td colspan="7" class="muted">You have not accepted any gigs yet.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($works as $work): ?>
                    <tr>
                        <td>#<?= (int) $work['GID'] ?></td>
                        <td><?= h($work['LIST_OF_GIGS']) ?></td>
                        <td><?= h($work['client_id']) ?></td>
                        <td><?= h($work['CATAGORY']) ?></td>
                        <td><?= h(format_credit((float) $work['CREDIT_AMOUNT'])) ?></td>
                        <td>
                            <span class="badge <?= h(status_badge_class($work['STATUS'])) ?>">
                                <?= h($work['STATUS']) ?>
                            </span>
                        </td>
                        <td>
                            <?= (int) $work['payment_released'] === 1 ? 'Released' : 'Not Released' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
