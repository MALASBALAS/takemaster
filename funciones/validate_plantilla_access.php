<?php
/**
 * VALIDACIÓN ROBUSTA DE ACCESO A PLANTILLAS
 * 
 * Este archivo proporciona funciones para verificar permiso de acceso a plantillas
 * de manera segura, rechazando explícitamente a usuarios con rol 'lector' que
 * intenten manipular el HTML/DevTools para editar plantillas compartidas.
 * 
 * @author Takemaster Security
 * @version 1.0
 */

/**
 * Valida el acceso de un usuario a una plantilla específica
 * 
 * @param mysqli $conn Conexión a base de datos
 * @param int $plantillaId ID de la plantilla
 * @param string $username Nombre del usuario (de sesión)
 * @param string|null $userEmail Email del usuario
 * @param string $requiredRole Rol mínimo requerido: 'propietario', 'editor', 'admin', 'lector', 'any'
 * @return array ['can_access' => bool, 'role' => string|null, 'error' => string|null]
 */
function validate_plantilla_access($conn, $plantillaId, $username, $userEmail = null, $requiredRole = 'any') {
    $plantillaId = (int)$plantillaId;
    
    if ($plantillaId <= 0) {
        error_log("[ACCESS] Intento de acceder a plantilla inválida ID={$plantillaId}");
        return [
            'can_access' => false,
            'role' => null,
            'error' => 'ID de plantilla inválido'
        ];
    }
    
    // 1. Verificar si la plantilla existe y obtener propietario
    $stmt = $conn->prepare("SELECT username FROM plantillas WHERE id = ? AND deleted_at IS NULL");
    if (!$stmt) {
        return [
            'can_access' => false,
            'role' => null,
            'error' => 'Error al verificar plantilla'
        ];
    }
    
    $stmt->bind_param('i', $plantillaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $plantilla = $result->fetch_assoc();
    $stmt->close();
    
    if (!$plantilla) {
        error_log("[ACCESS] Plantilla no encontrada ID={$plantillaId}");
        return [
            'can_access' => false,
            'role' => null,
            'error' => 'Plantilla no encontrada'
        ];
    }
    
    $owner = $plantilla['username'];
    $isOwner = ($owner === $username);
    $userRole = $isOwner ? 'propietario' : null;
    
    // 2. Si no es propietario, buscar rol en plantillas_compartidas
    if (!$isOwner && $userEmail) {
        $stmt = $conn->prepare(
            "SELECT rol FROM plantillas_compartidas 
             WHERE id_plantilla = ? AND email = ? LIMIT 1"
        );
        if ($stmt) {
            $stmt->bind_param('is', $plantillaId, $userEmail);
            $stmt->execute();
            $share_result = $stmt->get_result();
            if ($share_result->num_rows > 0) {
                $share_row = $share_result->fetch_assoc();
                $userRole = $share_row['rol'];
            }
            $stmt->close();
        }
    }
    
    // 3. Si no tiene rol, acceso denegado
    if (!$userRole) {
        error_log("[SECURITY] Intento de acceso no autorizado a plantilla #{$plantillaId} por '{$username}' ({$userEmail})");
        return [
            'can_access' => false,
            'role' => null,
            'error' => 'No tienes acceso a esta plantilla'
        ];
    }
    
    // 4. Validar rol requerido
    if ($requiredRole !== 'any') {
        $roleHierarchy = ['lector' => 1, 'editor' => 2, 'admin' => 3, 'propietario' => 4];
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 1;
        $userLevel = $roleHierarchy[$userRole] ?? 0;
        
        if ($userLevel < $requiredLevel) {
            // Especial: Si es 'lector' intentando editar, loguear como intento malicioso
            if ($userRole === 'lector' && in_array($requiredRole, ['editor', 'admin', 'propietario'])) {
                error_log("[SECURITY] INTENTO DE EDICIÓN POR LECTOR: Plantilla #{$plantillaId}, Usuario '{$username}' ({$userEmail}), IP: " . get_client_ip());
            }
            return [
                'can_access' => false,
                'role' => $userRole,
                'error' => "Tu rol ({$userRole}) no permite esta acción"
            ];
        }
    }
    
    return [
        'can_access' => true,
        'role' => $userRole,
        'error' => null
    ];
}

/**
 * Valida que un usuario PUEDE EDITAR una plantilla (rechaza explícitamente lectores)
 * 
 * @param mysqli $conn Conexión a base de datos
 * @param int $plantillaId ID de la plantilla
 * @param string $username Nombre del usuario
 * @param string|null $userEmail Email del usuario
 * @return array ['can_edit' => bool, 'reason' => string]
 */
function can_user_edit_plantilla($conn, $plantillaId, $username, $userEmail = null) {
    $validation = validate_plantilla_access($conn, $plantillaId, $username, $userEmail, 'editor');
    
    return [
        'can_edit' => $validation['can_access'],
        'reason' => $validation['error']
    ];
}

/**
 * Rechaza explícitamente si el usuario es LECTOR
 * Responde con JSON error 403 y termina la ejecución
 * 
 * @param mysqli $conn Conexión a base de datos
 * @param int $plantillaId ID de la plantilla
 * @param string $username Nombre del usuario
 * @param string|null $userEmail Email del usuario
 * @return void (termina ejecución si es rechazado)
 */
function require_plantilla_edit_access($conn, $plantillaId, $username, $userEmail = null) {
    $validation = validate_plantilla_access($conn, $plantillaId, $username, $userEmail, 'editor');
    
    if (!$validation['can_access']) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        
        // Mensaje especial para lectores que intenten manipular
        $message = $validation['error'];
        if ($validation['role'] === 'lector') {
            $message = 'Acceso denegado: Tu rol es "Lector" - no puedes editar ni guardar cambios en esta plantilla.';
        }
        
        die(json_encode([
            'success' => false,
            'error' => $message,
            'code' => 'PERMISSION_DENIED'
        ]));
    }
}
?>
