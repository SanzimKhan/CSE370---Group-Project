<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function is_post_request(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function format_credit(float $amount): string
{
    return number_format($amount, 2) . ' credits';
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'listed' => 'badge-listed',
        'pending' => 'badge-pending',
        'done' => 'badge-done',
        default => 'badge-listed',
    };
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function is_valid_csrf_token(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sessionToken) || $sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

function enforce_csrf_or_fail(string $redirectPath): void
{
    if (!is_valid_csrf_token($_POST['_csrf'] ?? null)) {
        set_flash('error', 'Your session token is invalid or expired. Please try again.');
        redirect($redirectPath);
    }
}

function normalize_bracu_id(string $value): string
{
    $normalized = preg_replace('/\s+/', '', $value);

    return $normalized ?? '';
}

function is_valid_bracu_id(string $value): bool
{
    return (bool) preg_match('/^\d{8}$/', $value);
}

function is_valid_ymd_date(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);

    return $date instanceof DateTime && $date->format('Y-m-d') === $value;
}
