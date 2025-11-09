<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);

// Leer acción
$action = $_GET['action'] ?? '';

if ($action === 'save' || $action === 'update') {
    if (!validate_csrf()) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'CSRF inválido']);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $card_number = substr(preg_replace('/\D+/', '', $data['card_number'] ?? ''), 0, 19);
    $card_name = trim((string)($data['card_name'] ?? ''));
    $expiry_month = substr(preg_replace('/\D+/', '', $data['expiry_month'] ?? ''), 0, 2);
    $expiry_year = substr(preg_replace('/\D+/', '', $data['expiry_year'] ?? ''), 0, 4);
    $ccv = substr(preg_replace('/\D+/', '', $data['ccv'] ?? ''), 0, 4);

    if ($action === 'save') {
        $sql = "INSERT INTO tarjetas (user_id, card_number, card_name, expiry_month, expiry_year, ccv) VALUES (?, ?, ?, ?, ?, ?)";
    } else {
        $sql = "UPDATE tarjetas SET card_number = ?, card_name = ?, expiry_month = ?, expiry_year = ?, ccv = ? WHERE user_id = ?";
    }

    $stmt = $conn->prepare($sql);
    if ($action === 'save') {
        $stmt->bind_param("isssss", $user_id, $card_number, $card_name, $expiry_month, $expiry_year, $ccv);
    } else {
        $stmt->bind_param("sssssi", $card_number, $card_name, $expiry_month, $expiry_year, $ccv, $user_id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar']);
    }

    $stmt->close();
}

if ($action === 'delete') {
    if (!validate_csrf()) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'CSRF inválido']);
        exit;
    }
    $sql = "DELETE FROM tarjetas WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar']);
    }

    $stmt->close();
}

$conn->close();
?>
