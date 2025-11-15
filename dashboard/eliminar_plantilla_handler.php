<?php
/**
 * eliminar_plantilla_handler.php
 * Handler separado para eliminación de plantillas vía AJAX
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
$idPlantilla = (int)($_POST['plantilla_id'] ?? 0);

if (empty($idPlantilla)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de plantilla requerido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verificar que la plantilla pertenece al usuario
    $stmt = $conn->prepare("SELECT id FROM plantillas WHERE id = ? AND username = ?");
    if (!$stmt) {
        throw new Exception('Error preparando statement: ' . $conn->error);
    }
    
    $stmt->bind_param("is", $idPlantilla, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Plantilla no encontrada o no tienes permiso'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $stmt->close();
    
    // Eliminar la plantilla
    $stmt = $conn->prepare("DELETE FROM plantillas WHERE id = ? AND username = ?");
    if (!$stmt) {
        throw new Exception('Error preparando statement: ' . $conn->error);
    }
    
    $stmt->bind_param("is", $idPlantilla, $username);
    if (!$stmt->execute()) {
        throw new Exception('Error eliminando plantilla: ' . $stmt->error);
    }
    
    $stmt->close();
    error_log('[eliminar_plantilla_handler.php] Plantilla ' . $idPlantilla . ' eliminada por usuario ' . $username);
    
    // Retornar éxito
    echo json_encode([
        'success' => true,
        'message' => 'Plantilla eliminada correctamente',
        'plantillaId' => $idPlantilla
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('[eliminar_plantilla_handler.php] Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al eliminar la plantilla: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
