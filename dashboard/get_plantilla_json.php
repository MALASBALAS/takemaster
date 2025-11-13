<?php
require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
require_once __DIR__ . '/../funciones/encryption.php';
start_secure_session();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$username = $_SESSION['username'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$stmt = $conn->prepare('SELECT contenido FROM plantillas WHERE id = ? AND username = ? AND deleted_at IS NULL');
$stmt->bind_param('is', $id, $username);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Plantilla no encontrada']);
    exit;
}

$contenido = [];
if (!empty($row['contenido'])) {
    try {
        // 🔐 DESENCRIPTAR contenido
        $contenido_desencriptado = decrypt_content($row['contenido']);
        $decoded = json_decode($contenido_desencriptado, true);
        if (is_array($decoded)) $contenido = $decoded;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al desencriptar: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => true, 'contenido' => $contenido]);
exit;
