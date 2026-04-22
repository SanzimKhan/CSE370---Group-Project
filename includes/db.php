<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

    try {
        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ]
        );
    } catch (PDOException $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());

        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Database Connection Error</title>';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="font-family: Arial, sans-serif; margin: 2rem;">';
        echo '<h1>Database Connection Error</h1>';
        echo '<p>Could not connect to MySQL. Please ensure MySQL is running and your database settings are correct.</p>';
        echo '<p>Expected connection: ' . htmlspecialchars(DB_HOST . ':' . (string) DB_PORT, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p>If you use XAMPP, start MySQL from the XAMPP Control Panel, then refresh this page.</p>';
        if (APP_DEBUG) {
            echo '<p><strong>Debug:</strong> ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        }
        echo '</body></html>';
        exit;
    }

    return $pdo;
}
