<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (!validate_csrf()) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "CSRF inválido"]);
        exit;
    }
    $data = json_decode(file_get_contents("php://input"), true);
    $idPlantilla = $data['plantillaId'];
    $emails = $data['emails'];

    foreach ($emails as $email) {
        $stmt = $conn->prepare("INSERT INTO plantillas_compartidas (id_plantilla, email) VALUES (?, ?)");
        $stmt->bind_param("is", $idPlantilla, $email);
        $stmt->execute();
        $stmt->close();
    }

    // Puedes aquí añadir lógica para enviar correos electrónicos si es necesario
    echo json_encode(["status" => "success"]);
    exit;
}
