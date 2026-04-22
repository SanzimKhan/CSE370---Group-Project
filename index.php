<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = null;
$bracuId = '';
$accountMode = 'hiring';

if (is_login_temporarily_locked()) {
    $error = 'Too many failed attempts. Try again in ' . login_lock_remaining_seconds() . ' seconds.';
}

if (is_post_request()) {
    enforce_csrf_or_fail('index.php');

    $bracuId = normalize_bracu_id(trim($_POST['bracu_id'] ?? ''));
    $password = $_POST['password'] ?? '';
    $accountMode = normalize_user_mode(trim($_POST['account_mode'] ?? 'hiring'));

    if (is_login_temporarily_locked()) {
        $error = 'Too many failed attempts. Try again in ' . login_lock_remaining_seconds() . ' seconds.';
    } elseif ($bracuId === '' || $password === '') {
        $error = 'BRACU ID and password are required.';
    } elseif (!is_valid_bracu_id($bracuId)) {
        $error = 'BRACU ID must be exactly 8 digits.';
    } else {
        $user = authenticate_user($bracuId, $password);

        if ($user) {
            login_user($user);

            $modeUpdateStatement = db()->prepare(
                'UPDATE `User` SET preferred_mode = :mode WHERE BRACU_ID = :id'
            );
            $modeUpdateStatement->execute([
                'mode' => $accountMode,
                'id' => $user['BRACU_ID'],
            ]);

            set_active_user_mode($accountMode);
            set_flash('success', 'Welcome back!');

            if ($accountMode === 'hiring') {
                redirect('client/create_gig.php');
            }

            redirect('freelancer/marketplace.php');
        }

        register_login_failure();
        if (is_login_temporarily_locked()) {
            $error = 'Too many failed attempts. Try again in ' . login_lock_remaining_seconds() . ' seconds.';
        } else {
            $error = 'Invalid BRACU ID or password.';
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>
<section class="card login-card">
    <div class="kicker">BRAC University</div>
    <h1>Student Freelance Marketplace</h1>
    <p class="muted">Sign in with your BRACU ID and password to continue as client or freelancer.</p>

    <?php if (isset($_GET['logged_out'])): ?>
        <div class="flash flash-info">You have logged out successfully.</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="flash flash-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="index.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <label for="bracu_id">BRACU ID</label>
            <input type="text" id="bracu_id" name="bracu_id" placeholder="e.g., 20101001" maxlength="8" value="<?= h($bracuId) ?>" required>
        </div>
        <div class="form-row">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter password" required>
        </div>
        <div class="form-row">
            <label for="account_mode">I want to use this account for</label>
            <select id="account_mode" name="account_mode" required>
                <option value="hiring" <?= $accountMode === 'hiring' ? 'selected' : '' ?>>Hiring (Post jobs)</option>
                <option value="working" <?= $accountMode === 'working' ? 'selected' : '' ?>>Working (Apply to jobs)</option>
            </select>
        </div>
        <button type="submit">Login</button>
    </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
