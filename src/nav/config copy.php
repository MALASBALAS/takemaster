<?php
declare(strict_types=1);

// Simple env helper
function env(string $key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// Application environment
define('APP_ENV', env('APP_ENV', 'production'));

// App URL and database config (override via environment variables on server)
define('BASE_URL', env('BASE_URL', 'https://testtakemaster.balbe.xyz'));
define('DB_HOST', env('DB_HOST', 'x'));
define('DB_NAME', env('DB_NAME', 'x'));
define('DB_USER', env('DB_USER', 'x'));
define('DB_PASS', env('DB_PASS', 'x'));

// Auth & security configuration
define('SESSION_NAME', env('SESSION_NAME', 'takemaster_sid'));
define('CSRF_TOKEN_KEY', 'csrf_token');
define('LOGIN_RATE_LIMIT_MAX', (int) env('LOGIN_RATE_LIMIT_MAX', '10')); // max attempts
define('LOGIN_RATE_LIMIT_WINDOW_MIN', (int) env('LOGIN_RATE_LIMIT_WINDOW_MIN', '15')); // minutes
?>
