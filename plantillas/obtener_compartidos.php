<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No autenticado"]);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID de plantilla requerido"]);
    exit;
}

try {
    // Obtener usuarios compartidos con roles (si existen) o solo emails
    // Usar COALESCE para asegurar que siempre tenemos rol
    $stmt = $conn->prepare("SELECT email, COALESCE(rol, 'lector') as rol FROM plantillas_compartidas WHERE id_plantilla = ? ORDER BY email");
    
    if (!$stmt) {
        // Si falla (columna rol no existe o query error), intentar sin COALESCE
        error_log("[obtener_compartidos.php] Query con COALESCE falló, usando fallback");
        $stmt = $conn->prepare("SELECT email FROM plantillas_compartidas WHERE id_plantilla = ? ORDER BY email");
        
        if (!$stmt) {
            throw new Exception("Error prepare: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Error execute: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $usuariosCompartidos = [];
        while ($row = $result->fetch_assoc()) {
            // Sin rol, asumir 'lector' por defecto
            $usuariosCompartidos[] = ['email' => $row['email'], 'rol' => 'lector'];
        }
        $stmt->close();
    } else {
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Error execute: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $usuariosCompartidos = [];
        while ($row = $result->fetch_assoc()) {
            // Asegurar que rol siempre tiene un valor válido
            $rol = $row['rol'];
            if (empty($rol) || !in_array($rol, ['lector', 'editor', 'admin'], true)) {
                $rol = 'lector';
            }
            $usuariosCompartidos[] = ['email' => $row['email'], 'rol' => $rol];
        }
        $stmt->close();
    }
    
    error_log("[obtener_compartidos.php] Usuarios encontrados: " . count($usuariosCompartidos));
    
    echo json_encode([
        "status" => "success",
        "usuariosCompartidos" => $usuariosCompartidos
    ]);
    
} catch (Throwable $e) {
    error_log("[obtener_compartidos.php] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
