<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

// Establecer header antes de cualquier output
header('Content-Type: application/json; charset=utf-8');

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Verificar CSRF
if (!validate_csrf()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'CSRF inválido']);
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$username = $_SESSION['username'];
$nombrePlantilla = trim($_POST['nombre_plantilla'] ?? '');

// Validar que el nombre no esté vacío
if (empty($nombrePlantilla)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El nombre de la plantilla es obligatorio']);
    exit;
}

// Insertar plantilla
$stmt = $conn->prepare("INSERT INTO plantillas (username, nombre) VALUES (?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta']);
    exit;
}

$stmt->bind_param("ss", $username, $nombrePlantilla);

if ($stmt->execute()) {
    $plantillaId = $stmt->insert_id;
    $stmt->close();
    
    $redirectUrl = BASE_URL . '/plantillas/miplantilla.php?id=' . urlencode((string)$plantillaId);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Plantilla creada correctamente',
        'redirect' => $redirectUrl,
        'plantilla_id' => $plantillaId
    ]);
    exit;
} else {
    $error = $conn->error;
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al crear la plantilla: ' . $error]);
    exit;
}
?>
