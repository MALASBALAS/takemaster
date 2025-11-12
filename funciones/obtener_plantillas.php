<?php
/**
 * obtener_plantillas.php
 * Devuelve la lista de plantillas del usuario actual en formato JSON.
 * 
 * Uso: fetch('/funciones/obtener_plantillas.php')
 *      .then(r => r.json())
 *      .then(data => console.log(data.plantillas))
 */

require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado', 'plantillas' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$username = $_SESSION['username'];
$plantillas = [];

try {
    if (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT id, nombre FROM plantillas WHERE username = ? ORDER BY id DESC");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $plantillas[] = [
                        'id' => (int)$row['id'],
                        'nombre' => (string)$row['nombre']
                    ];
                }
            }
            $stmt->close();
        }
    }
} catch (Throwable $e) {
    error_log('[obtener_plantillas.php] Error: ' . $e->getMessage());
}

echo json_encode([
    'success' => true,
    'plantillas' => $plantillas
], JSON_UNESCAPED_UNICODE);
