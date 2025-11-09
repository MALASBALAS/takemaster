<?php
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // En desarrollo, mostrar el mensaje de error real para facilitar la depuración. En producción mantener un mensaje genérico.
        http_response_code(500);
        if (defined('APP_ENV') && APP_ENV !== 'production') {
            // Mostrar un mensaje detallado pero amigable para debug local
            $msg = 'Error de conexión a la base de datos: ' . $e->getMessage();
            die(htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }
        die('Error de conexión a la base de datos.');
}
?>
