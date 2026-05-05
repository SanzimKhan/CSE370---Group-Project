<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/credits.php';

$user = require_login();
$error = null;

if (is_post_request()) {
    enforce_csrf_or_fail('profile.php');

    $fullName = trim($_POST['full_name'] ?? '');
    $mobileNumber = trim($_POST['mobile_number'] ?? '');
    $addressLine = trim($_POST['address_line'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $preferredMode = normalize_user_mode(trim($_POST['preferred_mode'] ?? 'hiring'));

    if ($fullName === '' || strlen($fullName) < 2) {
        $error = 'Please enter your full name (minimum 2 characters).';
    } elseif (strlen($fullName) > 120) {
        $error = 'Full name is too long.';
    } elseif ($mobileNumber === '' || !preg_match('/^[0-9+\-\s]{7,20}$/', $mobileNumber)) {
        $error = 'Please enter a valid phone number.';
    } elseif (strlen($addressLine) > 255) {
        $error = 'Address is too long (max 255 characters).';
    } elseif (strlen($bio) > 1000) {
        $error = 'Bio is too long (max 1000 characters).';
    } elseif (strlen($skills) > 1000) {
        $error = 'Skills are too long (max 1000 characters).';
    }

    $avatarPath = $user['avatar_path'] ?? null;

    if ($error === null && isset($_FILES['avatar']) && (int) ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $avatarError = (int) ($_FILES['avatar']['error'] ?? UPLOAD_ERR_OK);
        if ($avatarError !== UPLOAD_ERR_OK) {
            $error = 'Could not upload profile image. Please try again.';
        } else {
            $tmpPath = (string) ($_FILES['avatar']['tmp_name'] ?? '');
            $fileSize = (int) ($_FILES['avatar']['size'] ?? 0);

            if ($fileSize <= 0 || $fileSize > 2 * 1024 * 1024) {
                $error = 'Profile image must be less than or equal to 2MB.';
            } elseif (!is_uploaded_file($tmpPath)) {
                $error = 'Invalid image upload.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($tmpPath) ?: '';
                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];

                if (!isset($allowedTypes[$mimeType])) {
                    $error = 'Only JPG, PNG, or WEBP images are allowed.';
                } else {
                    $uploadDir = __DIR__ . '/assets/uploads/avatars';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }

                    $extension = $allowedTypes[$mimeType];
                    $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;
                    $destination = $uploadDir . '/' . $newFileName;

                    if (!move_uploaded_file($tmpPath, $destination)) {
                        $error = 'Failed to save profile image.';
                    } else {
                        $newAvatarPath = 'assets/uploads/avatars/' . $newFileName;

                        $oldAvatarPath = (string) ($user['avatar_path'] ?? '');
                        if ($oldAvatarPath !== '' && str_starts_with($oldAvatarPath, 'assets/uploads/avatars/')) {
                            $oldAbsolutePath = __DIR__ . '/' . $oldAvatarPath;
                            if (is_file($oldAbsolutePath)) {
                                @unlink($oldAbsolutePath);
                            }
                        }

                        $avatarPath = $newAvatarPath;
                    }
                }
            }
        }
    }

    if ($error === null) {
        $updateStatement = db()->prepare(
            'UPDATE `User`
             SET full_name = :full_name,
                 mobile_number = :mobile_number,
                 address_line = :address_line,
                 bio = :bio,
                 skills = :skills,
                 avatar_path = :avatar_path,
                 preferred_mode = :preferred_mode
             WHERE BRACU_ID = :id'
        );

        $updateStatement->execute([
            'full_name' => $fullName,
            'mobile_number' => $mobileNumber,
            'address_line' => $addressLine !== '' ? $addressLine : null,
            'bio' => $bio !== '' ? $bio : null,
            'skills' => $skills !== '' ? $skills : null,
            'avatar_path' => $avatarPath,
            'preferred_mode' => $preferredMode,
            'id' => $user['BRACU_ID'],
        ]);

        set_active_user_mode($preferredMode);
        set_flash('success', 'Profile updated successfully.');
        redirect('profile.php');
    }
}

$user = require_login();
$fullNameValue = (string) ($user['full_name'] ?? '');
$addressValue = (string) ($user['address_line'] ?? '');
$bioValue = (string) ($user['bio'] ?? '');
$skillsValue = (string) ($user['skills'] ?? '');
$avatarSrc = (string) ($user['avatar_path'] ?? '');
$preferredModeValue = normalize_user_mode((string) ($user['preferred_mode'] ?? active_user_mode($user)));

