<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

if (!headers_sent()) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
}

try {
    db()->query('SELECT 1');

    echo json_encode([
        'status' => 'ok',
        'app' => APP_NAME,
        'time' => date(DATE_ATOM),
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(503);

    echo json_encode([
        'status' => 'degraded',
        'app' => APP_NAME,
        'time' => date(DATE_ATOM),
    ], JSON_THROW_ON_ERROR);
}
