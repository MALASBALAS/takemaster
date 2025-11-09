<?php
require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

// --- Captura robusta de errores: recoger advertencias/excepciones de PHP y emitirlas en la consola del navegador
$GLOBALS['__php_errors'] = [];
$__prev_error_handler = set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $entry = ['type' => 'error', 'errno' => $errno, 'message' => $errstr, 'file' => $errfile, 'line' => $errline];
    $GLOBALS['__php_errors'][] = $entry;
    // no ejecutar el manejador interno de PHP (evita salidas duplicadas); continuar la ejecución
    return true;
});
set_exception_handler(function($ex) {
    $GLOBALS['__php_errors'][] = ['type' => 'exception', 'message' => $ex->getMessage(), 'file' => $ex->getFile(), 'line' => $ex->getLine()];
});

// Manejador de shutdown: capturar errores fatales y reportarlos de forma segura.
// Solo emitir información detallada de los errores al navegador cuando estemos en entorno de desarrollo.
register_shutdown_function(function() {
    $last = error_get_last();
    if ($last && ($last['type'] & (E_ERROR|E_PARSE|E_CORE_ERROR|E_COMPILE_ERROR|E_USER_ERROR))) {
        $GLOBALS['__php_errors'][] = ['type' => 'shutdown', 'message' => $last['message'], 'file' => $last['file'], 'line' => $last['line']];
    }
    if (!empty($GLOBALS['__php_errors'])) {
        $payload = json_encode($GLOBALS['__php_errors'], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    // En desarrollo, emitir un snippet JS seguro para que la consola del frontend muestre los errores PHP.
        if (defined('APP_ENV') && APP_ENV === 'development') {
            echo "\n<script>try{console.group('PHP errors'); var __errs = $payload || []; __errs.forEach(function(e){ console.error('[PHP] '+(e.type||'error')+': '+(e.message||'')+ ' at ' + (e.file||'') + ':' + (e.line||'') ); }); console.groupEnd();}catch(e){/* ignore */}</script>\n";
        } else {
            // En producción: registrar una versión compacta en el log del servidor sin filtrar detalles a los clientes.
            // Guardar un prefijo corto y el número de errores.
            $summary = 'PHP errors detected (' . count($GLOBALS['__php_errors']) . '), details logged on server.';
            error_log($summary);
        }
    }
});


// Verificación de inicio de sesión
if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$username = $_SESSION['username'];

// Manejo de la creación y eliminación de plantillas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear plantilla
    if (isset($_POST['crear_plantilla'])) {
        if (!validate_csrf()) {
            http_response_code(400);
            die('CSRF inválido');
        }
        $nombrePlantilla = $_POST['nombre_plantilla'];
        
        $stmt = $conn->prepare("INSERT INTO plantillas (username, nombre) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $nombrePlantilla);
        $stmt->execute();
        $plantillaId = $stmt->insert_id;
        $stmt->close();

    // Redirigir al editor de la plantilla recién creada tras una pequeña espera (1 segundo) para permitir feedback en la interfaz
    $redirectUrl = BASE_URL . '/plantillas/miplantilla.php?id=' . urlencode((string)$plantillaId);

    // Si la petición se envió vía AJAX (fetch/XHR), devolver JSON y permitir que el cliente
    // renderice un banner inline bajo el contenido. Si no, usar el comportamiento
    // tradicional (página intersticial pequeña + redirección) para envíos no-AJAX.
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
        exit;
    }

    // Fallback para no-AJAX: mostrar la página intersticial existente (mantiene compatibilidad hacia atrás)
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Plantilla creada</title>';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    // Keep styling minimal and only change layout/spacing — do NOT modify colors (inherit from site styles)
    echo '<style>
        html,body{height:100%}
        body{font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; display:flex; align-items:center; justify-content:center; height:100vh; margin:0;}
        .box{padding:24px;border-radius:12px;max-width:480px;width:92%;box-sizing:border-box;text-align:center;}
        .icon{width:72px;height:72px;margin:0 auto 12px;display:block}
        h2{margin:0 0 6px;font-size:1.25rem}
        .muted{margin:6px 0}
        .actions{margin-top:14px}
        .link{display:inline-block;padding:8px 12px;border-radius:8px}
        .spinner{width:36px;height:6px;margin:12px auto 0;border-radius:999px;background:linear-gradient(90deg, rgba(0,0,0,0.12), rgba(0,0,0,0.04));animation:spin 1s linear infinite}
        @keyframes spin{0%{transform:translateX(-10px)}50%{transform:translateX(10px)}100%{transform:translateX(-10px)}}
    </style>';

    // Contenido mejorado: icono SVG de éxito, mensaje, spinner y enlace claro de acción
    echo '</head><body><div class="box">';
    echo '<svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">';
    echo '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" opacity="0.12"/>'; 
    echo '<path d="M9.5 12.75l1.8 1.8 3.7-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>'; 
    echo '</svg>';
    echo '<h2>Plantilla creada</h2>';
    echo '<p class="muted">Redirigiendo al editor...</p>';
    echo '<div class="spinner" aria-hidden="true"></div>';
    echo '<div class="actions"><a class="link" href="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">Ir ahora al editor</a></div>';
    echo '</div>';
    // Usar json_encode para emitir de forma segura la URL dentro de JavaScript
    echo '<script>setTimeout(function(){window.location.href=' . json_encode($redirectUrl) . ';}, 1000);</script>';
    echo '</body></html>';
    exit;
    } 
    // Eliminar plantilla
    elseif (isset($_POST['eliminar_plantilla'])) {
        if (!validate_csrf()) {
            http_response_code(400);
            die('CSRF inválido');
        }
        $idPlantilla = $_POST['eliminar_plantilla'];
        
        $stmt = $conn->prepare("DELETE FROM plantillas WHERE id = ? AND username = ?");
        $stmt->bind_param("is", $idPlantilla, $username);
        $stmt->execute();
        $stmt->close();

    header("Location: " . BASE_URL . "/pags/micuenta.php?section=dashboard");
        exit;
    }
}

