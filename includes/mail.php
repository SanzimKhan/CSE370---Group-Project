<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function send_notification_email(string $to, string $subject, string $message): bool
{
    $headers = [
        'From: noreply@bracu-marketplace.local',
        'Reply-To: noreply@bracu-marketplace.local',
        'X-Mailer: PHP/' . phpversion(),
        'Content-Type: text/plain; charset=UTF-8',
    ];

    $sent = @mail($to, $subject, $message, implode("\r\n", $headers));

    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $line = sprintf(
        "[%s] to=%s subject=%s status=%s%s",
        date('Y-m-d H:i:s'),
        $to,
        $subject,
        $sent ? 'sent' : 'logged-only',
        PHP_EOL
    );

    file_put_contents($logDir . '/mail.log', $line, FILE_APPEND);

    return $sent;
}
