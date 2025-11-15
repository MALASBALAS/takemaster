<?php
/**
 * crear_plantilla_handler.php
 * Handler separado para AJAX POST de creación de plantillas
 * Devuelve JSON puro, sin HTML
 */

require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validar CSRF
if (!validate_csrf()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtener datos
$username = $_SESSION['username'];
$nombrePlantilla = $_POST['nombre_plantilla'] ?? '';

if (empty($nombrePlantilla)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El nombre de la plantilla es requerido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Crear plantilla
    $stmt = $conn->prepare("INSERT INTO plantillas (username, nombre) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception('Error preparando statement: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $username, $nombrePlantilla);
    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando insert: ' . $stmt->error);
    }
    
    $plantillaId = $stmt->insert_id;
    $stmt->close();
    
    // Retornar éxito con redirect URL
    $redirectUrl = BASE_URL . '/plantillas/miplantilla.php?id=' . urlencode((string)$plantillaId);
    echo json_encode([
        'success' => true,
        'plantillaId' => $plantillaId,
        'nombre' => $nombrePlantilla,
        'redirect' => $redirectUrl
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('[crear_plantilla_handler.php] Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al crear la plantilla: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
