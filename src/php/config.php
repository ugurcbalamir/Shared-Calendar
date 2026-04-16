<?php
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$detectedBase = $scriptName ? rtrim(str_replace('index.php', '', $scriptName), '/') : '';

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'croissant_schedule',
        'user' => getenv('DB_USER') ?: 'croissant_user',
        'password' => getenv('DB_PASSWORD') ?: 'change-me',
        'charset' => 'utf8mb4'
    ],
    'smtp' => [
        'host' => getenv('SMTP_HOST') ?: 'smtp.example.com',
        'port' => (int)(getenv('SMTP_PORT') ?: 587),
        'username' => getenv('SMTP_USER') ?: 'smtp-user@example.com',
        'password' => getenv('SMTP_PASSWORD') ?: 'change-me',
        'from' => 'Croissant Schedule <noreply@example.com>'
    ],
    'languages' => ['en', 'es', 'fr', 'tr'],
    'default_language' => 'en',
    'base_path' => rtrim(getenv('APP_BASE_PATH') ?: $detectedBase, '/')
];
