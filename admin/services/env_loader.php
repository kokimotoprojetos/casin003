<?php
// Environment loader
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!getenv($key)) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

// Load from project root .env
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    loadEnv($envFile);
}
?>
