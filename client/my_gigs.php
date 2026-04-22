<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/wallet.php';
require_once dirname(__DIR__) . '/includes/mail.php';

$user = require_login();

if (is_post_request()) {
    enforce_csrf_or_fail('client/my_gigs.php');

    $action = $_POST['action'] ?? '';
    $gigId = (int) ($_POST['gid'] ?? 0);

    if ($action === 'mark_done' && $gigId > 0) {
        $result = mark_gig_done_and_release_payment($gigId, $user['BRACU_ID']);

        if ($result['ok']) {
            $freelancer = find_user_by_bracu_id($result['freelancer_id']);

            if ($freelancer) {
                $subject = 'Gig #' . $gigId . ' has been marked done';
                $clientMessage = "You confirmed completion for Gig #{$gigId}. {$result['amount']} credits were transferred from your wallet.";
                $freelancerMessage = "Congratulations! Gig #{$gigId} is marked done. {$result['amount']} credits were added to your wallet.";

                send_notification_email($user['Bracu_mail'], $subject, $clientMessage);
                send_notification_email($freelancer['Bracu_mail'], $subject, $freelancerMessage);
            }

            set_flash('success', $result['message']);
        } else {
            set_flash('error', $result['message']);
        }
    }

    redirect('client/my_gigs.php');
}

$statement = db()->prepare(
    'SELECT g.*, w.BRACU_ID AS freelancer_id, w.payment_released, w.done_at
     FROM `Gigs` g
     LEFT JOIN `Working_on` w ON w.GID = g.GID
     WHERE g.BRACU_ID = :id
     ORDER BY g.created_at DESC'
);
$statement->execute(['id' => $user['BRACU_ID']]);
$gigs = $statement->fetchAll();

$pageTitle = 'Listed Gigs';
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="card">
    <div class="kicker">Client Tool</div>
    <h1>Listed Gigs</h1>
    <p class="muted">Track your gig status as listed, pending, or done. Mark pending gigs as done to release payment.</p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>GID</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Deadline</th>
                    <th>Credit</th>
                    <th>Status</th>
                    <th>Freelancer</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$gigs): ?>
                    <tr>
                        <td colspan="8" class="muted">No gigs posted yet.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($gigs as $gig): ?>
                    <tr>
                        <td>#<?= (int) $gig['GID'] ?></td>
                        <td><?= h($gig['LIST_OF_GIGS']) ?></td>
                        <td><?= h($gig['CATAGORY']) ?></td>
                        <td><?= h($gig['DEADLINE']) ?></td>
                        <td><?= h(format_credit((float) $gig['CREDIT_AMOUNT'])) ?></td>
                        <td>
                            <span class="badge <?= h(status_badge_class($gig['STATUS'])) ?>">
                                <?= h($gig['STATUS']) ?>
                            </span>
                        </td>
                        <td><?= h($gig['freelancer_id'] ?? '-') ?></td>
                        <td>
                            <?php if ($gig['STATUS'] === 'pending'): ?>
                                <form class="inline-form" method="post" action="my_gigs.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="mark_done">
                                    <input type="hidden" name="gid" value="<?= (int) $gig['GID'] ?>">
                                    <button class="btn-success" type="submit">Mark as Done</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
