<?php
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // In development, show the real error message to help debugging. In production keep the generic message.
    http_response_code(500);
    if (defined('APP_ENV') && APP_ENV !== 'production') {
        // show a friendly but detailed message for local debugging
        $msg = 'Error de conexión a la base de datos: ' . $e->getMessage();
        die(htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }
    die('Error de conexión a la base de datos.');
}
?>
