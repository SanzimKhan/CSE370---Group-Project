<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

logout_user();
header('Location: index.php?logged_out=1');
exit;