$pageTitle = 'Profile & Wallet';
require_once __DIR__ . '/includes/header.php';
?>
<section class="card">
    <div class="kicker">Profile</div>
    <h1><?= h($user['BRACU_ID']) ?></h1>
    <p class="muted">Complete your profile like a real freelance platform account.</p>

    <?php if ($error): ?>
        <div class="flash flash-error"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="profile-grid">
        <div class="card">
            <h2>Profile Preview</h2>
            <div class="avatar-wrap">
                <?php if ($avatarSrc !== ''): ?>
                    <img class="avatar-preview" src="<?= h(BASE_URL . ltrim($avatarSrc, '/')) ?>" alt="Profile picture">
                <?php else: ?>
                    <div class="avatar-fallback"><?= h(strtoupper(substr($fullNameValue !== '' ? $fullNameValue : $user['BRACU_ID'], 0, 1))) ?></div>
                <?php endif; ?>
            </div>
            <p><strong><?= h($fullNameValue !== '' ? $fullNameValue : $user['BRACU_ID']) ?></strong></p>
            <p class="muted"><?= h($user['Bracu_mail']) ?></p>
            <p class="muted">Phone: <?= h($user['mobile_number']) ?></p>
            <p class="muted">Address: <?= h($addressValue !== '' ? $addressValue : 'Not set') ?></p>
            <p class="muted">Skills: <?= h($skillsValue !== '' ? $skillsValue : 'Not set') ?></p>
            <p class="muted">Mode: <?= h($preferredModeValue === 'hiring' ? 'Hiring (Post jobs)' : 'Working (Apply to jobs)') ?></p>
            <p class="muted">Wallet: <strong style="color: #28a745;">৳<?= number_format((float) $user['credit_balance'], 2) ?></strong></p>
            <p style="margin-top: 0.75rem;">
                <a class="btn btn-ghost" href="public_profile.php?id=<?= urlencode($user['BRACU_ID']) ?>" target="_blank">Public Career Profile</a>
            </p>
        </div>

        <div class="card">
            <h2>💰 Credit Wallet</h2>
            <div style="margin-bottom: 1rem;">
                <p class="muted">Current Balance</p>
                <p style="font-size: 1.8em; font-weight: bold; color: #28a745;">৳<?= number_format(get_user_credit_balance($user['BRACU_ID']), 2) ?></p>
            </div>
            <p>
                <a class="btn btn-ghost" href="credits/history.php" style="width: 100%; text-align: center; margin-bottom: 0.5rem;">📊 View History</a>
                <a class="btn btn-ghost" href="public_profile.php?id=<?= urlencode($user['BRACU_ID']) ?>" style="width: 100%; text-align: center;">🌐 Public Profile</a>
            </p>
        </div>

        <div class="card">
            <h2>Edit Profile</h2>
            <form method="post" action="profile.php" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="form-row">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" maxlength="120" value="<?= h($fullNameValue) ?>" required>
                </div>
                <div class="form-row">
                    <label for="mobile_number">Phone Number</label>
                    <input type="text" id="mobile_number" name="mobile_number" maxlength="20" value="<?= h((string) $user['mobile_number']) ?>" required>
                </div>
                <div class="form-row">
                    <label for="address_line">Address</label>
                    <input type="text" id="address_line" name="address_line" maxlength="255" value="<?= h($addressValue) ?>" placeholder="House, Road, Area, City">
                </div>
                <div class="form-row">
                    <label for="bio">About You</label>
                    <textarea id="bio" name="bio" maxlength="1000" placeholder="Write a short professional bio..."><?= h($bioValue) ?></textarea>
                </div>
                <div class="form-row">
                    <label for="skills">Skills</label>
                    <textarea id="skills" name="skills" maxlength="1000" placeholder="Example: React, Node.js, UI/UX, Data Analysis"><?= h($skillsValue) ?></textarea>
                </div>
                <div class="form-row">
                    <label for="preferred_mode">Default Login Mode</label>
                    <select id="preferred_mode" name="preferred_mode" required>
                        <option value="hiring" <?= $preferredModeValue === 'hiring' ? 'selected' : '' ?>>Hiring (Post jobs)</option>
                        <option value="working" <?= $preferredModeValue === 'working' ? 'selected' : '' ?>>Working (Apply to jobs)</option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="avatar">Profile Picture (JPG, PNG, WEBP, max 2MB)</label>
                    <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
                </div>
                <button type="submit">Save Profile</button>
            </form>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
