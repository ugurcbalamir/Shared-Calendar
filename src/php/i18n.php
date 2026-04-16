<?php
require_once __DIR__ . '/config.php';

$translations = [];
$lang = null;

function load_translations(): array
{
    static $loaded = null;
    global $config;
    if ($loaded !== null) {
        return $loaded;
    }
    $dir = __DIR__ . '/../locales';
    $loaded = [];
    foreach ($config['languages'] as $language) {
        $file = $dir . '/' . $language . '.json';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $loaded[$language] = json_decode($content, true) ?: [];
        }
    }
    return $loaded;
}

function current_lang(): string
{
    global $config, $lang;
    if ($lang !== null) {
        return $lang;
    }
    $available = $config['languages'];
    $selected = $_GET['lang'] ?? ($_SESSION['lang'] ?? $config['default_language']);
    $lang = in_array($selected, $available, true) ? $selected : $config['default_language'];
    $_SESSION['lang'] = $lang;
    return $lang;
}

function t(string $key): string|array
{
    static $translations = null;
    if ($translations === null) {
        $translations = load_translations();
    }
    $lang = current_lang();

    // Defensive check
    if (!isset($translations[$lang])) {
        return $key;
    }

    // Allow nested keys (e.g. auth.forgot_password_link)
    if (strpos($key, '.') !== false) {
        $parts = explode('.', $key);
        $value = $translations[$lang];
        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $key;
            }
        }
        return $value;
    }

    return $translations[$lang][$key] ?? $key;
}
