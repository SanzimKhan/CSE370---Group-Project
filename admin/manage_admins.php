<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

$adminUser = require_admin();

if (is_post_request()) {
    enforce_csrf_or_fail('admin/manage_admins.php');

    $action = trim($_POST['action'] ?? '');
    $targetBracuId = normalize_bracu_id(trim($_POST['target_bracu_id'] ?? ''));

    if (!is_valid_bracu_id($targetBracuId)) {
        set_flash('error', 'Invalid BRACU ID selected for admin action.');
        redirect('admin/manage_admins.php');
    }

    $targetStatement = db()->prepare('SELECT BRACU_ID, is_admin FROM `User` WHERE BRACU_ID = :id LIMIT 1');
    $targetStatement->execute(['id' => $targetBracuId]);
    $targetUser = $targetStatement->fetch();

    if (!$targetUser) {
        set_flash('error', 'Selected user not found.');
        redirect('admin/manage_admins.php');
    }

    if ($action === 'promote') {
        if ((int) ($targetUser['is_admin'] ?? 0) === 1) {
            set_flash('info', 'This user is already an admin.');
            redirect('admin/manage_admins.php');
        }

        $promoteStatement = db()->prepare('UPDATE `User` SET is_admin = 1 WHERE BRACU_ID = :id');
        $promoteStatement->execute(['id' => $targetBracuId]);

        set_flash('success', 'User promoted to admin successfully.');
        redirect('admin/manage_admins.php');
    }

    if ($action === 'demote') {
        if ($targetBracuId === $adminUser['BRACU_ID']) {
            set_flash('error', 'You cannot remove your own admin access.');
            redirect('admin/manage_admins.php');
        }

        if ((int) ($targetUser['is_admin'] ?? 0) !== 1) {
            set_flash('info', 'This user is not an admin.');
            redirect('admin/manage_admins.php');
        }

        $adminCountStatement = db()->query('SELECT COUNT(*) AS total FROM `User` WHERE is_admin = 1');
        $adminCount = (int) $adminCountStatement->fetchColumn();
        if ($adminCount <= 1) {
            set_flash('error', 'At least one admin must remain in the system.');
            redirect('admin/manage_admins.php');
        }

        $demoteStatement = db()->prepare('UPDATE `User` SET is_admin = 0 WHERE BRACU_ID = :id');
        $demoteStatement->execute(['id' => $targetBracuId]);

        set_flash('success', 'Admin access removed for selected user.');
        redirect('admin/manage_admins.php');
    }

    set_flash('error', 'Unsupported admin action.');
    redirect('admin/manage_admins.php');
}

$usersStatement = db()->query(
    'SELECT BRACU_ID, Bracu_mail, mobile_number, credit_balance, is_admin, client, freelancer
     FROM `User`
     ORDER BY is_admin DESC, BRACU_ID ASC'
);
$users = $usersStatement->fetchAll();

$adminCountStatement = db()->query('SELECT COUNT(*) AS total FROM `User` WHERE is_admin = 1');
$adminCount = (int) $adminCountStatement->fetchColumn();

$pageTitle = 'Admin Management';
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="card">
    <div class="kicker">Admin Console</div>
    <h1>Manage Admins</h1>
    <p class="muted">Promote or demote users to control who can access admin tools.</p>

    <div class="stats" style="margin-bottom: 1rem;">
        <div class="stat">
            <div class="label">Total Users</div>
            <div class="value"><?= count($users) ?></div>
        </div>
        <div class="stat">
            <div class="label">Current Admins</div>
            <div class="value"><?= $adminCount ?></div>
        </div>
        <div class="stat">
            <div class="label">You</div>
            <div class="value" style="font-size:1rem"><?= h($adminUser['BRACU_ID']) ?></div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>BRACU ID</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Roles</th>
                    <th>Wallet</th>
                    <th>Admin</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td><?= h($row['BRACU_ID']) ?></td>
                        <td><?= h($row['Bracu_mail']) ?></td>
                        <td><?= h($row['mobile_number']) ?></td>
                        <td>
                            <?= (int) $row['client'] === 1 ? 'Client ' : '' ?>
                            <?= (int) $row['freelancer'] === 1 ? 'Freelancer' : '' ?>
                        </td>
                        <td><?= h(format_credit((float) $row['credit_balance'])) ?></td>
                        <td>
                            <span class="badge <?= (int) $row['is_admin'] === 1 ? 'badge-done' : 'badge-listed' ?>">
                                <?= (int) $row['is_admin'] === 1 ? 'Yes' : 'No' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ((int) $row['is_admin'] === 1): ?>
                                <?php if ($row['BRACU_ID'] === $adminUser['BRACU_ID']): ?>
                                    <span class="muted">Current Admin</span>
                                <?php else: ?>
                                    <form class="inline-form" method="post" action="manage_admins.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="demote">
                                        <input type="hidden" name="target_bracu_id" value="<?= h($row['BRACU_ID']) ?>">
                                        <button type="submit">Remove Admin</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <form class="inline-form" method="post" action="manage_admins.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="promote">
                                    <input type="hidden" name="target_bracu_id" value="<?= h($row['BRACU_ID']) ?>">
                                    <button type="submit">Make Admin</button>
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
