<?php
declare(strict_types=1);

// Load .env file in development for local testing (non-production only)
$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    // Parse simple KEY=VALUE lines, ignore comments and blank lines
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $k = trim($parts[0]);
        $v = trim($parts[1]);
        // remove optional surrounding quotes
        if ((strpos($v, '"') === 0 && strrpos($v, '"') === strlen($v)-1) || (strpos($v, "'") === 0 && strrpos($v, "'") === strlen($v)-1)) {
            $v = substr($v, 1, -1);
        }
        if ($k !== '') {
            // only set env if not already present to allow real env override
            if (getenv($k) === false) {
                putenv("$k=$v");
            }
            if (!isset($_ENV[$k])) $_ENV[$k] = $v;
            if (!isset($_SERVER[$k])) $_SERVER[$k] = $v;
        }
    }
}

// Simple env helper
function env(string $key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// Application environment
define('APP_ENV', env('APP_ENV', 'production'));

// App URL and database config (override via environment variables on server)
define('BASE_URL', env('BASE_URL', 'http://localhost'));
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'changeme'));
define('DB_USER', env('DB_USER', 'changeme'));
define('DB_PASS', env('DB_PASS', 'changeme'));

// Auth & security configuration
define('SESSION_NAME', env('SESSION_NAME', 'takemaster_sid'));
define('CSRF_TOKEN_KEY', 'csrf_token');
define('LOGIN_RATE_LIMIT_MAX', (int) env('LOGIN_RATE_LIMIT_MAX', '10')); // max attempts
define('LOGIN_RATE_LIMIT_WINDOW_MIN', (int) env('LOGIN_RATE_LIMIT_WINDOW_MIN', '15')); // minutes
?>
