<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = null;
$bracuId = '';
$email = '';
$fullName = '';
$mobileNumber = '';
$preferredMode = 'hiring';

if (is_post_request()) {
    enforce_csrf_or_fail('signup.php');

    $bracuId = normalize_bracu_id(trim($_POST['bracu_id'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $mobileNumber = trim($_POST['mobile_number'] ?? '');
    $preferredMode = normalize_user_mode(trim($_POST['preferred_mode'] ?? 'hiring'));

    if ($bracuId === '' || $email === '' || $password === '') {
        $error = 'BRACU ID, email and password are required.';
    } elseif (!is_valid_bracu_id($bracuId)) {
        $error = 'BRACU ID must be exactly 8 digits.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        $created = register_user($bracuId, $email, $password, $fullName, $mobileNumber, $preferredMode);

        if ($created) {
            set_flash('success', 'Account created. You can now log in.');
            redirect('index.php');
        }

        $error = 'Could not create account. BRACU ID or email may already be in use.';
    }
}

$pageTitle = 'Sign Up';
require_once __DIR__ . '/includes/header.php';
?>
<section class="card login-card">
    <div class="kicker">BRAC University</div>
    <h1>Create an Account</h1>
    <p class="muted">Register using your BRACU ID to join the marketplace.</p>

    <?php if ($error): ?>
        <div class="flash flash-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="signup.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <label for="bracu_id">BRACU ID</label>
            <input type="text" id="bracu_id" name="bracu_id" maxlength="8" value="<?= h($bracuId) ?>" required>
        </div>
        <div class="form-row">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= h($email) ?>" required>
        </div>
        <div class="form-row">
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" value="<?= h($fullName) ?>">
        </div>
        <div class="form-row">
            <label for="mobile_number">Mobile number</label>
            <input type="text" id="mobile_number" name="mobile_number" value="<?= h($mobileNumber) ?>">
        </div>
        <div class="form-row">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-row">
            <label for="preferred_mode">Account mode</label>
            <select id="preferred_mode" name="preferred_mode" required>
                <option value="hiring" <?= $preferredMode === 'hiring' ? 'selected' : '' ?>>Hiring (post jobs)</option>
                <option value="working" <?= $preferredMode === 'working' ? 'selected' : '' ?>>Working (apply to jobs)</option>
            </select>
        </div>
        <button type="submit">Create Account</button>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

