<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No autenticado"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

$username = $_SESSION['username'];

try {
    // Validar CSRF
    if (!validate_csrf()) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "CSRF inválido"]);
        exit;
    }
    
    // Leer JSON
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception("JSON inválido");
    }
    
    $idPlantilla = isset($data['plantillaId']) ? intval($data['plantillaId']) : 0;
    $email = isset($data['email']) ? trim($data['email']) : '';
    
    if (!$idPlantilla || !$email) {
        throw new Exception("Datos incompletos");
    }
    
    // Verificar que el usuario es propietario O tiene rol admin en la plantilla
    $stmt = $conn->prepare("SELECT username FROM plantillas WHERE id = ? AND deleted_at IS NULL");
    if (!$stmt) {
        throw new Exception("Error prepare plantillas: " . $conn->error);
    }
    
    $stmt->bind_param("i", $idPlantilla);
    if (!$stmt->execute()) {
        throw new Exception("Error execute plantillas: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $plantilla_row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$plantilla_row) {
        throw new Exception("Plantilla no encontrada");
    }
    
    // Obtener email del usuario actual
    $stmt = $conn->prepare("SELECT email FROM users WHERE username = ?");
    if (!$stmt) {
        throw new Exception("Error prepare users: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $userRow = $userResult->fetch_assoc();
    $stmt->close();
    
    if (!$userRow) {
        throw new Exception("Usuario no encontrado");
    }
    
    $userEmail = $userRow['email'];
    
    // El propietario SIEMPRE puede eliminar comparticiones
    $isOwner = ($plantilla_row['username'] === $username);
    
    // Si no es propietario, verificar si tiene rol admin en esta plantilla
    if (!$isOwner) {
        $stmt = $conn->prepare("SELECT rol FROM plantillas_compartidas WHERE id_plantilla = ? AND email = ?");
        if (!$stmt) {
            throw new Exception("Error prepare check role: " . $conn->error);
        }
        $stmt->bind_param("is", $idPlantilla, $userEmail);
        $stmt->execute();
        $roleResult = $stmt->get_result();
        $roleRow = $roleResult->fetch_assoc();
        $stmt->close();
        
        $userRole = $roleRow ? ($roleRow['rol'] ?? 'lector') : 'lector';
        $hasPermission = ($userRole === 'admin');
    } else {
        $hasPermission = true;
    }
    
    if (!$hasPermission) {
        throw new Exception("No tienes permiso para eliminar comparticiones en esta plantilla");
    }
    
    // Eliminar el acceso compartido
    $stmt = $conn->prepare("DELETE FROM plantillas_compartidas WHERE id_plantilla = ? AND email = ?");
    if (!$stmt) {
        throw new Exception("Error prepare delete: " . $conn->error);
    }
    
    $stmt->bind_param("is", $idPlantilla, $email);
    if (!$stmt->execute()) {
        throw new Exception("Error delete: " . $stmt->error);
    }
    
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    if ($affected > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Acceso eliminado correctamente"
        ]);
    } else {
        throw new Exception("Usuario no encontrado en compartidos");
    }
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
