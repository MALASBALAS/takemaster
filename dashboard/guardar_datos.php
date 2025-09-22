<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
// Optional CSRF protection for JSON requests using header X-CSRF-Token
if (!validate_csrf()) {
    echo json_encode(['success' => false, 'message' => 'CSRF inválido']);
    exit();
}
$username = $_SESSION['username'] ?? '';

if (!$username) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

$card_number = $data['inputNumero'] ?? '';
$card_name = $data['inputNombre'] ?? '';
$expiry_month = $data['selectMes'] ?? '';
$expiry_year = $data['selectYear'] ?? '';
$ccv = $data['inputCCV'] ?? '';

if (!$card_number || !$card_name || !$expiry_month || !$expiry_year || !$ccv) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit();
}

$sql = "REPLACE INTO payment_methods (username, card_number, card_name, expiry_month, expiry_year, ccv) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssssss', $username, $card_number, $card_name, $expiry_month, $expiry_year, $ccv);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar los datos']);
}

$stmt->close();
$conn->close();
?>
