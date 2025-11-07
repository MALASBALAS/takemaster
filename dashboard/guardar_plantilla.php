<?php
require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$username = $_SESSION['username'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!validate_csrf()) {
        http_response_code(400);
        die('CSRF inválido');
    }
    // Build a structured payload from POST arrays instead of storing raw $_POST.
    $idPlantilla = (int)($_POST['id_plantilla'] ?? 0);

    // Collect trabajo rows
    $trabajo = [];
    $estudios = $_POST['estudio'] ?? [];
    $tipos = $_POST['tipo_trabajo'] ?? [];
    $cgs = $_POST['cgs'] ?? [];
    $takes = $_POST['takes'] ?? [];
    $max = max(count($estudios), count($tipos), count($cgs), count($takes));
    for ($i = 0; $i < $max; $i++) {
        $est = trim((string)($estudios[$i] ?? ''));
        $tip = trim((string)($tipos[$i] ?? ''));
        $cg = is_numeric($cgs[$i] ?? null) ? (float)$cgs[$i] : null;
        $tk = is_numeric($takes[$i] ?? null) ? (float)$takes[$i] : null;
        // Skip empty rows
        if ($est === '' && $tip === '' && $cg === null && $tk === null) continue;
        $trabajo[] = [
            'estudio' => $est,
            'tipo' => $tip,
            'cgs' => $cg,
            'takes' => $tk,
        ];
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

    // Use transaction for safe write
    $conn->begin_transaction();
    try {
        if ($idPlantilla > 0) {
            $stmt = $conn->prepare("UPDATE plantillas SET contenido = ?, updated_at = NOW() WHERE id = ? AND username = ?");
            if (!$stmt) throw new Exception('Error al preparar la consulta de actualización.');
            $stmt->bind_param('sis', $contenido_json, $idPlantilla, $username);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                // Could be mismatch of username/id
                // We'll still commit but warn
            }
            $stmt->close();
        } else {
            $nombre = trim((string)($_POST['nombre'] ?? ('Plantilla ' . date('YmdHis'))));
            $stmt = $conn->prepare("INSERT INTO plantillas (username, nombre, contenido, updated_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt) throw new Exception('Error al preparar la inserción.');
            $stmt->bind_param('sss', $username, $nombre, $contenido_json);
            $stmt->execute();
            $idPlantilla = $stmt->insert_id;
            $stmt->close();
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
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
        echo json_encode(['success' => true, 'id' => $idPlantilla]);
        exit;
    }

    // Redireccionar a la página de 'Mi cuenta' dashboard con el id abierto (flujo clásico)
    header("Location: " . BASE_URL . "/pags/micuenta.php?section=dashboard&open_id=" . urlencode((string)$idPlantilla));
    exit;
}
?>
