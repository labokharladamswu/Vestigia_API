<?php
// Reads configuration from real environment variables:
//   - Docker:  injected by docker-compose (env_file: .env)
//   - Vercel:  Project Settings -> Environment Variables
// For plain `php -S` use, a .env file in the project root is read as a fallback.

function env(string $key, $default = null) {
    $v = getenv($key);
    if ($v === false && isset($_ENV[$key])) {
        $v = $_ENV[$key];
    }
    return ($v === false || $v === null || $v === '') ? $default : $v;
}

(function () {
    $file = dirname(__DIR__) . '/.env';
    if (!is_readable($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\"'");
        if ($k !== '' && getenv($k) === false) {
            putenv("$k=$v");
        }
    }
})();
