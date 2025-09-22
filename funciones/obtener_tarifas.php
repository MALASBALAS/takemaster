<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';

$provincia = $_GET['provincia'] ?? '';

if ($provincia) {
    $stmt = $conn->prepare("SELECT cg_actor, take FROM provincias_cine WHERE provincia_id = (SELECT id FROM provincias WHERE nombre = ?)");
header('Content-Type: application/json');
    $stmt->bind_param("s", $provincia);
    $stmt->execute();
    $result = $stmt->get_result();
    $tarifas = $result->fetch_assoc();
    $stmt->close();

    echo json_encode($tarifas);
} else {
    echo json_encode($tarifas ?: []);
}
