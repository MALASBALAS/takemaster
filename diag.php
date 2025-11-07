<?php
require __DIR__ . '/src/nav/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "APP_ENV: " . (defined('APP_ENV') ? APP_ENV : 'n/a') . PHP_EOL;
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'n/a') . PHP_EOL;
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'n/a') . PHP_EOL;
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'n/a') . PHP_EOL;
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'n/a') . PHP_EOL;

// Try DB connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $m = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    echo "MySQL connection: OK (server_info=" . $m->server_info . ")" . PHP_EOL;
    $m->close();
} catch (Exception $e) {
    echo "MySQL connection: FAILED" . PHP_EOL;
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

// Quick file checks for common assets
$files = [
    '/src/css/styles.css',
    '/src/nav/topnav.php',
    '/auth/login.php'
];
foreach ($files as $f) {
    $path = __DIR__ . $f;
    echo "File " . $f . ": " . (file_exists($path) ? 'FOUND' : 'MISSING') . PHP_EOL;
}

?>