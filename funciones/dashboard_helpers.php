<?php
/**
 * dashboard_helpers.php
 * Funciones auxiliares para dashboard.php
 * Extrae lógica de negocio para mejorar mantenimiento
 */

require_once __DIR__ . '/../src/nav/db_connection.php';
require_once __DIR__ . '/encryption.php';

/**
 * Obtiene todas las plantillas propias del usuario
 * @param mysqli $conn Conexión a BD
 * @param string $username Usuario actual
 * @return array Array de plantillas propias
 */
function obtener_plantillas_propias($conn, $username) {
    $plantillas = [];
    try {
        $stmt = $conn->prepare("
            SELECT id, nombre, contenido, username 
            FROM plantillas 
            WHERE username = ? AND deleted_at IS NULL 
            ORDER BY id DESC
        ");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $row['es_propia'] = true;
                    $plantillas[] = $row;
                }
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log("[dashboard_helpers] Error obtener_plantillas_propias: " . $e->getMessage());
    }
    return $plantillas;
}

/**
 * Obtiene todas las plantillas compartidas con el usuario
 * @param mysqli $conn Conexión a BD
 * @param string $userEmail Email del usuario actual
 * @return array Array de plantillas compartidas
 */
function obtener_plantillas_compartidas($conn, $userEmail) {
    $plantillas = [];
    if (!$userEmail) return $plantillas;
    
    try {
        $stmt = $conn->prepare("
            SELECT DISTINCT p.id, p.nombre, p.contenido, p.username, COALESCE(pc.rol, 'lector') as rol
            FROM plantillas p
            INNER JOIN plantillas_compartidas pc ON p.id = pc.id_plantilla
            WHERE pc.email = ? AND p.deleted_at IS NULL 
            ORDER BY p.id DESC
        ");
        if ($stmt) {
            $stmt->bind_param('s', $userEmail);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $row['es_propia'] = false;
                    $row['compartida_por'] = $row['username'];
                    $plantillas[] = $row;
                }
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log("[dashboard_helpers] Error obtener_plantillas_compartidas: " . $e->getMessage());
    }
    return $plantillas;
}

/**
 * Extrae ingresos y gastos de una plantilla
 * @param array $plantilla Plantilla con contenido JSON/encriptado
 * @return array ['ingresos' => float, 'gastos' => float]
 */
function extraer_totales_plantilla($plantilla) {
    $totales = ['ingresos' => 0.0, 'gastos' => 0.0];
    
    if (empty($plantilla['contenido'])) return $totales;
    
    try {
        // Intentar desencriptar
        try {
            $contenido_desencriptado = decrypt_content($plantilla['contenido']);
            $decoded = json_decode($contenido_desencriptado, true);
        } catch (Exception $e) {
            // Fallback a JSON directo (compatibilidad)
            $decoded = json_decode($plantilla['contenido'], true);
        }
        
        if (!is_array($decoded)) return $totales;
        
        // Sumar ingresos (trabajos)
        if (!empty($decoded['trabajo']) && is_array($decoded['trabajo'])) {
            foreach ($decoded['trabajo'] as $t) {
                if (isset($t['total']) && $t['total'] !== '') {
                    $totales['ingresos'] += floatval($t['total']);
                } elseif (isset($t['aplicado_cg']) || isset($t['aplicado_take'])) {
                    $totales['ingresos'] += floatval($t['aplicado_cg'] ?? 0) + floatval($t['aplicado_take'] ?? 0);
                }
            }
        }
        
        // Sumar gastos (variables + fijos)
        if (!empty($decoded['gastos_variables']) && is_array($decoded['gastos_variables'])) {
            foreach ($decoded['gastos_variables'] as $gv) {
                $totales['gastos'] += floatval($gv['monto'] ?? 0);
            }
        }
        if (!empty($decoded['gastos_fijos']) && is_array($decoded['gastos_fijos'])) {
            foreach ($decoded['gastos_fijos'] as $gf) {
                $totales['gastos'] += floatval($gf['monto'] ?? 0);
            }
        }
    } catch (Throwable $e) {
        error_log("[dashboard_helpers] Error extraer_totales_plantilla: " . $e->getMessage());
    }
    
    return $totales;
}

/**
 * Calcula agregados de ingresos/gastos para un array de plantillas
 * @param array $plantillas Array de plantillas
 * @param bool $solo_propias Si true, solo suma propias; si false, suma compartidas
 * @return array ['ingresos' => float, 'gastos' => float, 'beneficio' => float]
 */
function calcular_agregados($plantillas, $solo_propias = true) {
    $resultado = ['ingresos' => 0.0, 'gastos' => 0.0, 'beneficio' => 0.0];
    
    foreach ($plantillas as $plantilla) {
        $es_propia = $plantilla['es_propia'] ?? false;
        
        // Filtrar según criterio
        if ($solo_propias && !$es_propia) continue;
        if (!$solo_propias && $es_propia) continue;
        
        $totales = extraer_totales_plantilla($plantilla);
        $resultado['ingresos'] += $totales['ingresos'];
        $resultado['gastos'] += $totales['gastos'];
    }
    
    $resultado['beneficio'] = $resultado['ingresos'] - $resultado['gastos'];
    return $resultado;
}

/**
 * Obtiene permisos y colores para una plantilla compartida según rol
 * @param string $rol Rol del usuario ('lector', 'editor', 'admin')
 * @return array ['roleLabel', 'roleColor', 'roleBg', 'isReadOnly', 'canEdit', 'canShare']
 */
function obtener_permisos_rol($rol) {
    $rol = $rol ?? 'lector';
    
    $roleConfig = [
        'admin' => [
            'label' => 'Admin',
            'color' => '#28a745',      // verde
            'bg' => '#d4edda',         // verde claro
            'icon' => '✎',
            'isReadOnly' => false,
            'canEdit' => true,
            'canShare' => true,
            'message' => 'Acceso completo (editar + compartir)'
        ],
        'editor' => [
            'label' => 'Editor',
            'color' => '#0c5460',      // azul
            'bg' => '#d1ecf1',         // azul claro
            'icon' => '✎',
            'isReadOnly' => false,
            'canEdit' => true,
            'canShare' => false,
            'message' => 'Puedes editar'
        ],
        'lector' => [
            'label' => 'Lector',
            'color' => '#856404',      // naranja
            'bg' => '#fff3cd',         // amarillo claro
            'icon' => '👁️',
            'isReadOnly' => true,
            'canEdit' => false,
            'canShare' => false,
            'message' => 'Solo lectura'
        ]
    ];
    
    return $roleConfig[$rol] ?? $roleConfig['lector'];
}

/**
 * Separa plantillas en propias y compartidas
 * @param array $plantillas Array de todas las plantillas
 * @return array ['propias' => [], 'compartidas' => []]
 */
function separar_plantillas($plantillas) {
    $propias = [];
    $compartidas = [];
    
    foreach ($plantillas as $p) {
        if ($p['es_propia']) {
            $propias[] = $p;
        } else {
            $compartidas[] = $p;
        }
    }
    
    return ['propias' => $propias, 'compartidas' => $compartidas];
}

/**
 * Obtiene el email del usuario desde su username
 * @param mysqli $conn Conexión a BD
 * @param string $username Username del usuario
 * @return string|null Email del usuario o null
 */
function obtener_email_usuario($conn, $username) {
    $email = null;
    try {
        $stmt = $conn->prepare("SELECT email FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $email = $row['email'];
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log("[dashboard_helpers] Error obtener_email_usuario: " . $e->getMessage());
    }
    return $email;
}
