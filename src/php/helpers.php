<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/auth.php';

if (!isset($config)) {
    $config = require __DIR__ . '/config.php';
}

function render(string $template, array $data = [], int $status = 200): void
{
    http_response_code($status);
    $currentUser = current_user();
    $lang = current_lang();
    global $config;
    $languages = $config['languages'];
    $basePath = $config['base_path'];
    extract($data);
    include __DIR__ . '/../../views/' . $template . '.php';
}

function redirect(string $location): void
{
    global $config;
    $base = $config['base_path'] ?? '';
    if (strpos($location, 'http') !== 0) {
        $location = $base . $location;
    }
    header('Location: ' . $location);
    exit;
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function get_json_input(): array
{
    $content = file_get_contents('php://input');
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function path_matches(string $path, string $pattern, array &$params = []): bool
{
    $pattern = str_replace('/', '\/', $pattern);
    $pattern = preg_replace('/:(\w+)/', '(?P<$1>[^\/]+)', $pattern);
    if (preg_match('/^' . $pattern . '$/', $path, $matches)) {
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $params[$key] = $value;
            }
        }
        return true;
    }
    return false;
}

function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool
{
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