?>

<?php
$
// Detectar si este archivo está siendo incluido desde otro script (p.ej., micuenta.php).
// Si se incluye, evitar imprimir el documento HTML completo (<html>, <head>, <body>) para prevenir documentos anidados.
$isIncluded = realpath(__FILE__) !== realpath($_SERVER['SCRIPT_FILENAME']);
?>

<?php if (!$isIncluded): ?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php endif; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Mi cuenta</title>
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/src/img/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <style>
        .container {
            /* Dashboard-specific: occupy 95% width and center horizontally */
            display: block;
            width: 95%;
            margin: 0 auto; /* center left and right */
            box-sizing: border-box;
            padding: 8px 12px;
        }
        .content {
            /* flexible width that respects a left sidebar if present (uses CSS variable from micuenta.php) */
            width: 100%;
            padding: 18px;
            box-sizing: border-box;
            margin: 0 auto;
            background: transparent;
        }
        .plantillas-lista {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            /* Prefer columns of ~400px, wrapping as needed; fallback to flexible columns */
            /* prefer cards ~320-420px, allow tighter packing on narrow screens */
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
            align-items: start;
            justify-content: start;
        }
        .plantillas-item {
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.02));
            padding: 12px;
            border-radius: 8px;
            min-height: 64px;
            box-sizing: border-box;
            /* Prefer full width of the grid cell; let the grid control sizing */
            width: 100%;
            max-width: 420px;
        }
        .plantillas-item a {
            text-decoration: none;
            color: #0b69ff;
            font-weight: 600;
            display: inline-block;
            word-break: break-word;
            max-width: 100%;
        }

    /* Ensure buttons inside list items don't expand full width (override global form button rule) */
    .plantillas-item .actions { display: flex; gap: 8px; align-items: center; justify-content: flex-end; }
    .plantillas-item form { display: inline-block; margin: 0; }
    .plantillas-item form button { width: auto !important; display: inline-block !important; padding: 6px 10px; }
    .plantillas-item > button { width: auto; padding: 6px 10px; }

        .plantillas-item:hover{
            color:#aaa;
        }
        /* Only style specific buttons here to avoid affecting all buttons globally */
        .btn-crear-plantilla {
            background-color: #007bff;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
        }
        .btn-crear-plantilla:hover {
            background-color: #0056b3;
        }

        /* Buttons inside plantilla items (Eliminar, Compartir) */
        .plantillas-item .actions button,
        .plantillas-item button,
        #popupCompartir button {
            background-color: #007bff;
            color: #fff;
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            margin-left: 8px;
            font-size: 0.95rem;
        }
        .plantillas-item button:hover,
        #popupCompartir button:hover {
            background-color: #0056b3;
        }
        /* Estilos para el popup */
        .popup {
            display: none; 
            position: fixed; 
            z-index: 100; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
            padding-top: 60px; 
        }
        .popup-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        /* Responsive tweaks: on small screens use a single column and make actions stack */
        @media (max-width: 920px) {
            .plantillas-lista { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
            .plantillas-item { max-width: 100%; }
        }

        @media (max-width: 720px) {
            .plantillas-lista { grid-template-columns: 1fr; justify-content: stretch; }
            /* stack content inside each card on mobile */
            .plantillas-item { width: 100%; flex-direction: column; align-items: stretch; justify-content: flex-start; }
            .plantillas-item .actions { display:flex; gap:8px; margin-top:8px; justify-content:flex-end; flex-wrap:wrap }
            .plantillas-item .actions button { flex: 1 1 auto; }
            .content { max-width: 100%; padding: 12px; }
        }

        @media (max-width: 480px) {
            .plantillas-item { padding: 10px; border-radius:10px }
            .plantillas-item a { font-size: 1rem }
            .plantillas-item .actions button { padding:10px; font-size:0.95rem }
            form input[type="text"]{width:100%;padding:10px;margin-bottom:10px}
            .btn-crear-plantilla{width:100%;display:block}
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/nav/topnav.php'; ?>
    <div class="container">
        <div class="content">
            <h3>Crear Plantilla</h3>
            <form method="post" id="form-crear-plantilla">
                <?php echo csrf_input(); ?>
                <input type="text" name="nombre_plantilla" placeholder="Nombre de la plantilla" required>
                <button type="submit" name="crear_plantilla" class="btn-crear-plantilla">Crear Nueva Plantilla</button>
            </form>
            <h3>Plantillas</h3>
            <?php
            // Asegurarse de que $plantillas está definida cuando este archivo se incluye desde otro sitio
            // (p.ej., micuenta.php). Si el llamador no la proporciona, cargarla desde la base de datos.
            if (!isset($plantillas)) {
                $plantillas = [];
                try {
                    if (isset($conn) && $conn instanceof mysqli) {
                        $stmt = $conn->prepare("SELECT id, nombre, contenido FROM plantillas WHERE username = ? ORDER BY id DESC");
                        if ($stmt) {
                            $stmt->bind_param('s', $username);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result) {
                                while ($row = $result->fetch_assoc()) {
                                    $plantillas[] = $row;
                                }
                            }
                            $stmt->close();
                        } else {
                            $GLOBALS['__php_errors'][] = ['type' => 'db', 'message' => 'Failed to prepare statement for fetching plantillas', 'file' => __FILE__, 'line' => __LINE__];
                        }
                    } else {
                        $GLOBALS['__php_errors'][] = ['type' => 'db', 'message' => 'Database connection ($conn) not available or not mysqli', 'file' => __FILE__, 'line' => __LINE__];
                    }
                } catch (Throwable $e) {
                    $GLOBALS['__php_errors'][] = ['type' => 'exception', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()];
                }
            }
            ?>
            <?php
            // Aggregate totals across all plantillas so we can show a single global donut chart.
            $total_ingresos = 0.0;
            $total_gastos = 0.0;
            foreach ($plantillas as $p) {
                if (!empty($p['contenido'])) {
                    $d = json_decode($p['contenido'], true);
                    if (is_array($d)) {
                        if (!empty($d['trabajo']) && is_array($d['trabajo'])) {
                            foreach ($d['trabajo'] as $t) {
                                if (isset($t['total']) && $t['total'] !== '') {
                                    $total_ingresos += floatval($t['total']);
                                } elseif (isset($t['aplicado_cg']) || isset($t['aplicado_take'])) {
                                    $total_ingresos += floatval($t['aplicado_cg'] ?? 0) + floatval($t['aplicado_take'] ?? 0);
                                }
                            }
                        }
                        if (!empty($d['gastos_variables']) && is_array($d['gastos_variables'])) {
                            foreach ($d['gastos_variables'] as $gv) {
                                $total_gastos += floatval($gv['monto'] ?? 0);
                            }
                        }
                        if (!empty($d['gastos_fijos']) && is_array($d['gastos_fijos'])) {
                            foreach ($d['gastos_fijos'] as $gf) {
                                $total_gastos += floatval($gf['monto'] ?? 0);
                            }
                        }
                    }
                }
            }
            $total_beneficio = $total_ingresos - $total_gastos;
            ?>

            <div style="margin:14px auto;display:flex;gap:18px;align-items:center;flex-wrap:wrap;justify-content:center;max-width:600px;">
                <div style="flex:0 0 220px;max-width:220px;">
                    <canvas id="chart-pie-all" class="cm-chart-widget" data-type="doughnut" data-ingresos="<?php echo htmlspecialchars((string)$total_ingresos, ENT_QUOTES); ?>" data-gastos="<?php echo htmlspecialchars((string)$total_gastos, ENT_QUOTES); ?>" data-beneficio="<?php echo htmlspecialchars((string)$total_beneficio, ENT_QUOTES); ?>" width="200" height="200" style="display:block;max-width:220px;width:100%;height:auto;" aria-label="Gráfico circular total gastos y beneficio"></canvas>
                </div>
                <div style="flex:1 1 260px;min-width:180px;font-size:0.95rem;color:#333;">
                    <div><strong>Total Ingresos:</strong> <?php echo number_format($total_ingresos,2,',','.'); ?> €</div>
                    <div><strong>Total Gastos:</strong> <?php echo number_format($total_gastos,2,',','.'); ?> €</div>
                    <div><strong>Beneficio Total:</strong> <?php echo number_format($total_beneficio,2,',','.'); ?> €</div>
                </div>
            </div>

            <ul class="plantillas-lista">
                <?php foreach ($plantillas as $plantilla) : ?>
                    <?php try { ?>
                    <li class="plantillas-item">
                        <a href="<?php echo BASE_URL; ?>/plantillas/miplantilla.php?id=<?php echo $plantilla['id']; ?>">
                            <?php echo htmlspecialchars($plantilla['nombre']); ?>
                        </a>
                        <?php
                        // Prepare summary numbers for a small chart: ingresos (trabajos) and gastos (variables + fijos)
                        $ingresos = 0.0;
                        $gastos = 0.0;
                        if (!empty($plantilla['contenido'])) {
                            $decoded = json_decode($plantilla['contenido'], true);
                            if (is_array($decoded)) {
                                // sum trabajos totals (try several keys)
                                if (!empty($decoded['trabajo']) && is_array($decoded['trabajo'])) {
                                    foreach ($decoded['trabajo'] as $t) {
                                        // prefer server-calculated 'total' if present
                                        if (isset($t['total']) && $t['total'] !== '') {
                                            $ingresos += floatval($t['total']);
                                        } elseif (isset($t['aplicado_cg']) || isset($t['aplicado_take'])) {
                                            $ingresos += floatval($t['aplicado_cg'] ?? 0) + floatval($t['aplicado_take'] ?? 0);
                                        }
                                    }
                                }
                                // sum gastos
                                if (!empty($decoded['gastos_variables']) && is_array($decoded['gastos_variables'])) {
                                    foreach ($decoded['gastos_variables'] as $gv) {
                                        $gastos += floatval($gv['monto'] ?? 0);
                                    }
                                }
                                if (!empty($decoded['gastos_fijos']) && is_array($decoded['gastos_fijos'])) {
                                    foreach ($decoded['gastos_fijos'] as $gf) {
                                        $gastos += floatval($gf['monto'] ?? 0);
                                    }
                                }
                            }
                        }
                        ?>

                        <?php
                        // Solo renderizar los gráficos si tenemos valores no nulos que mostrar. Evitar crear canvases vacíos cuando no hay datos.
                        $beneficio = $ingresos - $gastos;
                        $show_charts = (abs($ingresos) > 0.0001) || (abs($gastos) > 0.0001);
                        if ($show_charts) :
                        ?>
                        <div style="margin-top:8px;display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                            <!-- Fixed size canvas using attributes to avoid Chart.js resize loop (prevents infinite height bug) -->
                            <div style="flex:1 1 320px;max-width:320px;">
                                <canvas id="chart-<?php echo $plantilla['id']; ?>" class="plantilla-chart cm-chart-widget" data-type="bar" data-ingresos="<?php echo htmlspecialchars((string)$ingresos, ENT_QUOTES); ?>" data-gastos="<?php echo htmlspecialchars((string)$gastos, ENT_QUOTES); ?>" width="320" height="120" style="display:block;max-width:320px;width:100%;height:auto;" aria-label="Gráfico de ingresos y gastos"></canvas>
                                <div style="display:flex;gap:6px;align-items:center;justify-content:center;margin-top:8px;">
                                    <button type="button" class="chart-filter" data-target="<?php echo $plantilla['id']; ?>" data-mode="both">Todos</button>
                                    <button type="button" class="chart-filter" data-target="<?php echo $plantilla['id']; ?>" data-mode="ingresos">Ingresos</button>
                                    <button type="button" class="chart-filter" data-target="<?php echo $plantilla['id']; ?>" data-mode="gastos">Gastos</button>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div style="margin-top:8px;padding:10px;border-radius:6px;background:rgba(0,0,0,0.02);color:#666;font-size:0.95rem;">
                            <em>No hay datos suficientes para mostrar gráficos.</em>
                        </div>
                        <?php endif; ?>
                        <form method="post" data-plantilla-form="<?php echo $plantilla['id']; ?>" style="display: inline;">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="eliminar_plantilla" value="<?php echo $plantilla['id']; ?>">
                            <button type="button" class="delete-btn" data-plantilla-id="<?php echo $plantilla['id']; ?>">Eliminar</button>
                            <button type="button" class="share-btn" data-plantilla-id="<?php echo $plantilla['id']; ?>">Compartir</button>

                        </form>
                    </li>
                    <?php } catch (Throwable $th) {
                        // capture rendering errors per-item and continue
                        $GLOBALS['__php_errors'][] = ['type' => 'render', 'message' => $th->getMessage(), 'file' => $th->getFile(), 'line' => $th->getLine()];
                        ?>
                        <li class="plantillas-item">
                            <div style="background:#fff5f5;border:1px solid #f5c6cb;color:#721c24;padding:10px;border-radius:6px;">Error al renderizar esta plantilla. Revisa la consola para más detalles.</div>
                        </li>
                    <?php } ?>
                <?php endforeach; ?>
            </ul>

            <?php // Usar los componentes ShareModal y ConfirmModal en lugar del popup inline. ?>
            <?php include __DIR__ . '/../src/components/notice.php'; ?>
            <?php include __DIR__ . '/../src/components/share-modal.php'; ?>
            <?php include __DIR__ . '/../src/components/confirm-modal.php'; ?>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>/src/js/components/share-modal.js"></script>
    <script src="<?php echo BASE_URL; ?>/src/js/components/confirm-modal.js"></script>
    <script src="<?php echo BASE_URL; ?>/src/js/components/notice.js"></script>

<!-- Chart.js (CDN) and initialization script for plantilla charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function(){
    // Envío AJAX para crear plantilla: mostrar un banner inline bajo el contenido
    // en lugar de navegar fuera o abrir una página intersticial separada.
    var form = document.getElementById('form-crear-plantilla');
    if (form) {
        form.addEventListener('submit', function(ev){
            ev.preventDefault();
            var submitBtn = form.querySelector('button[type=submit]');
            if (submitBtn) submitBtn.disabled = true;
            var fd = new FormData(form);
            // ensure server sees the crear_plantilla key
            if (!fd.has('crear_plantilla')) fd.append('crear_plantilla','1');

            fetch(window.location.href, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(resp){
                    if (!resp.ok) throw new Error('HTTP '+resp.status);
                    return resp.json();
                })
                .then(function(json){
                    // use Notice to show success banner and redirect
                    if (window.Notice && typeof window.Notice.show === 'function') {
                        var link = json.redirect ? (' <a href="'+json.redirect+'">Ir ahora</a>') : '';
                        window.Notice.show('success','<strong>Plantilla creada.</strong> Redirigiendo al editor...'+link, 3000);
                    } else {
                        // fallback to inline banner
                        var banner = document.getElementById('create-banner');
                        if (!banner) {
                            banner = document.createElement('div');
                            banner.id = 'create-banner';
                            banner.style.margin = '12px 0';
                            banner.style.padding = '12px';
                            banner.style.borderRadius = '8px';
                            banner.style.background = '#f8f9fa';
                            banner.style.boxShadow = '0 1px 2px rgba(0,0,0,0.04)';
                            form.parentNode.insertBefore(banner, form.nextSibling);
                        }
                        banner.innerHTML = '<strong>Plantilla creada.</strong> Redirigiendo al editor... <span style="margin-left:8px"><a id="create-banner-link" href="'+(json.redirect||'#')+'">Ir ahora</a></span>';
                    }
                    setTimeout(function(){ window.location.href = json.redirect || window.location.href; }, 900);
                })
                .catch(function(err){
                    console.error('Error creando plantilla:', err);
                    var banner = document.getElementById('create-banner');
                    if (!banner) { banner = document.createElement('div'); banner.id='create-banner'; form.parentNode.insertBefore(banner, form.nextSibling); }
                    banner.style.background = '#fff5f5'; banner.style.border = '1px solid #f5c6cb'; banner.style.color = '#721c24';
                    banner.innerText = 'Error al crear la plantilla. Revisa la consola para más detalles.';
                })
                .finally(function(){ if (submitBtn) submitBtn.disabled = false; });
        });
    }
    })();
</script>
<script src="<?php echo BASE_URL; ?>/src/js/components/chart-widget.js"></script>

<?php if (!$isIncluded): ?>
</body>
</html>
<?php endif; ?>
