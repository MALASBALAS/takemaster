<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

// Activar error reporting y logging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[PHP ERROR] [$errno] $errstr in $errfile:$errline");
    return false;
});

if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        header('Content-Type: application/json');
        
        error_log("[compartir_plantilla.php] ===== START POST REQUEST =====");
        
        // Verificar conexión a BD
        if (!$conn) {
            throw new Exception("Base de datos no disponible");
        }
        
        error_log("[compartir_plantilla.php] Conexión BD OK");
        error_log("[compartir_plantilla.php] Usuario: {$username}");
        error_log("[compartir_plantilla.php] CSRF Token en header: " . ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? 'NO PRESENTE'));
        
        // Validar CSRF
        if (!validate_csrf()) {
            error_log("[compartir_plantilla.php] CSRF validation failed");
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "CSRF inválido"]);
            exit;
        }
        
        error_log("[compartir_plantilla.php] CSRF validation OK");
        
        // Leer JSON
        $input = file_get_contents("php://input");
        error_log("[compartir_plantilla.php] Raw input: " . substr($input, 0, 300));
        
        $data = json_decode($input, true);
        if (!$data) {
            throw new Exception("JSON inválido: " . json_last_error_msg());
        }
        
        $idPlantilla = isset($data['plantillaId']) ? intval($data['plantillaId']) : null;
        $emailsWithRoles = isset($data['emailsWithRoles']) ? $data['emailsWithRoles'] : [];
        
        // Soporte backward compatibility con 'emails' (solo emails, sin roles)
        if (empty($emailsWithRoles) && isset($data['emails'])) {
            $emails = $data['emails'];
            $emailsWithRoles = array_map(function($email) {
                return ['email' => $email, 'rol' => 'lector'];
            }, $emails);
        }
        
        error_log("[compartir_plantilla.php] ID: {$idPlantilla}, emailsWithRoles: " . json_encode($emailsWithRoles));
        
        if (!$idPlantilla || !is_array($emailsWithRoles) || empty($emailsWithRoles)) {
            throw new Exception("Datos incompletos");
        }
        
        error_log("[compartir_plantilla.php] Datos validados OK");

        // 🔐 VERIFICACIÓN DE PERMISOS
        error_log("[compartir_plantilla.php] Verificando permisos para plantilla {$idPlantilla}");
        
        // Obtener email del usuario actual
        $stmt = $conn->prepare("SELECT username, email FROM users WHERE username = ?");
        if (!$stmt) {
            throw new Exception("Error prepare users: " . $conn->error);
        }
        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            throw new Exception("Error execute users: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $user_row = $result->fetch_assoc();
        $stmt->close();
        
        if (!$user_row) {
            throw new Exception("Usuario no encontrado");
        }
        
        $userEmail = $user_row['email'];
        error_log("[compartir_plantilla.php] Email del usuario actual: {$userEmail}");
        
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
        
        // El propietario SIEMPRE puede compartir
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
            throw new Exception("No tienes permiso para compartir esta plantilla (necesitas ser propietario o admin)");
        }
        
        error_log("[compartir_plantilla.php] Permisos OK");
        
        // Procesar cada email
        $compartidos = 0;
        foreach ($emailsWithRoles as $item) {
            if (!is_array($item) && !is_object($item)) continue;
            
            $email = isset($item['email']) ? trim($item['email']) : trim($item);
            $rol = isset($item['rol']) ? trim($item['rol']) : 'lector';
            
            // Validar rol - asegurar que es uno de los valores permitidos
            if (!in_array($rol, ['lector', 'editor', 'admin'], true)) {
                error_log("[compartir_plantilla.php] Rol inválido: {$rol}, asignando lector por defecto");
                $rol = 'lector';
            }
            
            if (empty($email)) continue;
            
            // Validar que no se comparta consigo mismo
            if (strtolower($email) === strtolower($userEmail)) {
                error_log("[compartir_plantilla.php] Intentando compartir consigo mismo ({$email}). Ignorando.");
                continue;
            }
            
            error_log("[compartir_plantilla.php] Procesando email: {$email}, rol: {$rol}");
            
            // Verificar duplicado - usando columnas correctas: id_plantilla, email
            $stmt = $conn->prepare("SELECT id, rol FROM plantillas_compartidas WHERE id_plantilla = ? AND email = ?");
            if (!$stmt) {
                error_log("[compartir_plantilla.php] Error prepare check duplicado: " . $conn->error);
                continue;
            }
            
            $stmt->bind_param("is", $idPlantilla, $email);
            if (!$stmt->execute()) {
                error_log("[compartir_plantilla.php] Error execute check duplicado: " . $stmt->error);
                $stmt->close();
                continue;
            }
            
            $result = $stmt->get_result();
            $exists = $result->fetch_assoc();
            $stmt->close();
            
            if ($exists) {
                $existingRol = $exists['rol'] ?? 'lector';
                
                // Si el rol es diferente, actualizar
                if ($existingRol !== $rol) {
                    error_log("[compartir_plantilla.php] Ya compartida con {$email} con rol {$existingRol}. Actualizando a {$rol}");
                    
                    $updateStmt = $conn->prepare("UPDATE plantillas_compartidas SET rol = ? WHERE id_plantilla = ? AND email = ?");
                    if ($updateStmt) {
                        $updateStmt->bind_param("sis", $rol, $idPlantilla, $email);
                        if ($updateStmt->execute()) {
                            error_log("[compartir_plantilla.php] ✓ Rol actualizado de {$existingRol} a {$rol}");
                            $compartidos++;
                        } else {
                            error_log("[compartir_plantilla.php] Error al actualizar rol: " . $updateStmt->error);
                        }
                        $updateStmt->close();
                    } else {
                        error_log("[compartir_plantilla.php] Error prepare update: " . $conn->error);
                    }
                } else {
                    error_log("[compartir_plantilla.php] Ya compartida con {$email} con el mismo rol {$rol}. Sin cambios.");
                }
                continue;
            }
            
            // Nueva compartición - INSERTAR CON ROL
            $stmt = $conn->prepare("INSERT INTO plantillas_compartidas (id_plantilla, email, rol) VALUES (?, ?, ?)");
            
            if (!$stmt) {
                error_log("[compartir_plantilla.php] Error prepare insert con rol: " . $conn->error);
                continue;
            }
            
            $stmt->bind_param("iss", $idPlantilla, $email, $rol);
            if (!$stmt->execute()) {
                error_log("[compartir_plantilla.php] Error insert con rol: " . $stmt->error);
                $stmt->close();
                continue;
            }
            $stmt->close();
            $compartidos++;
            error_log("[compartir_plantilla.php] ✓ Compartida con {$email} (rol: {$rol})");
        }
        
        // Obtener lista actualizada de usuarios compartidos
        // Consultar con COALESCE para asegurar que siempre tenemos rol
        $stmt = $conn->prepare("SELECT email, COALESCE(rol, 'lector') as rol FROM plantillas_compartidas WHERE id_plantilla = ? ORDER BY email");
        
        if (!$stmt) {
            error_log("[compartir_plantilla.php] Error prepare final list: " . $conn->error);
            echo json_encode([
                "status" => "success", 
                "compartidas" => $compartidos,
                "usuariosCompartidos" => []
            ]);
        } else {
            $stmt->bind_param("i", $idPlantilla);
            if (!$stmt->execute()) {
                error_log("[compartir_plantilla.php] Error execute final list: " . $stmt->error);
                $usuariosCompartidos = [];
            } else {
                $result = $stmt->get_result();
                $usuariosCompartidos = [];
                while ($row = $result->fetch_assoc()) {
                    $usuariosCompartidos[] = ['email' => $row['email'], 'rol' => $row['rol']];
                }
            }
            $stmt->close();
            
            error_log("[compartir_plantilla.php] Proceso completado. Compartidas: {$compartidos}. Total usuarios: " . count($usuariosCompartidos));
            echo json_encode([
                "status" => "success", 
                "compartidas" => $compartidos,
                "usuariosCompartidos" => $usuariosCompartidos
            ]);
        }
        exit;
    } catch (Throwable $e) {
        error_log("[compartir_plantilla.php] EXCEPTION: " . $e->getMessage());
        error_log("[compartir_plantilla.php] File: " . $e->getFile() . " Line: " . $e->getLine());
        error_log("[compartir_plantilla.php] Trace: " . $e->getTraceAsString());
        
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage(),
            "type" => get_class($e)
        ]);
        exit;
    }
}
