<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function find_user_by_bracu_id(string $bracuId): ?array
{
    $statement = db()->prepare('SELECT * FROM `User` WHERE BRACU_ID = :bracu_id LIMIT 1');
    $statement->execute(['bracu_id' => $bracuId]);
    $user = $statement->fetch();

    return $user ?: null;
}

function authenticate_user(string $bracuId, string $password): ?array
{
    $user = find_user_by_bracu_id($bracuId);

    if (!$user || !password_verify($password, $user['password'])) {
        return null;
    }

    unset($user['password']);

    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    reset_login_failures();
    $_SESSION['user_bracu_id'] = $user['BRACU_ID'];
    set_active_user_mode((string) ($user['preferred_mode'] ?? 'hiring'));
}

function current_user(): ?array
{
    if (empty($_SESSION['user_bracu_id'])) {
        return null;
    }

    $user = find_user_by_bracu_id($_SESSION['user_bracu_id']);

    if (!$user) {
        logout_user();
        return null;
    }

    unset($user['password']);

    return $user;
}

function require_login(): array
{
    $user = current_user();

    if (!$user) {
        set_flash('error', 'Please log in with your BRACU credentials.');
        redirect('index.php');
    }

    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    return $user;
}

function current_user_is_admin(?array $user = null): bool
{
    $target = $user ?? current_user();

    if (!$target) {
        return false;
    }

    return ((int) ($target['is_admin'] ?? 0)) === 1;
}

function require_admin(): array
{
    $user = require_login();

    if (!current_user_is_admin($user)) {
        set_flash('error', 'Admin access is required for that page.');
        redirect('dashboard.php');
    }

    return $user;
}

function normalize_user_mode(string $mode): string
{
    return in_array($mode, ['hiring', 'working'], true) ? $mode : 'hiring';
}

function set_active_user_mode(string $mode): void
{
    $_SESSION['active_user_mode'] = normalize_user_mode($mode);
}

function active_user_mode(?array $user = null): string
{
    $sessionMode = $_SESSION['active_user_mode'] ?? '';
    if (is_string($sessionMode) && in_array($sessionMode, ['hiring', 'working'], true)) {
        return $sessionMode;
    }

    $target = $user ?? current_user();
    $fallback = normalize_user_mode((string) ($target['preferred_mode'] ?? 'hiring'));
    $_SESSION['active_user_mode'] = $fallback;

    return $fallback;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function login_throttle_state(): array
{
    if (!isset($_SESSION['login_throttle']) || !is_array($_SESSION['login_throttle'])) {
        $_SESSION['login_throttle'] = [
            'attempts' => 0,
            'lock_until' => 0,
        ];
    }

    return $_SESSION['login_throttle'];
}

function is_login_temporarily_locked(): bool
{
    $state = login_throttle_state();

    return (int) ($state['lock_until'] ?? 0) > time();
}

function login_lock_remaining_seconds(): int
{
    $state = login_throttle_state();
    $remaining = (int) ($state['lock_until'] ?? 0) - time();

    return max(0, $remaining);
}

function register_login_failure(): void
{
    $state = login_throttle_state();
    $attempts = (int) ($state['attempts'] ?? 0) + 1;
    $lockUntil = (int) ($state['lock_until'] ?? 0);

    if ($attempts >= LOGIN_MAX_ATTEMPTS) {
        $lockUntil = time() + LOGIN_LOCKOUT_SECONDS;
        $attempts = 0;
    }

    $_SESSION['login_throttle'] = [
        'attempts' => $attempts,
        'lock_until' => $lockUntil,
    ];
}

function reset_login_failures(): void
{
    unset($_SESSION['login_throttle']);
}

/**
 * Register a new user in the database.
 * Returns the created user array (without password) on success, or null on failure.
 */
function register_user(string $bracuId, string $email, string $plainPassword, string $fullName = '', string $mobileNumber = '', string $preferredMode = 'hiring'): ?array
{
    $bracuId = normalize_bracu_id($bracuId);
    if ($bracuId === '' || !is_valid_bracu_id($bracuId)) {
        return null;
    }

    $email = trim($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $preferredMode = normalize_user_mode($preferredMode);

    // Ensure BRACU ID or email are not already taken
    $existing = db()->prepare('SELECT BRACU_ID FROM `User` WHERE BRACU_ID = :id OR Bracu_mail = :mail LIMIT 1');
    $existing->execute(['id' => $bracuId, 'mail' => $email]);
    if ($existing->fetch()) {
        return null;
    }

    $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);

    $insert = db()->prepare(
        'INSERT INTO `User` (BRACU_ID, Bracu_mail, full_name, client, mobile_number, password, freelancer, preferred_mode, is_admin, credit_balance)
         VALUES (:bracu_id, :email, :full_name, :client, :mobile_number, :password, :freelancer, :preferred_mode, :is_admin, :credit_balance)'
    );

    try {
        $insert->execute([
            'bracu_id' => $bracuId,
            'email' => $email,
            'full_name' => $fullName,
            'client' => 1,
            'mobile_number' => $mobileNumber,
            'password' => $passwordHash,
            'freelancer' => 1,
            'preferred_mode' => $preferredMode,
            'is_admin' => 0,
            'credit_balance' => '500.00',
        ]);
    } catch (PDOException $e) {
        // On constraint violation or other DB error, fail gracefully
        error_log('User registration failed: ' . $e->getMessage());
        return null;
    }

    $user = find_user_by_bracu_id($bracuId);
    if ($user) {
        unset($user['password']);
    }

    return $user ?: null;
}
