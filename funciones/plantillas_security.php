<?php
/**
 * plantillas_security.php
 * 
 * Funciones para gestión segura de plantillas con auditoría y control de versiones.
 * Incluye operaciones CRUD con validación de permisos, logging automático y recuperación de versiones.
 * 
 * Uso:
 *   require_once __DIR__ . '/../funciones/plantillas_security.php';
 */

/**
 * Crear plantilla nueva con auditoría
 * 
 * @param mysqli $conn Conexión a BD
 * @param string $username Usuario propietario
 * @param string $nombre Nombre de la plantilla
 * @param array $contenido_inicial Array que se convertirá a JSON (opcional)
 * @param string $client_ip IP del cliente (para auditoría)
 * 
 * @return array ['success' => bool, 'plantilla_id' => int, 'error' => string]
 */
function crear_plantilla_segura($conn, $username, $nombre, $contenido_inicial = [], $client_ip = null)
{
    try {
        if (!$client_ip) {
            $client_ip = get_client_ip();
        }

        $contenido_json = json_encode($contenido_inicial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Comenzar transacción
        $conn->begin_transaction();

        // Establecer IP para triggers
        $conn->query("SET @client_ip = '" . $conn->real_escape_string($client_ip) . "'");

        // Insertar plantilla
        $stmt = $conn->prepare("
            INSERT INTO plantillas (username, nombre, contenido, version_actual)
            VALUES (?, ?, ?, 1)
        ");
        $stmt->bind_param("sss", $username, $nombre, $contenido_json);
        $stmt->execute();
        $plantilla_id = $stmt->insert_id;
        $stmt->close();

        // Crear versión 1
        $hash = hash('sha256', $contenido_json);
        $stmt = $conn->prepare("
            INSERT INTO plantillas_versiones (
                plantilla_id, version_numero, contenido, tamaño_bytes, 
                hash_contenido, guardado_por, guardado_desde
            ) VALUES (?, 1, ?, ?, UNHEX(?), ?, ?)
        ");
        $stmt->bind_param("isisss", $plantilla_id, $contenido_json, strlen($contenido_json), $hash, $username, $client_ip);
        $stmt->execute();
        $stmt->close();

        // Log en auditoría
        $detalles = json_encode([
            'nombre' => $nombre,
            'tamaño_inicial' => strlen($contenido_json),
            'versión' => 1
        ]);
        $stmt = $conn->prepare("
            INSERT INTO plantillas_auditoria (
                plantilla_id, username, accion, detalles, ip_address
            ) VALUES (?, ?, 'CREATE', ?, INET6_ATON(?))
        ");
        $stmt->bind_param("isss", $plantilla_id, $username, $detalles, $client_ip);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        error_log("[plantillas_security] Plantilla {$plantilla_id} creada por {$username}");

        return [
            'success' => true,
            'plantilla_id' => $plantilla_id,
            'message' => 'Plantilla creada exitosamente'
        ];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("[plantillas_security] Error al crear plantilla: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error al crear la plantilla: ' . $e->getMessage()
        ];
    }
}

/**
 * Actualizar contenido de plantilla con versionado automático
 * 
 * @param mysqli $conn Conexión a BD
 * @param int $plantilla_id ID de la plantilla
 * @param string $username Usuario propietario (verificación)
 * @param array|string $contenido Nuevo contenido (array se convierte a JSON)
 * @param string $cambio_descripcion Descripción del cambio (opcional)
 * @param string $client_ip IP del cliente (para auditoría)
 * 
 * @return array ['success' => bool, 'version_nueva' => int, 'error' => string]
 */
function actualizar_plantilla_segura($conn, $plantilla_id, $username, $contenido, $cambio_descripcion = null, $client_ip = null)
{
    try {
        if (!$client_ip) {
            $client_ip = get_client_ip();
        }

        // Convertir array a JSON si es necesario
        if (is_array($contenido)) {
            $contenido_json = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $contenido_json = $contenido;
            // Validar que es JSON válido
            json_decode($contenido_json);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Contenido JSON inválido: " . json_last_error_msg());
            }
        }

        // Comenzar transacción
        $conn->begin_transaction();

        // Verificar permisos y obtener versión actual
        $stmt = $conn->prepare("
            SELECT version_actual, contenido FROM plantillas
            WHERE id = ? AND username = ? AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->bind_param("is", $plantilla_id, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Plantilla no encontrada o sin permisos");
        }
        $row = $result->fetch_assoc();
        $version_actual = $row['version_actual'];
        $contenido_anterior = $row['contenido'];
        $stmt->close();

        // Si el contenido no cambió, retornar la misma versión
        if ($contenido_anterior === $contenido_json) {
            error_log("[plantillas_security] Plantilla {$plantilla_id}: contenido idéntico, sin cambios");
            return [
                'success' => true,
                'version_nueva' => $version_actual,
                'message' => 'Contenido sin cambios'
            ];
        }

        // Establecer IP para triggers
        $conn->query("SET @client_ip = '" . $conn->real_escape_string($client_ip) . "'");

        // Incrementar versión
        $version_nueva = $version_actual + 1;

        // Actualizar plantilla
        $stmt = $conn->prepare("
            UPDATE plantillas 
            SET contenido = ?, version_actual = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("sii", $contenido_json, $version_nueva, $plantilla_id);
        $stmt->execute();
        $stmt->close();

        // Crear entrada en versiones
        $hash = hash('sha256', $contenido_json);
        $stmt = $conn->prepare("
            INSERT INTO plantillas_versiones (
                plantilla_id, version_numero, contenido, tamaño_bytes, 
                hash_contenido, cambio_descripcion, guardado_por, guardado_desde
            ) VALUES (?, ?, ?, ?, UNHEX(?), ?, ?, ?)
        ");
        $stmt->bind_param("iisisss", $plantilla_id, $version_nueva, $contenido_json, strlen($contenido_json), $hash, $cambio_descripcion, $username, $client_ip);
        $stmt->execute();
        $stmt->close();

        // Log en auditoría
        $detalles = json_encode([
            'versión_anterior' => $version_actual,
            'versión_nueva' => $version_nueva,
            'cambio_descripción' => $cambio_descripcion,
            'tamaño_anterior' => strlen($contenido_anterior),
            'tamaño_nuevo' => strlen($contenido_json),
            'contenido_cambió' => true
        ]);
        $stmt = $conn->prepare("
            INSERT INTO plantillas_auditoria (
                plantilla_id, username, accion, detalles, ip_address
            ) VALUES (?, ?, 'UPDATE', ?, INET6_ATON(?))
        ");
        $stmt->bind_param("isss", $plantilla_id, $username, $detalles, $client_ip);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        error_log("[plantillas_security] Plantilla {$plantilla_id} actualizada: v{$version_actual} → v{$version_nueva}");

        return [
            'success' => true,
            'version_nueva' => $version_nueva,
            'message' => "Plantilla actualizada a versión {$version_nueva}"
        ];

    } catch (Exception $e) {
        if ($conn->in_transaction) {
            $conn->rollback();
        }
        error_log("[plantillas_security] Error al actualizar plantilla: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error al actualizar: ' . $e->getMessage()
        ];
    }
}

/**
 * Eliminar plantilla de forma segura (soft delete)
 * 
 * @param mysqli $conn Conexión a BD
 * @param int $plantilla_id ID de la plantilla
 * @param string $username Usuario propietario (verificación)
 * @param string $client_ip IP del cliente (para auditoría)
 * 
 * @return array ['success' => bool, 'error' => string]
 */
function eliminar_plantilla_segura($conn, $plantilla_id, $username, $client_ip = null)
{
    try {
        if (!$client_ip) {
            $client_ip = get_client_ip();
        }

        // Usar procedimiento almacenado para máxima seguridad
        $stmt = $conn->prepare("CALL sp_plantillas_eliminar_seguro(?, ?, ?)");
        $stmt->bind_param("iss", $plantilla_id, $username, $client_ip);
        $stmt->execute();
        $stmt->close();

        error_log("[plantillas_security] Plantilla {$plantilla_id} eliminada (soft delete) por {$username}");

        return [
            'success' => true,
            'message' => 'Plantilla eliminada exitosamente'
        ];

    } catch (Exception $e) {
        error_log("[plantillas_security] Error al eliminar plantilla: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error al eliminar: ' . $e->getMessage()
        ];
    }
}

/**
 * Restaurar plantilla eliminada
 * 
 * @param mysqli $conn Conexión a BD
 * @param int $plantilla_id ID de la plantilla
 * @param string $username Usuario propietario (verificación)
 * @param string $client_ip IP del cliente (para auditoría)
 * 
 * @return array ['success' => bool, 'error' => string]
 */
function restaurar_plantilla($conn, $plantilla_id, $username, $client_ip = null)
{
    try {
        if (!$client_ip) {
            $client_ip = get_client_ip();
        }

        $stmt = $conn->prepare("CALL sp_plantillas_restaurar(?, ?, ?)");
        $stmt->bind_param("iss", $plantilla_id, $username, $client_ip);
        $stmt->execute();
        $stmt->close();

        error_log("[plantillas_security] Plantilla {$plantilla_id} restaurada por {$username}");

        return [
            'success' => true,
            'message' => 'Plantilla restaurada exitosamente'
        ];

    } catch (Exception $e) {
        error_log("[plantillas_security] Error al restaurar: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error al restaurar: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtener historial de versiones de una plantilla
 * 
 * @param mysqli $conn Conexión a BD
 * @param int $plantilla_id ID de la plantilla
 * @param string $username Usuario propietario (verificación)
 * 
 * @return array ['success' => bool, 'versiones' => array, 'error' => string]
 */
function obtener_historial_versiones($conn, $plantilla_id, $username)
{
    try {
        // Verificar permisos
        $stmt = $conn->prepare("
            SELECT id FROM plantillas WHERE id = ? AND username = ?
        ");
        $stmt->bind_param("is", $plantilla_id, $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception("Plantilla no encontrada o sin permisos");
        }
        $stmt->close();

        // Obtener versiones
        $result = $conn->query("
            SELECT
                version_numero,
                tamaño_bytes,
                cambio_descripcion,
                guardado_por,
                created_at,
                HEX(hash_contenido) as hash
            FROM plantillas_versiones
            WHERE plantilla_id = {$plantilla_id}
            ORDER BY version_numero DESC
        ");

        $versiones = [];
        while ($row = $result->fetch_assoc()) {
            $versiones[] = $row;
        }

        return [
            'success' => true,
            'versiones' => $versiones,
            'total' => count($versiones)
        ];

    } catch (Exception $e) {
        error_log("[plantillas_security] Error al obtener historial: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error al obtener historial: ' . $e->getMessage()
        ];
    }
}

/**
 * Restaurar plantilla a una versión anterior
 * 
 * @param mysqli $conn Conexión a BD
 * @param int $plantilla_id ID de la plantilla
 * @param int $numero_version Versión a restaurar
 * @param string $username Usuario propietario (verificación)
 * @param string $client_ip IP del cliente (para auditoría)
 * 
 * @return array ['success' => bool, 'version_nueva' => int, 'error' => string]
 */
function restaurar_version_anterior($conn, $plantilla_id, $numero_version, $username, $client_ip = null)
{
    try {
        if (!$client_ip) {
            $client_ip = get_client_ip();
        }

        // Verificar permisos
        $stmt = $conn->prepare("
            SELECT id FROM plantillas WHERE id = ? AND username = ? AND deleted_at IS NULL
        ");
        $stmt->bind_param("is", $plantilla_id, $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception("Plantilla no encontrada o sin permisos");
        }
        $stmt->close();

        // Obtener contenido de la versión
        $stmt = $conn->prepare("
            SELECT contenido FROM plantillas_versiones
            WHERE plantilla_id = ? AND version_numero = ?
        ");
        $stmt->bind_param("ii", $plantilla_id, $numero_version);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Versión no encontrada");
        }
        $row = $result->fetch_assoc();
        $contenido = $row['contenido'];
        $stmt->close();

        // Actualizar a esa versión
        $cambio_descripcion = "Restaurado desde versión {$numero_version}";
        return actualizar_plantilla_segura($conn, $plantilla_id, $username, $contenido, $cambio_descripcion, $client_ip);

    } catch (Exception $e) {
        error_log("[plantillas_security] Error al restaurar versión: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error al restaurar versión: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtener log de auditoría de una plantilla
 * 
 * @param mysqli $conn Conexión a BD
 * @param int $plantilla_id ID de la plantilla
 * @param string $username Usuario propietario (verificación)
 * @param int $limite Número de registros (default 50)
 * 
 * @return array ['success' => bool, 'auditoria' => array, 'error' => string]
 */
function obtener_auditoria_plantilla($conn, $plantilla_id, $username, $limite = 50)
{
    try {
        // Verificar permisos
        $stmt = $conn->prepare("
            SELECT id FROM plantillas WHERE id = ? AND username = ?
        ");
        $stmt->bind_param("is", $plantilla_id, $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception("Plantilla no encontrada o sin permisos");
        }
        $stmt->close();

        // Obtener auditoría
        $stmt = $conn->prepare("
            SELECT
                id,
                accion,
                detalles,
                ip_address,
                created_at
            FROM plantillas_auditoria
            WHERE plantilla_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $plantilla_id, $limite);
        $stmt->execute();
        $result = $stmt->get_result();

        $auditoria = [];
        while ($row = $result->fetch_assoc()) {
            $row['detalles'] = json_decode($row['detalles'], true);
            $row['ip_address'] = $row['ip_address'] ? inet_ntop(hex2bin($row['ip_address'])) : 'desconocida';
            $auditoria[] = $row;
        }
        $stmt->close();

        return [
            'success' => true,
            'auditoria' => $auditoria,
            'total' => count($auditoria)
        ];

    } catch (Exception $e) {
        error_log("[plantillas_security] Error al obtener auditoría: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error al obtener auditoría: ' . $e->getMessage()
        ];
    }
}

/**
 * Helper: Obtener IP del cliente
 * 
 * @return string IP del cliente
 */
function get_client_ip()
{
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) { // Cloudflare
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        return $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        return $_SERVER['HTTP_FORWARDED'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

?>
