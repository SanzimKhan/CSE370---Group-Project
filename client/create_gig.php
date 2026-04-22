<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

$user = require_login();
$error = null;

if (is_post_request()) {
    enforce_csrf_or_fail('client/create_gig.php');

    $description = trim($_POST['description'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');
    $creditAmount = (float) ($_POST['credit_amount'] ?? 0);
    $category = trim($_POST['category'] ?? '');

    $validCategories = ['IT', 'Writing', 'Others'];

    if ($description === '' || strlen($description) < 15) {
        $error = 'Please provide a meaningful gig description (minimum 15 characters).';
    } elseif (strlen($description) > 2000) {
        $error = 'Description is too long. Keep it under 2000 characters.';
    } elseif (!in_array($category, $validCategories, true)) {
        $error = 'Please choose a valid gig category.';
    } elseif ($creditAmount <= 0) {
        $error = 'Credit amount must be greater than zero.';
    } elseif ($creditAmount > 100000) {
        $error = 'Credit amount is too high for a single gig.';
    } elseif (!is_valid_ymd_date($deadline)) {
        $error = 'Please select a valid deadline date.';
    } elseif ($deadline < date('Y-m-d')) {
        $error = 'Deadline cannot be in the past.';
    } else {
        $statement = db()->prepare(
            'INSERT INTO `Gigs` (BRACU_ID, CREDIT_AMOUNT, LIST_OF_GIGS, CATAGORY, DEADLINE, STATUS)
             VALUES (:bracu_id, :credit_amount, :description, :category, :deadline, :status)'
        );

        $statement->execute([
            'bracu_id' => $user['BRACU_ID'],
            'credit_amount' => $creditAmount,
            'description' => $description,
            'category' => $category,
            'deadline' => $deadline,
            'status' => 'listed',
        ]);

        set_flash('success', 'Gig posted successfully and listed in the marketplace.');
        redirect('client/my_gigs.php');
    }
}

$pageTitle = 'Request Gig';
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="card">
    <div class="kicker">Client Tool</div>
    <h1>Request Your Gig</h1>
    <p class="muted">Create a new request with description, deadline, payment credit, and category.</p>

    <?php if ($error): ?>
        <div class="flash flash-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="create_gig.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <label for="description">Description</label>
            <textarea id="description" name="description" maxlength="2000" placeholder="Explain what you need done..." required><?= h($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="grid cols-2">
            <div class="form-row">
                <label for="deadline">Deadline</label>
                <input type="date" id="deadline" name="deadline" value="<?= h($_POST['deadline'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <label for="credit_amount">Credit Amount</label>
                <input type="number" id="credit_amount" name="credit_amount" min="1" step="0.01" value="<?= h($_POST['credit_amount'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="">Select category</option>
                <?php foreach (['IT', 'Writing', 'Others'] as $item): ?>
                    <option value="<?= h($item) ?>" <?= (($_POST['category'] ?? '') === $item) ? 'selected' : '' ?>>
                        <?= h($item) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">Post Gig</button>
    </form>
</section>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
