<?php
/**
 * dashboard_simple.php
 * Dashboard simple sin componentes externos - versión funcional directa
 */

require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
require_once __DIR__ . '/../funciones/encryption.php';
start_secure_session();

// Verificación de inicio de sesión
if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$username = $_SESSION['username'];

// Obtener email del usuario
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

// === OBTENER PLANTILLAS PROPIAS ===
$plantillas_propias = [];
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
    while ($row = $result->fetch_assoc()) {
        $plantillas_propias[] = $row;
    }
    $stmt->close();
}

// === OBTENER PLANTILLAS COMPARTIDAS ===
$plantillas_compartidas = [];
if ($userEmail) {
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
        while ($row = $result->fetch_assoc()) {
            $plantillas_compartidas[] = $row;
        }
        $stmt->close();
    }
}

// === FUNCIÓN: Extraer totales ===
function extraer_totales($contenido) {
    $totales = ['ingresos' => 0.0, 'gastos' => 0.0];
    if (empty($contenido)) return $totales;
    
    try {
        // Intentar desencriptar
        try {
            $desenc = decrypt_content($contenido);
            $decoded = json_decode($desenc, true);
        } catch (Exception $e) {
            $decoded = json_decode($contenido, true);
        }
        
        if (!is_array($decoded)) return $totales;
        
        // Sumar ingresos
        if (!empty($decoded['trabajo']) && is_array($decoded['trabajo'])) {
            foreach ($decoded['trabajo'] as $t) {
                if (isset($t['total']) && $t['total'] !== '') {
                    $totales['ingresos'] += floatval($t['total']);
                }
            }
        }
        
        // Sumar gastos
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
    } catch (Exception $e) {
        error_log("Error extraer_totales: " . $e->getMessage());
    }
    
    return $totales;
}

// === Calcular totales agregados ===
$totales_propias = ['ingresos' => 0.0, 'gastos' => 0.0];
foreach ($plantillas_propias as $p) {
    $t = extraer_totales($p['contenido']);
    $totales_propias['ingresos'] += $t['ingresos'];
    $totales_propias['gastos'] += $t['gastos'];
}
$totales_propias['beneficio'] = $totales_propias['ingresos'] - $totales_propias['gastos'];

$totales_compartidas = ['ingresos' => 0.0, 'gastos' => 0.0];
foreach ($plantillas_compartidas as $p) {
    $t = extraer_totales($p['contenido']);
    $totales_compartidas['ingresos'] += $t['ingresos'];
    $totales_compartidas['gastos'] += $t['gastos'];
}
$totales_compartidas['beneficio'] = $totales_compartidas['ingresos'] - $totales_compartidas['gastos'];

