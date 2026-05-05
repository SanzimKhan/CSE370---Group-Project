<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/credits.php';

$pageTitle = $pageTitle ?? APP_NAME;
$activeUser = current_user();
$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> | <?= h(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Barlow+Condensed:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/styles.css">
</head>
<body>
<div class="bg-shape bg-shape-one"></div>
<div class="bg-shape bg-shape-two"></div>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="<?= BASE_URL ?>dashboard.php">BRACU Freelance Hub</a>
        <?php if ($activeUser): ?>
            <nav class="nav-links">
                <a href="<?= BASE_URL ?>dashboard.php">Dashboard</a>
                <a href="<?= BASE_URL ?>profile.php">Profile</a>
                <a href="<?= BASE_URL ?>public_profile.php?id=<?= urlencode($activeUser['BRACU_ID']) ?>" target="_blank">Public Profile</a>
                
                <!-- Credit Wallet -->
                <a href="<?= BASE_URL ?>credits/topup.php" style="color: #28a745; font-weight: 600;">
                    💰 ৳<?= number_format(get_user_credit_balance($activeUser['BRACU_ID']), 0) ?>
                </a>
                
                <!-- Analytics -->
                <?php if ($activeUser['preferred_mode'] === 'working'): ?>
                    <a href="<?= BASE_URL ?>freelancer/analytics.php">📊 Analytics</a>
                <?php elseif ($activeUser['preferred_mode'] === 'hiring'): ?>
                    <a href="<?= BASE_URL ?>client/analytics.php">📊 Analytics</a>
                <?php endif; ?>
                
                <!-- Search -->
                <a href="<?= BASE_URL ?>search.php">🔍 Search</a>
                
                <!-- Community -->
                <a href="<?= BASE_URL ?>community/forum.php">💬 Forum</a>
                <a href="<?= BASE_URL ?>community/messages_inbox.php">✉️ Messages</a>
                
                <?php if (current_user_is_admin($activeUser)): ?>
                    <a href="<?= BASE_URL ?>admin/manage_admins.php">Admin</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>client/create_gig.php">Request Gig</a>
                <a href="<?= BASE_URL ?>client/my_gigs.php">Listed Gigs</a>
                <a href="<?= BASE_URL ?>freelancer/marketplace.php">Available Gigs</a>
                <a href="<?= BASE_URL ?>freelancer/my_work.php">My Work</a>
                <a class="danger-link" href="<?= BASE_URL ?>logout.php">Logout</a>
            </nav>
        <?php endif; ?>
    </div>
</header>
<main class="container content">
    <?php if ($flash): ?>
        <div class="flash flash-<?= h($flash['type']) ?>">
            <?= h($flash['message']) ?>
        </div>
    <?php endif; ?>
