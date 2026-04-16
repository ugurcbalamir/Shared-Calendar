<?php
require_once __DIR__ . '/database.php';

if (!isset($config)) {
    $config = require __DIR__ . '/config.php';
}

function find_user_by_email(string $email): ?array
{
    $stmt = get_pdo()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_user_by_username(string $username): ?array
{
    $stmt = get_pdo()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_user_by_id(int $id): ?array
{
    $stmt = get_pdo()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_user(string $email, string $username, string $password): array
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = get_pdo()->prepare('INSERT INTO users (email, username, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$email, $username, $hash]);
    $id = (int) get_pdo()->lastInsertId();
    return ['id' => $id, 'email' => $email, 'username' => $username];
}

function update_profile(int $userId, ?string $username, ?string $password): array
{
    if ($username) {
        $stmt = get_pdo()->prepare('UPDATE users SET username = ? WHERE id = ?');
        $stmt->execute([$username, $userId]);
    }
    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = get_pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $userId]);
    }
    return find_user_by_id($userId) ?? ['id' => $userId, 'username' => $username];
}

function require_auth(): ?array
{
    global $config;
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    $base = $config['base_path'] ?? '';
    header('Location: ' . $base . '/login');
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function create_reset_token(int $userId): string
{
    $pdo = get_pdo();

    // Invalidate old tokens for this user
    $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL");
    $stmt->execute([$userId]);

    // Generate secure token (selector:validator)
    $selector = bin2hex(random_bytes(8)); // 16 chars
    $validator = bin2hex(random_bytes(16)); // 32 chars
    $tokenHash = password_hash($validator, PASSWORD_DEFAULT);

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // Use MySQL NOW() to ensure consistency between creation and verification timezones
    $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, selector, token_hash, expires_at, ip_address, user_agent) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?, ?)");
    $stmt->execute([$userId, $selector, $tokenHash, $ip, $userAgent]);

    return $selector . ':' . $validator;
}

function verify_reset_token(string $token): ?array
{
    $parts = explode(':', $token);
    if (count($parts) !== 2) {
        return null;
    }
    $selector = $parts[0];
    $validator = $parts[1];

    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE selector = ? AND used_at IS NULL AND expires_at > NOW()");
    $stmt->execute([$selector]);
    $row = $stmt->fetch();

    if ($row && password_verify($validator, $row['token_hash'])) {
        return $row;
    }

    return null;
}

function consume_reset_token(int $tokenId): void
{
    $stmt = get_pdo()->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?");
    $stmt->execute([$tokenId]);
}

function reset_user_password(int $userId, string $newPassword): void
{
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = get_pdo()->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$hash, $userId]);
}