// === Manejo de POST (crear plantilla) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_plantilla'])) {
    if (!validate_csrf()) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'CSRF inválido']));
    }
    
    require_once __DIR__ . '/../funciones/plantillas_security.php';
    
    $resultado = crear_plantilla_segura(
        $conn,
        $username,
        $_POST['nombre_plantilla'] ?? '',
        [],
        get_client_ip()
    );
    
    if (!$resultado['success']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $resultado['error']]);
        exit;
    }
    
    $plantillaId = $resultado['plantilla_id'];
    $redirectUrl = BASE_URL . '/plantillas/miplantilla.php?id=' . urlencode((string)$plantillaId);
    
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Mi cuenta</title>
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/src/img/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <meta name="base-url" content="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <style>
        .container { width: 95%; margin: 0 auto; padding: 8px 12px; box-sizing: border-box; }
        .content { width: 100%; padding: 18px; box-sizing: border-box; }
        .plantillas-lista { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; }
        .plantillas-item { margin: 0; display: flex; flex-direction: column; gap: 8px; background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.02)); padding: 12px; border-radius: 8px; min-height: 64px; box-sizing: border-box; }
        .plantillas-item a { text-decoration: none; color: #0b69ff; font-weight: 600; }
        .plantillas-item.hidden { display: none; }
        .btn { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 0.9rem; }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-primary:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; color: #fff; margin-right: 5px; }
        .btn-success { background: #28a745; color: #fff; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/nav/topnav.php'; ?>
    
    <div class="container">
        <div class="content">
            <h3>Crear Plantilla</h3>
            <form method="post" id="form-crear-plantilla" style="display:flex;gap:10px;margin-bottom:20px;">
                <?php echo csrf_input(); ?>
                <input type="text" name="nombre_plantilla" placeholder="Nombre de la plantilla" required style="flex:1;padding:10px;border:1px solid #ddd;border-radius:5px;">
                <button type="submit" name="crear_plantilla" class="btn btn-primary">Crear Nueva Plantilla</button>
            </form>

            <h3>Mis Plantillas (<?php echo count($plantillas_propias); ?>)</h3>
            <?php if (empty($plantillas_propias)): ?>
                <p style="color:#999;">No tienes plantillas aún.</p>
            <?php else: ?>
            <ul class="plantillas-lista">
                <?php foreach ($plantillas_propias as $p): ?>
                <li class="plantillas-item">
                    <a href="<?php echo BASE_URL; ?>/plantillas/miplantilla.php?id=<?php echo $p['id']; ?>">
                        <?php echo htmlspecialchars($p['nombre']); ?>
                    </a>
                    <?php $t = extraer_totales($p['contenido']); ?>
                    <div style="font-size:0.9rem;color:#666;">
                        📊 Ingresos: <?php echo number_format($t['ingresos'],2,',','.'); ?> € | 
                        Gastos: <?php echo number_format($t['gastos'],2,',','.'); ?> €
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="btn btn-success" onclick="location.href='<?php echo BASE_URL; ?>/plantillas/miplantilla.php?id=<?php echo $p['id']; ?>'">Editar</button>
                        <button type="button" class="btn btn-primary" onclick="alert('Compartir: ' + this.parentElement.parentElement.querySelector('a').innerText)">Compartir</button>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <h3 style="margin-top:30px;">Plantillas Compartidas Conmigo (<?php echo count($plantillas_compartidas); ?>)</h3>
            <?php if (empty($plantillas_compartidas)): ?>
                <p style="color:#999;">No tienes plantillas compartidas aún.</p>
            <?php else: ?>
            <ul class="plantillas-lista">
                <?php foreach ($plantillas_compartidas as $p): ?>
                <li class="plantillas-item">
                    <a href="<?php echo BASE_URL; ?>/plantillas/miplantilla.php?id=<?php echo $p['id']; ?>">
                        <?php echo htmlspecialchars($p['nombre']); ?>
                    </a>
                    <div style="font-size:0.85rem;color:#666;">
                        📤 Compartida por: <?php echo htmlspecialchars($p['username']); ?> | 
                        Rol: <strong><?php echo htmlspecialchars($p['rol']); ?></strong>
                    </div>
                    <?php $t = extraer_totales($p['contenido']); ?>
                    <div style="font-size:0.9rem;color:#666;">
                        📊 Ingresos: <?php echo number_format($t['ingresos'],2,',','.'); ?> € | 
                        Gastos: <?php echo number_format($t['gastos'],2,',','.'); ?> €
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="btn btn-success" onclick="location.href='<?php echo BASE_URL; ?>/plantillas/miplantilla.php?id=<?php echo $p['id']; ?>'">Ver</button>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Incluir modales si existen -->
    <?php if (file_exists(__DIR__ . '/../src/components/notice.php')) include __DIR__ . '/../src/components/notice.php'; ?>
    <?php if (file_exists(__DIR__ . '/../src/components/share-modal.php')) include __DIR__ . '/../src/components/share-modal.php'; ?>
    
    <script>
    // AJAX form submission
    document.getElementById('form-crear-plantilla').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                window.location.href = json.redirect;
            } else {
                alert('Error: ' + (json.error || 'desconocido'));
            }
        })
        .catch(err => console.error(err));
    });
    </script>
</body>
</html>
