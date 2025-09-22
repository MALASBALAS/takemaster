<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

header('Content-Type: application/json');

$username = $_SESSION['username'] ?? '';

if (!$username) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

$sql = "SELECT card_number, card_name, expiry_month, expiry_year, ccv FROM payment_methods WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($card_number, $card_name, $expiry_month, $expiry_year, $ccv);
    $stmt->fetch();
    echo json_encode([
        'success' => true,
        'card_number' => $card_number,
        'card_name' => $card_name,
        'expiry_month' => $expiry_month,
        'expiry_year' => $expiry_year,
        'ccv' => $ccv
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontraron datos de la tarjeta']);
}

$stmt->close();
$conn->close();
?>
