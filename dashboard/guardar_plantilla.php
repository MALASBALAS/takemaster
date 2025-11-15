<?php
require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
require_once __DIR__ . '/../funciones/validate_plantilla_access.php';
start_secure_session();

if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$username = $_SESSION['username'];

// Get user's email from database (needed for shared plantilla permission checks)
$userEmail = null;
$stmt = $conn->prepare("SELECT email FROM users WHERE username = ?");
if ($stmt) {
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $userEmail = $row['email'];
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!validate_csrf()) {
        http_response_code(400);
        die('CSRF inválido');
    }
    // Construir una carga estructurada (payload) a partir de los arrays POST en lugar de almacenar raw $_POST.
    $idPlantilla = (int)($_POST['id_plantilla'] ?? 0);

    // Recoger las filas de 'trabajo'
    $trabajo = [];
    $estudios = $_POST['estudio'] ?? [];
    $tipos = $_POST['tipo_trabajo'] ?? [];
    $comentarios = $_POST['comentario_tipo'] ?? [];
    $cgs = $_POST['cgs'] ?? [];
    $totales = $_POST['total'] ?? [];
    $takes = $_POST['takes'] ?? [];
    $fechas = $_POST['trabajo_fecha'] ?? [];
    $max = max(count($estudios), count($tipos), count($cgs), count($takes), count($comentarios), count($fechas));
    for ($i = 0; $i < $max; $i++) {
        $est = trim((string)($estudios[$i] ?? ''));
        $tip = trim((string)($tipos[$i] ?? ''));
        $comentario = trim((string)($comentarios[$i] ?? ''));
    // Fecha por fila (opcional) - normalizar a YYYY-MM-DD si es válida
        $fecha_row_raw = trim((string)($fechas[$i] ?? ''));
        $fecha_row = null;
        if ($fecha_row_raw !== '') {
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha_row_raw, $mf)) {
                $yy = (int)$mf[1]; $mmo = (int)$mf[2]; $dd = (int)$mf[3];
                if (checkdate($mmo, $dd, $yy)) {
                    $fecha_row = sprintf('%04d-%02d-%02d', $yy, $mmo, $dd);
                }
            }
        }
    // Tratar CGs/Takes como enteros (conteos)
        $cg = null;
        $tk = null;
        if (isset($cgs[$i]) && is_numeric($cgs[$i])) {
            $cg = (int)floor((float)$cgs[$i]);
            if ($cg < 0) $cg = 0;
        }
        if (isset($takes[$i]) && is_numeric($takes[$i])) {
            $tk = (int)floor((float)$takes[$i]);
            if ($tk < 0) $tk = 0;
        }
        $total_row = is_numeric($totales[$i] ?? null) ? (float)$totales[$i] : null;
        // Skip empty rows (also consider fecha)
        if ($est === '' && $tip === '' && $cg === null && $tk === null && $comentario === '' && $fecha_row === null) continue;
        $trabajo[] = [
            'estudio' => $est,
            'tipo' => $tip,
            'comentario_tipo' => $comentario,
            'fecha' => $fecha_row,
            'cgs' => $cg,
            'takes' => $tk,
            'total' => $total_row,
        ];
    }

    // Server-side mapping of community -> rates (authoritative)
    $communityRates = [
        'Madrid' => ['Serie' => ['cg' => 49.31, 'take' => 5.41], 'Cine' => ['cg' => 65.76, 'take' => 7.20]],
        'Comunidad Valenciana' => ['Serie' => ['cg' => 25.75, 'take' => 2.78], 'Cine' => ['cg' => 29.61, 'take' => 3.20]],
        'Cataluña' => ['Serie' => ['cg' => 39.74, 'take' => 4.36], 'Cine' => ['cg' => 54.40, 'take' => 6.04]],
        'Galicia' => ['Serie' => ['cg' => 35.93, 'take' => 3.01], 'Cine' => ['cg' => 35.93, 'take' => 3.01]],
    ];

    // Descripción opcional de la plantilla
    $descripcion_plantilla = trim((string)($_POST['descripcion_plantilla'] ?? ''));
    // Fecha de la plantilla (opcional) - expected format YYYY-MM-DD
    $fecha_input = trim((string)($_POST['fecha_plantilla'] ?? ''));
    $fecha_valida = null;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha_input, $m)) {
        $y = (int)$m[1]; $mo = (int)$m[2]; $d = (int)$m[3];
        if (checkdate($mo, $d, $y)) {
            $fecha_valida = sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }
    }
    // Comunidad autónoma (por defecto Madrid) - sanitize & whitelist
    $comunidad_plantilla = trim((string)($_POST['comunidad_plantilla'] ?? 'Madrid'));
    // Only allow communities that have server-side rate mappings
    $allowed_comunidades = [
        'Madrid', 'Comunidad Valenciana', 'Cataluña', 'Galicia'
    ];
    if (!in_array($comunidad_plantilla, $allowed_comunidades, true)) {
        // Fallback to Madrid if an unexpected value is received
        $comunidad_plantilla = 'Madrid';
    }
    // Confirmación (checkbox) -- may be absent for some AJAX flows; store boolean flag
    $comunidad_confirm = !empty($_POST['comunidad_confirm']) ? true : false;
    // Usar neto flag (checkbox)
    $usar_neto = !empty($_POST['usar_neto']) ? true : false;

    // Enrich trabajo rows with the applied rates according to comunidad and tipo
    for ($i = 0; $i < count($trabajo); $i++) {
        $tipo = $trabajo[$i]['tipo'] ?? '';
        // Preserve the user-provided values in 'cgs' and 'takes' (counts or amounts)
    $user_cgs = $trabajo[$i]['cgs'];
    $user_takes = $trabajo[$i]['takes'];

        $applied_cg = null;
        $applied_take = null;
    // Si disponemos de una tarifa server-side para esta comunidad+tipo, calcular los importes aplicados como: tarifa_unidad * cantidad_usuario
        if (isset($communityRates[$comunidad_plantilla]) && isset($communityRates[$comunidad_plantilla][$tipo])) {
            $rate_unit_cg = (float)$communityRates[$comunidad_plantilla][$tipo]['cg'];
            $rate_unit_take = (float)$communityRates[$comunidad_plantilla][$tipo]['take'];
            // Si el usuario proporcionó conteos numéricos, multiplicar; en caso contrario tratar la tarifa por unidad como el importe
            // user_cgs/user_takes son enteros (conteos). Multiplicar la tarifa por unidad por la cantidad para obtener el importe aplicado.
            $applied_cg = is_numeric($user_cgs) ? $rate_unit_cg * (int)$user_cgs : $rate_unit_cg;
            $applied_take = is_numeric($user_takes) ? $rate_unit_take * (int)$user_takes : $rate_unit_take;
        } else {
            // No mapping: fallback to whatever the user provided (if numeric)
            $applied_cg = is_numeric($user_cgs) ? (float)$user_cgs : null;
            $applied_take = is_numeric($user_takes) ? (float)$user_takes : null;
        }

        // Do NOT overwrite the original 'cgs'/'takes' fields — keep user input intact.

    // Si el total no fue suministrado por el cliente, calcularlo como la suma de los importes aplicados (cuando estén disponibles)
        if (empty($trabajo[$i]['total'])) {
            $t = 0.0;
            if (is_numeric($applied_cg)) $t += $applied_cg;
            if (is_numeric($applied_take)) $t += $applied_take;
            if ($t > 0) $trabajo[$i]['total'] = $t;
        }

        // Store applied amounts for traceability
        $trabajo[$i]['aplicado_cg'] = $applied_cg;
        $trabajo[$i]['aplicado_take'] = $applied_take;
    }

    // Gastos variables
    $gastos_variables = [];
    $tg_var = $_POST['tipo_gasto_var'] ?? [];
    $desc_var = $_POST['descripcion_gasto_var'] ?? [];
    $monto_var = $_POST['monto_gasto_var'] ?? [];
    $max = max(count($tg_var), count($desc_var), count($monto_var));
    for ($i = 0; $i < $max; $i++) {
        $tipo = trim((string)($tg_var[$i] ?? ''));
        $desc = trim((string)($desc_var[$i] ?? ''));
        $monto = is_numeric($monto_var[$i] ?? null) ? (float)$monto_var[$i] : null;
        if ($tipo === '' && $desc === '' && $monto === null) continue;
        $gastos_variables[] = ['tipo' => $tipo, 'descripcion' => $desc, 'monto' => $monto];
    }

    // Gastos fijos
    $gastos_fijos = [];
    $tg_fijo = $_POST['tipo_gasto_fijo'] ?? [];
    $desc_fijo = $_POST['descripcion_gasto_fijo'] ?? [];
    $monto_fijo = $_POST['monto_gasto_fijo'] ?? [];
    $max = max(count($tg_fijo), count($desc_fijo), count($monto_fijo));
    for ($i = 0; $i < $max; $i++) {
        $tipo = trim((string)($tg_fijo[$i] ?? ''));
        $desc = trim((string)($desc_fijo[$i] ?? ''));
        $monto = is_numeric($monto_fijo[$i] ?? null) ? (float)$monto_fijo[$i] : null;
        if ($tipo === '' && $desc === '' && $monto === null) continue;
        $gastos_fijos[] = ['tipo' => $tipo, 'descripcion' => $desc, 'monto' => $monto];
    }

    $payload = [
        'trabajo' => $trabajo,
        'gastos_variables' => $gastos_variables,
        'gastos_fijos' => $gastos_fijos,
    'descripcion' => $descripcion_plantilla,
    'fecha' => $fecha_valida,
    'comunidad' => $comunidad_plantilla,
    'comunidad_confirm' => $comunidad_confirm,
    'usar_neto' => $usar_neto,
        'meta' => [
            'saved_at' => date('c'),
            'saved_by' => $username,
        ],
    ];

    $contenido_json = json_encode($payload, JSON_UNESCAPED_UNICODE);

    // Basic size check
    if ($contenido_json === false || strlen($contenido_json) > 1024 * 1024 * 2) { // limit ~2MB
        http_response_code(400);
        die('Contenido inválido o demasiado grande.');
    }

    // Debug: log the payload on non-production environments to help trace issues
    if (defined('APP_ENV') && APP_ENV !== 'production') {
        error_log('[DEBUG] guardar_plantilla payload length=' . strlen($contenido_json) . ' payload=' . $contenido_json);
    }

    // Usar función de seguridad con auditoría y versionado automático
    require_once __DIR__ . '/../funciones/plantillas_security.php';

    try {
        if ($idPlantilla > 0) {
            // 🔐 VALIDACIÓN ROBUSTA: Verifica acceso y rechaza explícitamente a 'lectores'
            // Si no tiene permiso, esta función termina la ejecución con HTTP 403 + JSON error
            require_plantilla_edit_access($conn, $idPlantilla, $username, $userEmail);
            
            // Actualizar plantilla existente con auditoría y versiones
            $resultado = actualizar_plantilla_segura(
                $conn,
                $idPlantilla,
                $username,
                $contenido_json,
                'Cambios guardados desde editor',
                get_client_ip()
            );
            
            if (!$resultado['success']) {
                http_response_code(500);
                die(json_encode(['success' => false, 'error' => $resultado['error']]));
            }
        } else {
            // Crear plantilla nueva con auditoría
            $nombre = trim((string)($_POST['nombre'] ?? ('Plantilla ' . date('YmdHis'))));
            $resultado = crear_plantilla_segura(
                $conn,
                $username,
                $nombre,
                $payload,
                get_client_ip()
            );
            
            if (!$resultado['success']) {
                http_response_code(500);
                die(json_encode(['success' => false, 'error' => $resultado['error']]));
            }
            
            $idPlantilla = $resultado['plantilla_id'];
        }
    } catch (Exception $e) {
        http_response_code(500);
        if (defined('APP_ENV') && APP_ENV !== 'production') {
            die('Error al guardar plantilla: ' . $e->getMessage());
        }
        die('Error interno al guardar plantilla.');
    }

    // Detectar llamada AJAX (fetch con X-Requested-With) o petición que acepte JSON
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        $response = ['success' => true, 'id' => $idPlantilla];
        // expose some debug info in non-production to ease troubleshooting
        if (defined('APP_ENV') && APP_ENV !== 'production') {
            $response['debug'] = ['len' => strlen($contenido_json)];
        }
        echo json_encode($response);
        exit;
    }

    // Redireccionar a la página de 'Mi cuenta' dashboard con el id abierto (flujo clásico)
    header("Location: " . BASE_URL . "/pags/micuenta.php?section=dashboard&open_id=" . urlencode((string)$idPlantilla));
    exit;
}
