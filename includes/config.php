<?php
declare(strict_types=1);

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

$httpsEnabled = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) == 443);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $httpsEnabled ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');
session_name(env_value('SESSION_NAME', 'bracu_marketplace_sid'));

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', env_value('APP_NAME', 'BRACU Student Freelance Marketplace'));
define('APP_ENV', env_value('APP_ENV', 'production'));
define('APP_DEBUG', env_value('APP_DEBUG', '0') === '1');

ini_set('log_errors', '1');
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) env_value('DB_PORT', '3306'));
define('DB_NAME', env_value('DB_NAME', 'bracu_freelance_marketplace'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS', ''));

define('LOGIN_MAX_ATTEMPTS', max(1, (int) env_value('LOGIN_MAX_ATTEMPTS', '5')));
define('LOGIN_LOCKOUT_SECONDS', max(30, (int) env_value('LOGIN_LOCKOUT_SECONDS', '300')));

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data:; connect-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self';");

    if ($httpsEnabled) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
$appRoot = realpath(dirname(__DIR__)) ?: '';
$baseUrl = '/';

if ($documentRoot !== '' && $appRoot !== '') {
    $normalizedDocumentRoot = str_replace('\\', '/', $documentRoot);
    $normalizedAppRoot = str_replace('\\', '/', $appRoot);

    if (str_starts_with(strtolower($normalizedAppRoot), strtolower($normalizedDocumentRoot))) {
        $relativePath = trim(substr($normalizedAppRoot, strlen($normalizedDocumentRoot)), '/');
        $baseUrl = $relativePath === '' ? '/' : '/' . $relativePath . '/';
    }
}

define('BASE_URL', $baseUrl);

date_default_timezone_set('Asia/Dhaka');
