<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
require __DIR__ . '/../funciones/encryption.php';
start_secure_session();

if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$username = $_SESSION['username'];
$idPlantilla = $_GET['id'];

// Obtener email del usuario actual
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

// Obtener la plantilla de la base de datos (propia o compartida)
// Primero intentar obtener una plantilla propia
$stmt = $conn->prepare("SELECT *, 1 as es_propia, 'propietario' as rol FROM plantillas WHERE id = ? AND username = ? AND deleted_at IS NULL");
$stmt->bind_param("is", $idPlantilla, $username);
$stmt->execute();
$result = $stmt->get_result();
$plantilla = $result->fetch_assoc();
$stmt->close();

// Si no es propia, intentar obtener una plantilla compartida conmigo (usando email correcto)
if (!$plantilla) {
    $stmt = $conn->prepare("
        SELECT p.*, 0 as es_propia, COALESCE(pc.rol, 'lector') as rol 
        FROM plantillas p
        INNER JOIN plantillas_compartidas pc ON p.id = pc.id_plantilla
        WHERE p.id = ? AND pc.email = ? AND p.deleted_at IS NULL
    ");
    if (!$stmt) {
        // Fallback si la columna rol no existe aún
        $stmt = $conn->prepare("
            SELECT p.*, 0 as es_propia, 'lector' as rol 
            FROM plantillas p
            INNER JOIN plantillas_compartidas pc ON p.id = pc.id_plantilla
            WHERE p.id = ? AND pc.email = ? AND p.deleted_at IS NULL
        ");
    }
    $stmt->bind_param("is", $idPlantilla, $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $plantilla = $result->fetch_assoc();
    $stmt->close();
}

// Si aún no encontramos la plantilla, mostrar error de acceso
if (!$plantilla) {
    http_response_code(403);
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Acceso Denegado</title>
        <link rel="shortcut icon" href="' . BASE_URL . '/src/img/favicon.png" type="image/png">
        <link rel="stylesheet" href="' . BASE_URL . '/src/css/styles.css">
        <style>
            body { display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto; }
            .error-box { text-align: center; padding: 40px; border-radius: 12px; background: #fff5f5; border: 1px solid #f5c6cb; max-width: 480px; }
            .error-icon { font-size: 48px; margin-bottom: 16px; }
            h1 { color: #721c24; margin: 0 0 12px; font-size: 24px; }
            p { color: #666; margin: 8px 0; line-height: 1.6; }
            .error-details { background: white; padding: 12px; border-radius: 8px; margin: 16px 0; font-size: 0.9rem; color: #999; font-family: monospace; }
            a { display: inline-block; margin-top: 16px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 6px; }
            a:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <div class="error-icon">🔐</div>
            <h1>Acceso Denegado</h1>
            <p>No tienes permiso para acceder a esta plantilla.</p>
            <div class="error-details">
                Plantilla ID: ' . htmlspecialchars($idPlantilla) . '<br>
                Tu email: ' . htmlspecialchars($userEmail ?? 'No disponible') . '
            </div>
            <p style="font-size: 0.9rem; color: #999;">Si crees que debería tener acceso, contacta al propietario de la plantilla.</p>
            <a href="' . BASE_URL . '/pags/micuenta.php?section=dashboard">Volver al Dashboard</a>
        </div>
    </body>
    </html>';
    exit;
}

// Decode stored contenido JSON into arrays for rendering
$contenido = [];
if (!empty($plantilla['contenido'])) {
    try {
        // 🔐 DESENCRIPTAR contenido
        $contenido_desencriptado = decrypt_content($plantilla['contenido']);
        $decoded = json_decode($contenido_desencriptado, true);
        if (is_array($decoded)) $contenido = $decoded;
    } catch (Exception $e) {
        echo "Error al desencriptar plantilla: " . htmlspecialchars($e->getMessage());
        exit;
    }
}
$trabajoRows = $contenido['trabajo'] ?? [];
$gastosVariablesRows = $contenido['gastos_variables'] ?? [];
$gastosFijosRows = $contenido['gastos_fijos'] ?? [];
$descripcion = $contenido['descripcion'] ?? '';
$comunidad = $contenido['comunidad'] ?? 'Madrid';
// Persistir opción de usar neto (-15%) si existe en el contenido guardado
$usarNeto = !empty($contenido['usar_neto']) ? true : false;
 
// DEFINIR PERMISOS AQUÍ, ANTES DEL HTML
$isReadOnly = !$plantilla['es_propia'] && $plantilla['rol'] === 'lector';
$canEdit = $plantilla['es_propia'] || in_array($plantilla['rol'], ['editor', 'admin']);
$canShare = $plantilla['es_propia'] || ($plantilla['rol'] === 'admin');
$canDeleteShares = $plantilla['es_propia'] || ($plantilla['rol'] === 'admin');

// Obtener estudios anteriores (SOLO de plantillas propias del usuario actual)
$estudiosAnteriores = [];
if ($canEdit) {
    $stmtStudios = $conn->prepare("
        SELECT DISTINCT contenido
        FROM plantillas 
        WHERE username = ? AND deleted_at IS NULL AND contenido IS NOT NULL AND contenido != ''
        LIMIT 50
    ");
    if ($stmtStudios) {
        $stmtStudios->bind_param('s', $username);
        $stmtStudios->execute();
        $resultStudios = $stmtStudios->get_result();
        while ($row = $resultStudios->fetch_assoc()) {
            if (!empty($row['contenido'])) {
                try {
                    $contenido_desencriptado = decrypt_content($row['contenido']);
                    $decoded = json_decode($contenido_desencriptado, true);
                    if (is_array($decoded) && !empty($decoded['trabajo'])) {
                        foreach ($decoded['trabajo'] as $trabajo) {
                            if (!empty($trabajo['estudio']) && !in_array($trabajo['estudio'], $estudiosAnteriores)) {
                                $estudiosAnteriores[] = $trabajo['estudio'];
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Ignorar errores de desencriptación
                    continue;
                }
            }
        }
        $stmtStudios->close();
        sort($estudiosAnteriores);
    }
}

// topnav will be included later in the template (avoid duplicate include here)

// Calcular el total de ingresos del trabajo
$totalIngresos = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo_trabajo'])) {
    $tiposTrabajo = $_POST['tipo_trabajo'];
    foreach ($tiposTrabajo as $tipo) {
        switch ($tipo) {
            case 'Cine':
                $totalIngresos += 6.80; // Actores Cine Take
                break;
            case 'Serie':
                $totalIngresos += 5.11; // Actores Vídeo take
                break;
            case 'Prueba':
                $totalIngresos += 59.78; // Actores Convocatoria de pruebas para cualquier producto
                break;
            case 'Spot':
                // Asignar el valor del tipo de trabajo para Spot (si existe)
                break;
            case 'Publicidad':
                // Asignar el valor del tipo de trabajo para Publicidad (si existe)
                break;
            case 'Direccion Cine':
                $totalIngresos += 10.09; // Adaptación de Cine. 1 minuto o fracción
                break;
            case 'Direccion Serie':
                $totalIngresos += 5.85; // Adaptación de Vídeo. 1 minuto o fracción
                break;
            case 'Personalizado':
                // Asignar el valor del tipo de trabajo personalizado (si existe)
                break;
            default:
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ver Plantilla - <?php echo htmlspecialchars($plantilla['nombre']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/style-table.css">
    <!-- estilos movidos a: /src/css/style-table.css (se han añadido las reglas específicas de miplantilla) -->
    <meta name="base-url" content="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="user-role" content="<?php echo htmlspecialchars($plantilla['rol'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="can-share" content="<?php echo $canShare ? 'true' : 'false'; ?>">
    <meta name="can-delete-shares" content="<?php echo $canDeleteShares ? 'true' : 'false'; ?>">
    <meta name="is-owner" content="<?php echo $plantilla['es_propia'] ? 'true' : 'false'; ?>">
    <style>
        .estudio-autocomplete-container {
            position: relative;
            width: 100%;
        }
        .estudio-autocomplete-dropdown {
            position: fixed;
            background: white;
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10000;
            display: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 0 0 4px 4px;
            min-width: 200px;
        }
        .estudio-autocomplete-dropdown.visible {
            display: block;
        }
        .estudio-autocomplete-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        .estudio-autocomplete-item:hover {
            background: #f0f0f0;
        }
        .estudio-autocomplete-item.selected {
            background: #e3f2fd;
        }
        .estudio-autocomplete-item:last-child {
            border-bottom: none;
        }
    </style>>
</head>
<body>
<?php include __DIR__ . '/../src/nav/topnav.php'; ?>

<?php if ($isReadOnly): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Deshabilitar todos los inputs, selects, textareas y buttons cuando es modo lectura
    const inputs = document.querySelectorAll('input:not([readonly]), select, textarea, button[type="button"][class*="add"], button[class*="eliminar"]');
    inputs.forEach(el => {
        if (el.getAttribute('readonly') !== 'readonly') {
            el.disabled = true;
            el.style.opacity = '0.6';
            el.style.cursor = 'not-allowed';
        }
    });
});
</script>
<?php endif; ?>

    <div class="container center">
        <h1><?php echo htmlspecialchars($plantilla['nombre']); ?></h1>
        <!-- Export button (CSV / XML) + Help button -->
        <div style="display:flex;gap:12px;align-items:center;margin-bottom:10px;">
            <div class="export-dropdown" style="position:relative;">
                <button id="export-btn" class="button-submit" type="button">Exportar</button>
                <div id="export-menu" class="export-menu" style="display: block;position: absolute;left: 0%;top: 100%;background: rgb(255, 255, 255);border: 1px solid var(--border);box-shadow: rgba(0, 0, 0, 0.08) 0px 6px 18px;border-radius: 6px;z-index: 40;">
                    <button class="export-item" data-format="csv" style="display:block;padding:8px 14px;border:none;background:transparent;width:100%;text-align:left;cursor:pointer">Exportar a Excel (CSV)</button>
                    <button class="export-item" data-format="xml" style="display:block;padding:8px 14px;border:none;background:transparent;width:100%;text-align:left;cursor:pointer">Exportar a XML</button>
                </div>
            </div>
            <div class="muted">Exporta la plantilla actual</div>
            <button id="help-btn" class="button-submit" type="button" title="Ver atajos de teclado disponibles" style="width:36px;height:36px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#0b69ff;color:#fff;border:none;cursor:pointer;font-weight:bold;font-size:18px;">?</button>
        </div>

    <form id="form-guardar-plantilla" method="post" action="<?php echo BASE_URL; ?>/dashboard/guardar_plantilla.php">
            <?php echo csrf_input(); ?>

            <?php 
            $roleLabel = $plantilla['es_propia'] ? 'Propietario' : ucfirst($plantilla['rol']);
            $disabledAttr = $isReadOnly ? 'disabled' : '';
            
            // Colores y mensajes según rol
            $roleColor = [
                'propietario' => '#0c5460',    // azul oscuro
                'admin' => '#28a745',          // verde
                'editor' => '#004085',         // azul
                'lector' => '#856404'          // naranja
            ][$plantilla['es_propia'] ? 'propietario' : strtolower($plantilla['rol'])] ?? '#666';
            $roleBg = [
                'propietario' => '#d1ecf1',    // azul claro
                'admin' => '#d4edda',          // verde claro
                'editor' => '#cce5ff',         // azul claro
                'lector' => '#fff3cd'          // amarillo claro
            ][$plantilla['es_propia'] ? 'propietario' : strtolower($plantilla['rol'])] ?? '#f0f0f0';
            ?>

            <?php if ($plantilla['es_propia']): ?>
                <!-- PROPIETARIO: Plantilla propia -->
                <div style="margin:12px 0;padding:12px;border-radius:6px;background:<?php echo $roleBg; ?>;border:1px solid <?php echo $roleColor; ?>;color:<?php echo $roleColor; ?>;">
                    <strong>✓ Plantilla Propia</strong><br>
                    Esta es tu plantilla. Tienes acceso completo para editar, compartir y eliminar accesos.
                </div>
            <?php elseif ($isReadOnly): ?>
                <!-- LECTOR: Solo lectura -->
                <div style="margin:12px 0;padding:12px;border-radius:6px;background:<?php echo $roleBg; ?>;border:1px solid <?php echo $roleColor; ?>;color:<?php echo $roleColor; ?>;">
                    <strong><svg class="icon-inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> Modo Solo Lectura</strong><br>
                    <strong><?php echo htmlspecialchars($plantilla['username']); ?></strong> compartió esta plantilla contigo como <strong>Lector</strong>. 
                    Puedes ver y revisar los datos, pero no puedes editar ni guardar cambios.
                </div>
            <?php elseif ($plantilla['rol'] === 'editor'): ?>
                <!-- EDITOR: Puede editar -->
                <div style="margin:12px 0;padding:12px;border-radius:6px;background:<?php echo $roleBg; ?>;border:1px solid <?php echo $roleColor; ?>;color:<?php echo $roleColor; ?>;">
                    <strong>✎ Modo Edición</strong><br>
                    <strong><?php echo htmlspecialchars($plantilla['username']); ?></strong> compartió esta plantilla contigo como <strong>Editor</strong>. 
                    Puedes editar los datos y guardar cambios, pero no puedes compartir ni eliminar accesos.
                </div>
            <?php elseif ($plantilla['rol'] === 'admin'): ?>
                <!-- ADMIN: Acceso completo -->
                <div style="margin:12px 0;padding:12px;border-radius:6px;background:<?php echo $roleBg; ?>;border:1px solid <?php echo $roleColor; ?>;color:<?php echo $roleColor; ?>;">
                    <strong>⚙️ Acceso Completo</strong><br>
                    <strong><?php echo htmlspecialchars($plantilla['username']); ?></strong> compartió esta plantilla contigo como <strong>Admin</strong>. 
                    Tienes acceso completo: puedes editar, compartir con otros, y eliminar accesos.
                </div>
            <?php endif; ?>

            <input type="hidden" name="id_plantilla" value="<?php echo $idPlantilla; ?>" <?php echo $disabledAttr; ?>>
            <!-- Token de seguridad: Rol del usuario para validación frontend y backend -->
            <input type="hidden" name="user_role_token" value="<?php echo htmlspecialchars($plantilla['es_propia'] ? 'propietario' : $plantilla['rol']); ?>">
            <input type="hidden" name="can_edit_token" value="<?php echo $canEdit ? '1' : '0'; ?>">

            <!-- Selección de Comunidad Autónoma (por defecto Madrid) -->
            <div style="margin:12px 0;">
                <label for="comunidad_plantilla">Comunidad Autónoma</label><br>
                <select name="comunidad_plantilla" id="comunidad_plantilla" style="width:100%; max-width:420px;" <?php echo $disabledAttr; ?>>
                    <?php
                    // Only keep communities that have price mappings (rates are defined client/server-side)
                    $comunidades = [
                        'Madrid', 'Comunidad Valenciana', 'Cataluña', 'Galicia'
                    ];
                    foreach ($comunidades as $c) {
                        $selC = ($c === $comunidad) ? 'selected' : '';
                        echo '<option value="'.htmlspecialchars($c, ENT_QUOTES).'" '.$selC.'>'.htmlspecialchars($c).'</option>';
                    }
                    ?>
                </select>
            </div>

            <!-- (Removed global plantilla fecha - per-row fecha is used instead) -->

        <!-- Opciones de cálculo -->
        <div style="display:flex;align-items:center;gap:12px;margin-top:6px;justify-content:center;flex-direction:column;">
            <label style="margin:0;">Calcular en neto (-15%)
                <input type="checkbox" id="usar_neto" name="usar_neto" <?php echo $usarNeto ? 'checked' : ''; ?> <?php echo $disabledAttr; ?>>
            </label>
            <span class="muted">Marca para aplicar -15% sobre el total calculado</span>
        </div>

        <!-- Tabla para el trabajo -->
        <h2>Trabajo</h2>
        <table id="tabla-trabajo">
            <thead>
                <tr>
                    <th>Estudio</th>
                    <th>Comentario</th>
                    <th>Tipo de Trabajo</th>
                    <th>Fecha</th>
                    <th>CGs</th>
                    <th>Takes</th>
                    <th>Total</th>
                    <th>Acciones</th>
                   
                </tr>
            </thead>
            <tbody>
                <!-- Filas dinámicas para agregar detalles del trabajo -->
                <?php if (!empty($trabajoRows) && is_array($trabajoRows)): ?>
                    <?php foreach ($trabajoRows as $r): ?>
                        <tr>
                            <td style="display:flex;gap:8px;align-items:center;">
                                <div class="estudio-autocomplete-container">
                                    <input type="text" class="estudio-input" name="estudio[]" value="<?php echo htmlspecialchars(strtoupper($r['estudio'] ?? ''), ENT_QUOTES); ?>" placeholder="Estudio de doblaje" style="flex:1;width:100%;text-transform:uppercase;" <?php echo $disabledAttr; ?>>
                                    <div class="estudio-autocomplete-dropdown"></div>
                                </div>
                                <!-- (Removed per-row preset select: community selection now controls CG/Take) -->
                            </td>
                            <td>
                                <!-- Comentario sobre el tipo de trabajo -->
                                <textarea name="comentario_tipo[]" placeholder="Comentario sobre el tipo de trabajo"><?php echo htmlspecialchars($r['comentario_tipo'] ?? '', ENT_QUOTES); ?></textarea>
                            </td>
                            <td>
                                <select name="tipo_trabajo[]" required>
                                    <?php
                                    $opts = [
                                        'Cine' => 'Cine',
                                        'Serie' => 'Serie',
                                        'Prueba' => 'Prueba',
                                        'Spot' => 'Spot',
                                        'Publicidad' => 'Publicidad',
                                        'Direccion Cine' => 'Dirección Cine',
                                        'Direccion Serie' => 'Dirección Serie',
                                        'Personalizado' => 'Personalizado (Cantidad x Precio)'
                                    ];
                                    $sel = $r['tipo'] ?? '';
                                    foreach ($opts as $value => $label) {
                                        $s = ($value === $sel) ? 'selected' : '';
                                        echo "<option value=\"".htmlspecialchars($value, ENT_QUOTES) ."\" $s>".htmlspecialchars($label)."</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <input type="date" name="trabajo_fecha[]" value="<?php echo htmlspecialchars($r['fecha'] ?? '', ENT_QUOTES); ?>" style="width:100%; max-width:160px;">
                            </td>
                            <td><input type="number" name="cgs[]" value="<?php echo htmlspecialchars($r['cgs'] ?? '', ENT_QUOTES); ?>" placeholder="CGs" min="0" step="1"></td>
                            <td><input type="number" name="takes[]" value="<?php echo htmlspecialchars($r['takes'] ?? '', ENT_QUOTES); ?>" placeholder="Takes" min="0" step="1"></td>
                            <td>
                                <!-- Este campo será calculado automáticamente -->
                                <input type="text" name="total[]" readonly>
                            </td>
                            <td><button type="button" class="btn-delete-row">Eliminar</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                        <tr>
                            <td style="display:flex;gap:8px;align-items:center;">
                                <div class="estudio-autocomplete-container">
                                    <input type="text" class="estudio-input" name="estudio[]" placeholder="Estudio de doblaje" style="flex:1;width:100%;text-transform:uppercase;">
                                    <div class="estudio-autocomplete-dropdown"></div>
                                </div>
                                <!-- per-row preset removed; comunidad_plantilla controls CG/Take -->
                            </td>
                            <td>
                                <!-- Comentario sobre el tipo de trabajo (por defecto vacío) -->
                                <textarea name="comentario_tipo[]" placeholder="Comentario sobre el tipo de trabajo"></textarea>
                            </td>
                            <td>
                                <select name="tipo_trabajo[]" required>
                                    <option value="Cine">Cine</option>
                                    <option value="Serie">Serie</option>
                                    <option value="Prueba">Prueba</option>
                                    <option value="Spot">Spot</option>
                                    <option value="Publicidad">Publicidad</option>
                                    <option value="Direccion Cine">Dirección Cine</option>
                                    <option value="Direccion Serie">Dirección Serie</option>
                                    <option value="Personalizado">Personalizado (Cantidad x Precio)</option>
                                </select>
                            </td>
                            <td>
                                <input type="date" name="trabajo_fecha[]" value="" style="width:100%; max-width:160px;">
                            </td>
                            <td><input type="number" name="cgs[]" placeholder="CGs" min="0" step="1"></td>
                            <td><input type="number" name="takes[]" placeholder="Takes" min="0" step="1"></td>
                            <td>
                                <!-- Este campo será calculado automáticamente -->
                                <input type="text" name="total[]" readonly>
                            </td>
                            <td><button type="button" class="btn-delete-row">Eliminar</button></td>
                        </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Botón para agregar fila -->
         <br>
                <button class="button-submit" type="button" id="btn-agregar-trabajo">Agregar Fila</button>

<!-- Tabla para gastos variables -->
<h2>Gastos Variables</h2>
<table id="tabla-gastos-variables">
    <thead>
        <tr>
            <th>Tipo de Gasto</th>
            <th>Descripción</th>
            <th>Monto</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <!-- Filas dinámicas para agregar detalles de los gastos variables -->
        <?php if (!empty($gastosVariablesRows) && is_array($gastosVariablesRows)): ?>
            <?php foreach ($gastosVariablesRows as $gv): ?>
                <tr>
                    <td>
                        <select name="tipo_gasto_var[]">
                            <?php
                            $optsGV = ['Ocio','Necesidades','Otros'];
                            $sel = $gv['tipo'] ?? '';
                            foreach ($optsGV as $opt) {
                                $s = ($opt === $sel) ? 'selected' : '';
                                echo "<option value=\"".htmlspecialchars($opt, ENT_QUOTES)."\" $s>".htmlspecialchars($opt)."</option>";
                            }
                            ?>
                        </select>
                    </td>
                    <td><input type="text" name="descripcion_gasto_var[]" value="<?php echo htmlspecialchars($gv['descripcion'] ?? '', ENT_QUOTES); ?>" placeholder="Descripción"></td>
                    <td><input type="number" name="monto_gasto_var[]" value="<?php echo htmlspecialchars($gv['monto'] ?? '', ENT_QUOTES); ?>" placeholder="Monto"></td>
                    <td><button type="button" class="btn-delete-row">Eliminar</button></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td>
                    <select name="tipo_gasto_var[]">
                        <option value="Ocio">Ocio</option>
                        <option value="Necesidades">Necesidades</option>
                        <option value="Otros">Otros</option>
                    </select>
                </td>
                <td><input type="text" name="descripcion_gasto_var[]" placeholder="Descripción"></td>
                <td><input type="number" name="monto_gasto_var[]" placeholder="Monto"></td>
                <td><button type="button" class="btn-delete-row">Eliminar</button></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Botón para agregar fila de gasto variable -->
<br>
<button class="button-submit" type="button" id="btn-agregar-gasto-variable">Agregar Fila</button>

<!-- Tabla para gastos fijos -->
<h2>Gastos Fijos</h2>
        <!-- Reutilizar gastos fijos desde otra plantilla -->
        <div style="margin-bottom:8px;display:flex;gap:8px;align-items:center;">
            <button type="button" id="btn-reutilizar-gastos" class="button-submit">Reutilizar gastos fijos de plantilla...</button>
            <div class="muted">Copiar gastos fijos de otra plantilla a esta</div>
        </div>
        <div id="modal-reutilizar" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,0.4);z-index:200;">
            <div style="background:#fff;padding:16px;border-radius:8px;max-width:520px;width:92%;box-sizing:border-box;">
                <h3 style="margin-top:0">Reutilizar gastos fijos</h3>
                <p>Selecciona la plantilla desde la que quieres copiar los gastos fijos:</p>
                <div style="display:flex;gap:8px;align-items:center;">
                    <select id="select-plantilla-reuse" style="flex:1;">
                        <option value="">-- Selecciona plantilla --</option>
                        <?php
                        // load user's templates for selection (exclude current)
                        $optStmt = $conn->prepare('SELECT id,nombre FROM plantillas WHERE username = ? ORDER BY nombre ASC');
                        $optStmt->bind_param('s', $username);
                        $optStmt->execute();
                        $optRes = $optStmt->get_result();
                        while ($opt = $optRes->fetch_assoc()) {
                            if ((int)$opt['id'] === (int)$idPlantilla) continue;
                            echo '<option value="'.htmlspecialchars($opt['id'], ENT_QUOTES).'">'.htmlspecialchars($opt['nombre']).'</option>';
                        }
                        $optStmt->close();
                        ?>
                    </select>
                    <button id="btn-copy-gastos" class="button-submit" type="button">Copiar</button>
                    <button id="btn-cancel-reuse" type="button">Cancelar</button>
                </div>
                <div id="reuse-status" style="margin-top:8px;color:#666;font-size:0.95rem;"></div>
            </div>
        </div>
<table id="tabla-gastos-fijos">
    <thead>
        <tr>
            <th>Tipo de Gasto</th>
            <th>Descripción</th>
            <th>Monto</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <!-- Filas dinámicas para agregar detalles de los gastos fijos -->
        <?php if (!empty($gastosFijosRows) && is_array($gastosFijosRows)): ?>
            <?php foreach ($gastosFijosRows as $gf): ?>
                <tr>
                    <td>
                        <select name="tipo_gasto_fijo[]">
                            <?php
                            $optsGF = ['Casa','Hipoteca','Alquiler','Coche','Luz','Agua','Otros'];
                            $sel = $gf['tipo'] ?? '';
                            foreach ($optsGF as $opt) {
                                $s = ($opt === $sel) ? 'selected' : '';
                                echo "<option value=\"".htmlspecialchars($opt, ENT_QUOTES)."\" $s>".htmlspecialchars($opt)."</option>";
                            }
                            ?>
                        </select>
                    </td>
                    <td><input type="text" name="descripcion_gasto_fijo[]" value="<?php echo htmlspecialchars($gf['descripcion'] ?? '', ENT_QUOTES); ?>" placeholder="Descripción"></td>
                    <td><input type="number" name="monto_gasto_fijo[]" value="<?php echo htmlspecialchars($gf['monto'] ?? '', ENT_QUOTES); ?>" placeholder="Monto"></td>
                    <td><button type="button" class="btn-delete-row">Eliminar</button></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td>
                    <select name="tipo_gasto_fijo[]">
                        <option value="Casa">Casa</option>
                        <option value="Hipoteca">Hipoteca</option>
                        <option value="Alquiler">Alquiler</option>
                        <option value="Coche">Coche</option>
                        <option value="Luz">Luz</option>
                        <option value="Agua">Agua</option>
                        <option value="Otros">Otros</option>
                    </select>
                </td>
                <td><input type="text" name="descripcion_gasto_fijo[]" placeholder="Descripción"></td>
                <td><input type="number" name="monto_gasto_fijo[]" placeholder="Monto"></td>
                <td><button type="button" class="btn-delete-row">Eliminar</button></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Botón para agregar fila de gasto fijo -->
<br>
<button class="button-submit" type="button" id="btn-agregar-gasto-fijo">Agregar Fila</button>

<!-- Casilla para el total del mes -->
<div id="total-mes">
    <h2>Total Mes</h2>
    <p id="total-mes-valor">0.00 €</p>
</div>


        <!-- Botón de guardar y compartir -->
            <div style="margin-top:18px; text-align:center;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;align-items:center;">
                <?php if ($canEdit): ?>
                <button type="submit" name="guardar_plantilla" class="button-submit" style="padding:10px 20px;background:#0b69ff;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:500;font-size:0.95rem;transition:background 0.2s;">
                    💾 Guardar plantilla
                </button>
                <?php elseif ($isReadOnly): ?>
                <button type="submit" name="guardar_plantilla" class="button-submit" style="padding:10px 20px;background:#ccc;color:#666;border:none;border-radius:6px;cursor:not-allowed;font-weight:500;font-size:0.95rem;" disabled>
                    <svg class="icon-inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> Solo lectura (No puedes editar)
                </button>
                <?php endif; ?>
                
                <?php if ($canShare): ?>
                <button type="button" class="share-btn" data-plantilla-id="<?php echo $idPlantilla; ?>" style="padding:10px 20px;background:#28a745;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:500;font-size:0.95rem;transition:background 0.2s;" title="Compartir plantilla con otros usuarios">
                    <svg class="icon-inline" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2v11m-7-2l7-7 7 7M2 20h20v2H2z"/></svg> Compartir
                </button>
                <?php endif; ?>
                
                <span id="save-status" class="save-status" aria-live="polite" style="display:inline-flex;align-items:center;margin-left:12px;font-size:0.9rem;color:#666;">
                    <span class="status-text">Listo</span>
                </span>
            </div>

        </form>
    </div>
    <br>
    <?php include __DIR__ . '/../src/nav/footer.php'; ?>

    <!-- Componentes modales -->
    <?php include __DIR__ . '/../src/components/notice.php'; ?>
    <?php include __DIR__ . '/../src/components/share-modal.php'; ?>
    <?php include __DIR__ . '/../src/components/confirm-modal.php'; ?>
    
    <!-- Modal de Ayuda - Atajos de teclado -->
    <div id="help-modal" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);z-index:300;">
        <div style="background:#fff;padding:24px;border-radius:12px;max-width:600px;width:92%;box-sizing:border-box;max-height:80vh;overflow-y:auto;box-shadow:0 10px 40px rgba(0,0,0,0.2);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h2 style="margin:0;font-size:22px;">⌨️ Atajos de Teclado</h2>
                <button id="help-close" type="button" style="border:none;background:transparent;font-size:24px;cursor:pointer;color:#999;">&times;</button>
            </div>
            
            <div style="border-top:1px solid #eee;padding-top:16px;">
                <h3 style="margin-top:0;color:#0b69ff;font-size:16px;">Operaciones Principales</h3>
                <div style="display:grid;gap:12px;">
                    <div style="display:flex;gap:16px;padding:10px;background:#f9f9f9;border-radius:6px;">
                        <kbd style="background:#0b69ff;color:#fff;padding:4px 8px;border-radius:4px;font-family:monospace;white-space:nowrap;font-weight:bold;">Ctrl+S</kbd>
                        <span>Guardar plantilla</span>
                    </div>
                    <div style="display:flex;gap:16px;padding:10px;background:#f9f9f9;border-radius:6px;">
                        <kbd style="background:#0b69ff;color:#fff;padding:4px 8px;border-radius:4px;font-family:monospace;white-space:nowrap;font-weight:bold;">Ctrl+N</kbd>
                        <span>Agregar fila de Trabajo</span>
                    </div>
                    <div style="display:flex;gap:16px;padding:10px;background:#f9f9f9;border-radius:6px;">
                        <kbd style="background:#0b69ff;color:#fff;padding:4px 8px;border-radius:4px;font-family:monospace;white-space:nowrap;font-weight:bold;">Ctrl+Shift+P</kbd>
                        <span>Compartir plantilla</span>
                    </div>
                    <div style="display:flex;gap:16px;padding:10px;background:#f9f9f9;border-radius:6px;">
                        <kbd style="background:#0b69ff;color:#fff;padding:4px 8px;border-radius:4px;font-family:monospace;white-space:nowrap;font-weight:bold;">Ctrl+Shift+E</kbd>
                        <span>Exportar plantilla (CSV)</span>
                    </div>
                </div>
            </div>
            
            <div style="border-top:1px solid #eee;padding-top:16px;margin-top:16px;">
                <h3 style="margin-top:0;color:#0b69ff;font-size:16px;">Gestión de Filas</h3>
                <div style="display:grid;gap:12px;">
                    <div style="display:flex;gap:16px;padding:10px;background:#f9f9f9;border-radius:6px;">
                        <kbd style="background:#28a745;color:#fff;padding:4px 8px;border-radius:4px;font-family:monospace;white-space:nowrap;font-weight:bold;">Ctrl+Alt+V</kbd>
                        <span>Agregar fila de Gasto Variable</span>
                    </div>
                    <div style="display:flex;gap:16px;padding:10px;background:#f9f9f9;border-radius:6px;">
                        <kbd style="background:#28a745;color:#fff;padding:4px 8px;border-radius:4px;font-family:monospace;white-space:nowrap;font-weight:bold;">Ctrl+Alt+F</kbd>
                        <span>Agregar fila de Gasto Fijo</span>
                    </div>
                </div>
            </div>
            
            <div style="border-top:1px solid #eee;padding-top:16px;margin-top:16px;">
                <p style="margin:0;font-size:0.9rem;color:#666;">💡 <strong>Consejo:</strong> Los atajos funcionan incluso cuando estás editando campos. Presiona <kbd style="background:#eee;padding:2px 6px;border-radius:3px;font-family:monospace;">?</kbd> en cualquier momento para ver esta ayuda nuevamente.</p>
            </div>
        </div>
    </div>

    <!-- Script JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/src/js/components/share-modal.js"></script>
    <script src="<?php echo BASE_URL; ?>/src/js/components/confirm-modal.js"></script>
    <script src="<?php echo BASE_URL; ?>/src/js/components/notice.js"></script>
    <script>
    (function(){
        const baseUrl = <?php echo json_encode(rtrim(BASE_URL, '/')); ?>;
        const isReadOnly = <?php echo json_encode($isReadOnly); ?>;
        const canEdit = <?php echo json_encode($canEdit); ?>;
        
        // Control de permisos de edición
        // SOLO deshabilitar campos si es LECTOR (isReadOnly=true)
        // Editor y Admin deben poder editar normalmente
        if (isReadOnly) {
            // LECTOR: Deshabilitar TODOS los campos
            const form = document.getElementById('form-guardar-plantilla');
            if (form) {
                const elements = form.querySelectorAll('input, select, textarea, button[type="button"][id*="btn-agregar"], button[type="button"][id*="btn-delete"], button[class="btn-delete-row"]');
                elements.forEach(function(el) {
                    if (el.type === 'hidden') return; // No deshabilitar inputs ocultos
                    el.disabled = true;
                    el.style.opacity = '0.5';
                    el.style.cursor = 'not-allowed';
                });
            }
        }
        
        const btn = document.getElementById('btn-reutilizar-gastos');
        const modal = document.getElementById('modal-reutilizar');
        const cancel = document.getElementById('btn-cancel-reuse');
        const copyBtn = document.getElementById('btn-copy-gastos');
        const select = document.getElementById('select-plantilla-reuse');
        const status = document.getElementById('reuse-status');

        if (btn && modal) {
            btn.addEventListener('click', function(){ modal.style.display = 'flex'; status.textContent=''; });
            cancel && cancel.addEventListener('click', function(){ modal.style.display='none'; });
            modal.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ e.preventDefault(); modal.style.display='none'; } });

            copyBtn && copyBtn.addEventListener('click', async function(){
                const otherId = select.value;
                if (!otherId) { status.textContent = 'Selecciona una plantilla válida.'; return; }
                status.textContent = 'Cargando...';
                try {
                    const resp = await fetch(baseUrl + '/dashboard/get_plantilla_json.php?id=' + encodeURIComponent(otherId), { credentials: 'same-origin' });
                    if (!resp.ok) throw new Error('Error al obtener plantilla');
                    const data = await resp.json();
                    if (!data.success) throw new Error(data.message || 'Error en respuesta');
                    const contenido = data.contenido || {};
                    const gastosFijos = Array.isArray(contenido.gastos_fijos) ? contenido.gastos_fijos : [];
                    if (gastosFijos.length === 0) { status.textContent = 'La plantilla seleccionada no tiene gastos fijos.'; return; }

                    // Append each gasto fijo to the gastos fijos table
                    const tbody = document.querySelector('#tabla-gastos-fijos tbody');
                    gastosFijos.forEach(function(g){
                        const tr = document.createElement('tr');
                        const tipo = document.createElement('td');
                        tipo.innerHTML = '<select name="tipo_gasto_fijo[]"><option>'+ (g.tipo ? escapeHtml(g.tipo) : 'Otros') +'</option></select>';
                        const desc = document.createElement('td');
                        desc.innerHTML = '<input type="text" name="descripcion_gasto_fijo[]" value="'+ escapeAttr(g.descripcion || '') +'">';
                        const monto = document.createElement('td');
                        monto.innerHTML = '<input type="number" name="monto_gasto_fijo[]" value="'+ escapeAttr(g.monto || '') +'">';
                        const acciones = document.createElement('td');
                        acciones.innerHTML = '<button type="button" class="btn-delete-row">Eliminar</button>';
                        tr.appendChild(tipo); tr.appendChild(desc); tr.appendChild(monto); tr.appendChild(acciones);
                        tbody.appendChild(tr);
                    });

                    // mark form dirty so autosave triggers
                    const form = document.getElementById('form-guardar-plantilla'); if (form){ form.dispatchEvent(new Event('input', {bubbles:true})); }
                    status.textContent = 'Gastos fijos copiados correctamente.';
                    setTimeout(function(){ modal.style.display='none'; }, 800);
                } catch (err) {
                    console.error(err);
                    status.textContent = 'Error: ' + (err.message || 'no se pudo copiar');
                }
            });
        }

        function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
        function escapeAttr(s){ return escapeHtml(s).replace(/"/g,'&quot;'); }
    })();
    </script>
    <script>
$(document).ready(function() {
    // Función para calcular el total al cambiar los valores de CGs o Takes
    $('#tabla-trabajo tbody').on('input', 'input[name="cgs[]"], input[name="takes[]"]', function() {
        var $input = $(this);
        // coerce to integer and non-negative
        var val = $input.val();
        if (val === '') { // allow empty while editing
            var fila = $input.closest('tr'); calcularTotal(fila); calcularTotalMes(); return;
        }
        var n = parseInt(val, 10);
        if (isNaN(n) || n < 0) n = 0;
        // if decimal provided, floor it
        if (String(n) !== String(val).trim()) {
            $input.val(n);
        }
        var fila = $input.closest('tr');
        calcularTotal(fila);
        calcularTotalMes();
    });

    // Recalcular cuando cambie el tipo de trabajo
    $('#tabla-trabajo tbody').on('change', 'select[name="tipo_trabajo[]"]', function() {
        var fila = $(this).closest('tr');
        calcularTotal(fila);
        calcularTotalMes();
    });

    // Función para calcular el total del mes al cambiar los montos de gastos variables
    $('#tabla-gastos-variables tbody').on('input', 'input[name="monto_gasto_var[]"]', function() {
        calcularTotalMes();
    });

    // Función para calcular el total del mes al cambiar los montos de gastos fijos
    $('#tabla-gastos-fijos tbody').on('input', 'input[name="monto_gasto_fijo[]"]', function() {
        calcularTotalMes();
    });

    // Botón para agregar fila (Trabajo)
    $('#btn-agregar-trabajo').click(function() {
        agregarFila();
    });

    // Función para agregar fila
    function agregarFila() {
        // Clonar la última fila de la tabla y agregarla al final
        var nuevaFila = $('#tabla-trabajo tbody tr:last').clone();

    // Limpiar los campos de la nueva fila and reset selects/date
    nuevaFila.find('input[type="text"], input[type="number"], input[type="date"]').val('');
        nuevaFila.find('input[name="total[]"]').val('');
        // reset selects: both tipo_trabajo[] and descripcion_trabajo[]
        nuevaFila.find('select').each(function(){
            // if it's the descripcion select, try to set to first preset
            $(this).prop('selectedIndex', 0);
        });

        // Agregar la nueva fila a la tabla
        $('#tabla-trabajo tbody').append(nuevaFila);
        // Do not overwrite user inputs; just calculate totals using current community unit rates
        calcularTotal(nuevaFila);
        calcularTotalMes();
        var form = document.getElementById('form-guardar-plantilla'); if (form){ form.dispatchEvent(new Event('input',{bubbles:true})); }
    }

    // Botón para agregar fila de gasto variable
    $('#btn-agregar-gasto-variable').click(function() {
        agregarFilaGastoVariable();
    });

    // Función para agregar fila de gasto variable
    function agregarFilaGastoVariable() {
        // Clonar la última fila de la tabla y agregarla al final
        var nuevaFila = $('#tabla-gastos-variables tbody tr:last').clone();

        // Limpiar los campos de la nueva fila y resetear selects
        nuevaFila.find('input[type="text"], input[type="number"]').val('');
        nuevaFila.find('select').prop('selectedIndex', 0);

        // Agregar la nueva fila a la tabla
        $('#tabla-gastos-variables tbody').append(nuevaFila);
    }

    // Botón para agregar fila de gasto fijo
    $('#btn-agregar-gasto-fijo').click(function() {
        agregarFilaGastoFijo();
    });

    // Función para agregar fila de gasto fijo
    function agregarFilaGastoFijo() {
        // Clonar la última fila de la tabla y agregarla al final
        var nuevaFila = $('#tabla-gastos-fijos tbody tr:last').clone();

        // Limpiar los campos de la nueva fila y resetear selects
        nuevaFila.find('input[type="text"], input[type="number"]').val('');
        nuevaFila.find('select').prop('selectedIndex', 0);

        // Agregar la nueva fila a la tabla
        $('#tabla-gastos-fijos tbody').append(nuevaFila);
    }

    // Delegated handler: eliminar fila (Trabajo)
    $('#tabla-trabajo').on('click', '.btn-delete-row', function() {
        var rows = $('#tabla-trabajo tbody tr');
        if (rows.length > 1) {
            $(this).closest('tr').remove();
        } else {
            // Si solo queda una fila, limpiar los campos en lugar de eliminar
            var row = $(this).closest('tr');
            row.find('input[type="text"], input[type="number"], input[type="date"]').val('');
            row.find('select').prop('selectedIndex', 0);
            row.find('input[name="total[]"]').val('');
        }
        calcularTotalMes();
    });

    // Mapping of community -> rates for Serie/Cine
    const communityRates = {
        'Madrid': { 'Serie': {cg:49.31, take:5.41}, 'Cine': {cg:65.76, take:7.20} },
        'Comunidad Valenciana': { 'Serie': {cg:25.75, take:2.78}, 'Cine': {cg:29.61, take:3.20} },
        'Cataluña': { 'Serie': {cg:39.74, take:4.36}, 'Cine': {cg:54.40, take:6.04} },
        'Galicia': { 'Serie': {cg:35.93, take:3.01}, 'Cine': {cg:35.93, take:3.01} }
    };

    // Keep user-entered cgs/takes intact. Totals are computed as: user_count * community_unit_rate
    function applyCommunityRatesToRow($row){
        // no-op: we no longer overwrite user inputs
        return;
    }

    // When the global community changes, recalculate totals for all rows (preserving inputs)
    $('#comunidad_plantilla').on('change', function(){
        $('#tabla-trabajo tbody tr').each(function(){
            calcularTotal($(this));
        });
        calcularTotalMes();
        // mark form dirty for autosave
        var form = document.getElementById('form-guardar-plantilla'); if (form){ form.dispatchEvent(new Event('input',{bubbles:true})); }
    });

    // When a row's tipo changes, recalculate totals for that row using community unit rates
    $('#tabla-trabajo tbody').on('change', 'select[name="tipo_trabajo[]"]', function(){
        var $row = $(this).closest('tr');
        var tipoTrabajo = $(this).val();
        
        // Actualizar placeholders según el tipo
        if (tipoTrabajo === 'Personalizado') {
            $row.find('input[name="cgs[]"]').attr('placeholder', 'Cantidad').attr('title', 'Cantidad de veces que se repite');
            $row.find('input[name="takes[]"]').attr('placeholder', 'Precio unitario').attr('title', 'Precio por unidad (Cantidad × Precio = Total)');
        } else {
            $row.find('input[name="cgs[]"]').attr('placeholder', 'CGs').attr('title', 'Cantidad de CGs');
            $row.find('input[name="takes[]"]').attr('placeholder', 'Takes').attr('title', 'Cantidad de Takes');
        }
        
        // ensure cgs/takes are ints before calc
        $row.find('input[name="cgs[]"], input[name="takes[]"]').each(function(){
            var $i = $(this); var v = $i.val(); if (v === '') return; var ni = parseInt(v,10); if (isNaN(ni) || ni < 0) ni = 0; $i.val(ni);
        });
        calcularTotal($row);
        calcularTotalMes();
        var form = document.getElementById('form-guardar-plantilla'); if (form){ form.dispatchEvent(new Event('input',{bubbles:true})); }
    });

    // Delegated handler: eliminar fila (Gastos variables)
    $('#tabla-gastos-variables').on('click', '.btn-delete-row', function() {
        var rows = $('#tabla-gastos-variables tbody tr');
        if (rows.length > 1) {
            $(this).closest('tr').remove();
        } else {
            var row = $(this).closest('tr');
            row.find('input[type="text"], input[type="number"]').val('');
            row.find('select').prop('selectedIndex', 0);
        }
        calcularTotalMes();
    });

    // Delegated handler: eliminar fila (Gastos fijos)
    $('#tabla-gastos-fijos').on('click', '.btn-delete-row', function() {
        var rows = $('#tabla-gastos-fijos tbody tr');
        if (rows.length > 1) {
            $(this).closest('tr').remove();
        } else {
            var row = $(this).closest('tr');
            row.find('input[type="text"], input[type="number"]').val('');
            row.find('select').prop('selectedIndex', 0);
        }
        calcularTotalMes();
    });

    // Función para calcular el total
    function calcularTotal(fila) {
        var cgs = parseFloat(fila.find('input[name="cgs[]"]').val());
        var takes = parseFloat(fila.find('input[name="takes[]"]').val());
        var tipoTrabajo = fila.find('select[name="tipo_trabajo[]"]').val();
        var valorCgs = 0;
        var valorTakes = 0;

        // CASO ESPECIAL: Personalizado (CGs × Takes = Total)
        if (tipoTrabajo === "Personalizado") {
            var bruto = (isNaN(cgs) ? 0 : cgs) * (isNaN(takes) ? 0 : takes);
            var usarNeto = document.getElementById('usar_neto') && document.getElementById('usar_neto').checked;
            var total = usarNeto ? bruto * 0.85 : bruto;
            fila.find('input[name="total[]"]').val(total.toFixed(2));
            return;
        }

        // First, try to use community unit rates (unit price) when available.
        var comunidad = (document.getElementById('comunidad_plantilla')||{value:'Madrid'}).value;
        var commRates = communityRates[comunidad] || null;
        if (commRates && commRates[tipoTrabajo]) {
            valorCgs = commRates[tipoTrabajo].cg;
            valorTakes = commRates[tipoTrabajo].take;
        } else {
            // Fallback: use the legacy per-type constants when no community mapping exists
            switch (tipoTrabajo) {
                case "Cine":
                    valorCgs = 62.11; // Actores Cine CG
                    valorTakes = 6.80; // Actores Cine Take
                    break;
                case "Serie":
                    valorCgs = 46.57; // Actores Vídeo CG
                    valorTakes = 5.11; // Actores Vídeo take
                    break;
                case "Prueba":
                    valorCgs = 59.78; // Actores Convocatoria de pruebas para cualquier producto
                    valorTakes = 0; // No hay valor de Takes en las pruebas
                    break;
                case "Spot":
                    // Asignar los valores correspondientes para Spot (si existe)
                    break;
                case "Publicidad":
                    // Asignar los valores correspondientes para Publicidad (si existe)
                    break;
                case "Direccion Cine":
                    valorCgs = 10.09; // Adaptación de Cine. 1 minuto o fracción
                    valorTakes = 0; // No hay valor de Takes para la dirección de Cine
                    break;
                case "Direccion Serie":
                    valorCgs = 5.85; // Adaptación de Vídeo. 1 minuto o fracción
                    valorTakes = 0; // No hay valor de Takes para la dirección de Serie
                    break;
                default:
                    break;
            }
        }

        // Calcular el total bruto: user_count * unit_price
        var bruto = ( (isNaN(cgs) ? 0 : cgs) * valorCgs ) + ( (isNaN(takes) ? 0 : takes) * valorTakes );

        // Si el checkbox de "usar_neto" está marcado, aplicar -15%
        var usarNeto = document.getElementById('usar_neto') && document.getElementById('usar_neto').checked;
        var total = usarNeto ? bruto * 0.85 : bruto;

        // Asignar el total al campo correspondiente en la fila
        fila.find('input[name="total[]"]').val(total.toFixed(2));
    }

    // Función para calcular el total del mes
    function calcularTotalMes() {
        var totalMes = 0;

        // Sumar los totales de la tabla de trabajo (usamos los totales ya calculados en cada fila)
        $('#tabla-trabajo tbody tr').each(function() {
            var total = parseFloat($(this).find('input[name="total[]"]').val());
            if (!isNaN(total)) {
                totalMes += total;
            }
        });

        // Sumar los montos de los gastos variables
        $('#tabla-gastos-variables tbody tr').each(function() {
            var monto = parseFloat($(this).find('input[name="monto_gasto_var[]"]').val());
            if (!isNaN(monto)) {
                totalMes -= monto; // Los gastos se restan, por eso se usa el operador de resta
            }
        });

        // Sumar los montos de los gastos fijos
        $('#tabla-gastos-fijos tbody tr').each(function() {
            var monto = parseFloat($(this).find('input[name="monto_gasto_fijo[]"]').val());
            if (!isNaN(monto)) {
                totalMes -= monto; // Los gastos se restan, por eso se usa el operador de resta
            }
        });

        // Actualizar el valor en el HTML
        $('#total-mes-valor').text(totalMes.toFixed(2) + ' €');
    }

    // Cuando cambie el checkbox de usar_neto, recalcular todo
    $('#usar_neto').on('change', function(){
        $('#tabla-trabajo tbody tr').each(function(){ calcularTotal($(this)); });
        calcularTotalMes();
        // mark form dirty so autosave will trigger
        var form = document.getElementById('form-guardar-plantilla');
        if (form) {
            var ev = new Event('input', {bubbles:true}); form.dispatchEvent(ev);
        }
    });

    // Initial calculation for loaded rows: apply current community rates and calc totals
    $('#tabla-trabajo tbody tr').each(function() {
        var $row = $(this);
        var tipoTrabajo = $row.find('select[name="tipo_trabajo[]"]').val();
        
        // Actualizar placeholders según el tipo inicial
        if (tipoTrabajo === 'Personalizado') {
            $row.find('input[name="cgs[]"]').attr('placeholder', 'Cantidad').attr('title', 'Cantidad de veces que se repite');
            $row.find('input[name="takes[]"]').attr('placeholder', 'Precio unitario').attr('title', 'Precio por unidad (Cantidad × Precio = Total)');
        }
        
        applyCommunityRatesToRow($row);
        calcularTotal($row);
    });
    calcularTotalMes();
});


    </script>
    <script>
    (function(){
        // Export menu handlers and data builders
        const exportBtn = document.getElementById('export-btn');
        const exportMenu = document.getElementById('export-menu');

        if (exportBtn && exportMenu) {
            exportBtn.addEventListener('click', function(e){
                exportMenu.style.display = exportMenu.style.display === 'none' ? 'block' : 'none';
            });

            // hide menu when clicking outside
            document.addEventListener('click', function(e){
                if (!exportMenu.contains(e.target) && !exportBtn.contains(e.target)) {
                    exportMenu.style.display = 'none';
                }
            });

            document.querySelectorAll('.export-item').forEach(function(btn){
                btn.addEventListener('click', function(){
                    const fmt = btn.getAttribute('data-format');
                    const data = gatherTemplateData();
                    if (fmt === 'csv') downloadCSV(data);
                    else if (fmt === 'xml') downloadXML(data);
                    exportMenu.style.display = 'none';
                });
            });
        }

        // Helpers to collect data from the current form
        function gatherTemplateData(){
            const plantillaId = <?php echo json_encode($idPlantilla); ?>;
            const nombre = <?php echo json_encode($plantilla['nombre']); ?>;
            const descripcion = (document.getElementById('descripcion_plantilla')||{value:''}).value || '';
            const comunidad = (document.getElementById('comunidad_plantilla')||{value:'Madrid'}).value || 'Madrid';
            const usarNeto = !!(document.getElementById('usar_neto') && document.getElementById('usar_neto').checked);

                const trabajos = [];
            $('#tabla-trabajo tbody tr').each(function(){
                const $r = $(this);
                const estudio = $r.find('input[name="estudio[]"]').val() || '';
                const tipo = $r.find('select[name="tipo_trabajo[]"]') .val() || '';
                const fecha = $r.find('input[name="trabajo_fecha[]"]').val() || '';
                const cgs = $r.find('input[name="cgs[]"]').val() || '';
                const takes = $r.find('input[name="takes[]"]').val() || '';
                const total = $r.find('input[name="total[]"]').val() || '';
                // Skip entirely empty rows
                if (!estudio && !tipo && !cgs && !takes && !total && !fecha) return;
                trabajos.push({estudio, tipo, fecha, cgs, takes, total});
            });

            const gastos_variables = [];
            $('#tabla-gastos-variables tbody tr').each(function(){
                const $r = $(this);
                const tipo = $r.find('select[name="tipo_gasto_var[]"]').val() || '';
                const desc = $r.find('input[name="descripcion_gasto_var[]"]').val() || '';
                const monto = $r.find('input[name="monto_gasto_var[]"]').val() || '';
                if (!tipo && !desc && !monto) return;
                gastos_variables.push({tipo, descripcion:desc, monto});
            });

            const gastos_fijos = [];
            $('#tabla-gastos-fijos tbody tr').each(function(){
                const $r = $(this);
                const tipo = $r.find('select[name="tipo_gasto_fijo[]"]').val() || '';
                const desc = $r.find('input[name="descripcion_gasto_fijo[]"]').val() || '';
                const monto = $r.find('input[name="monto_gasto_fijo[]"]').val() || '';
                if (!tipo && !desc && !monto) return;
                gastos_fijos.push({tipo, descripcion:desc, monto});
            });

            return {id:plantillaId, nombre, descripcion, comunidad, usarNeto, trabajos, gastos_variables, gastos_fijos};
        }

        // CSV download (Excel-friendly CSV)
        function downloadCSV(data){
            let lines = [];
            // Header info
            lines.push('Plantilla:,'+escapeCsv(data.nombre));
            lines.push('Descripcion:,'+escapeCsv(data.descripcion));
            lines.push('Comunidad:,'+escapeCsv(data.comunidad));
            lines.push('Usar neto:,'+(data.usarNeto? 'SI':'NO'));
            lines.push('');

            // Trabajos
            lines.push('Trabajos');
            lines.push(['Estudio','Tipo','Fecha','CGs','Takes','Total'].map(escapeCsv).join(','));
            data.trabajos.forEach(function(t){
                lines.push([t.estudio,t.tipo,t.fecha,t.cgs,t.takes,t.total].map(escapeCsv).join(','));
            });
            lines.push('');

            // Gastos variables
            lines.push('Gastos Variables');
            lines.push(['Tipo','Descripcion','Monto'].map(escapeCsv).join(','));
            data.gastos_variables.forEach(function(g){
                lines.push([g.tipo,g.descripcion,g.monto].map(escapeCsv).join(','));
            });
            lines.push('');

            // Gastos fijos
            lines.push('Gastos Fijos');
            lines.push(['Tipo','Descripcion','Monto'].map(escapeCsv).join(','));
            data.gastos_fijos.forEach(function(g){
                lines.push([g.tipo,g.descripcion,g.monto].map(escapeCsv).join(','));
            });

            const blob = new Blob([lines.join('\r\n')], {type:'text/csv;charset=utf-8;'});
            const filename = 'plantilla_' + data.id + '.csv';
            triggerDownload(blob, filename);
        }

        function escapeCsv(v){
            if (v === null || v === undefined) return '';
            const s = String(v);
            // escape double quotes
            if (s.includes('"') || s.includes(',') || s.includes('\n') || s.includes('\r')){
                return '"' + s.replace(/"/g,'""') + '"';
            }
            return s;
        }

        // XML download
        function downloadXML(data){
            function esc(s){
                if (s === null || s === undefined) return '';
                return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&apos;');
            }
            let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
            xml += '<plantilla id="'+esc(data.id)+'">\n';
            xml += '  <nombre>'+esc(data.nombre)+'</nombre>\n';
            xml += '  <descripcion>'+esc(data.descripcion)+'</descripcion>\n';
            xml += '  <comunidad>'+esc(data.comunidad)+'</comunidad>\n';
            xml += '  <usar_neto>'+ (data.usarNeto? 'true':'false') +'</usar_neto>\n';

            xml += '  <trabajos>\n';
            data.trabajos.forEach(function(t){
                xml += '    <trabajo>\n';
                xml += '      <estudio>'+esc(t.estudio)+'</estudio>\n';
                xml += '      <tipo>'+esc(t.tipo)+'</tipo>\n';
                xml += '      <fecha>'+esc(t.fecha)+'</fecha>\n';
                xml += '      <cgs>'+esc(t.cgs)+'</cgs>\n';
                xml += '      <takes>'+esc(t.takes)+'</takes>\n';
                xml += '      <total>'+esc(t.total)+'</total>\n';
                xml += '    </trabajo>\n';
            });
            xml += '  </trabajos>\n';

            xml += '  <gastos_variables>\n';
            data.gastos_variables.forEach(function(g){
                xml += '    <gasto>\n';
                xml += '      <tipo>'+esc(g.tipo)+'</tipo>\n';
                xml += '      <descripcion>'+esc(g.descripcion)+'</descripcion>\n';
                xml += '      <monto>'+esc(g.monto)+'</monto>\n';
                xml += '    </gasto>\n';
            });
            xml += '  </gastos_variables>\n';

            xml += '  <gastos_fijos>\n';
            data.gastos_fijos.forEach(function(g){
                xml += '    <gasto>\n';
                xml += '      <tipo>'+esc(g.tipo)+'</tipo>\n';
                xml += '      <descripcion>'+esc(g.descripcion)+'</descripcion>\n';
                xml += '      <monto>'+esc(g.monto)+'</monto>\n';
                xml += '    </gasto>\n';
            });
            xml += '  </gastos_fijos>\n';

            xml += '</plantilla>';

            const blob = new Blob([xml], {type:'application/xml;charset=utf-8;'});
            const filename = 'plantilla_' + data.id + '.xml';
            triggerDownload(blob, filename);
        }

        function triggerDownload(blob, filename){
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = filename; document.body.appendChild(a); a.click();
            setTimeout(function(){ URL.revokeObjectURL(url); a.remove(); }, 1000);
        }

    })();
    </script>
    <script>
    (function(){
    // AJAX save + autosave for plantilla form
    // Use an explicit id to reliably select the form even when BASE_URL is absolute
    const form = document.querySelector('#form-guardar-plantilla') || document.querySelector('form[action$="/guardar_plantilla.php"]');
        if (!form) return;

        // 🔐 CHECK IF PLANTILLA IS SHARED (READ-ONLY MODE) - ONLY for LECTOR role
        const esPropia = <?php echo $plantilla['es_propia'] ? 'true' : 'false'; ?>;
        const userRole = '<?php echo htmlspecialchars($plantilla['es_propia'] ? 'propietario' : $plantilla['rol'], ENT_QUOTES); ?>';
        const isReadOnly = !esPropia && userRole === 'lector';
        
        if (isReadOnly) {
            // LECTOR ONLY: Disable all input, select, textarea, and button elements in the form
            const inputs = form.querySelectorAll('input:not([readonly]), select, textarea, button[type="submit"], button[type="button"]');
            inputs.forEach(el => {
                el.disabled = true;
                el.style.opacity = '0.6';
                el.style.cursor = 'not-allowed';
            });
            // Hide the save button and save status
            const saveBtn = form.querySelector('button[name="guardar_plantilla"]');
            if (saveBtn) saveBtn.style.display = 'none';
            return; // Exit early - no autosave for LECTOR users
        }
        
        // If EDITOR or ADMIN (shared or own), enable autosave
        if (!esPropia && (userRole === 'editor' || userRole === 'admin')) {
            // EDITOR/ADMIN: Allow editing and autosave - continue normally
            // Nothing to disable here
        }

        const saveStatusEl = document.getElementById('save-status');
        const statusText = saveStatusEl ? saveStatusEl.querySelector('.status-text') : null;

        let dirty = false;
        let saving = false;
        let saveTimeout = null;

        function setStatus(text, cls){
            if (!saveStatusEl) return;
            statusText.textContent = text;
            saveStatusEl.classList.remove('saved','error');
            if (cls) saveStatusEl.classList.add(cls);
        }

        function debounceSave(delay){
            if (saveTimeout) clearTimeout(saveTimeout);
            saveTimeout = setTimeout(()=>{ ajaxSave(); }, delay);
        }

        async function ajaxSave(){
            if (saving) return; // simple guard
            saving = true;
            setStatus('Guardando...', null);

            try {
                // 🔐 VALIDACIÓN DE SEGURIDAD: Verificar rol ANTES de enviar
                // Si alguien removió el 'disabled' del HTML manualmente, aún no puede guardar
                const roleToken = form.querySelector('input[name="user_role_token"]');
                const canEditToken = form.querySelector('input[name="can_edit_token"]');
                
                if (roleToken && roleToken.value === 'lector') {
                    // 🚫 INTENTO MALICIOSO DETECTADO: Usuario LECTOR intentando guardar
                    console.error('[SECURITY] Intento de guardar por LECTOR detectado. Rol:', roleToken.value);
                    saving = false;
                    setStatus('<svg class="icon-inline" viewBox="0 0 24 24" fill="currentColor" style="width:1em;height:1em;vertical-align:-2px;margin-right:4px;"><circle cx="12" cy="12" r="10"/><path d="M8 8l8 8M16 8l-8 8" stroke="white" stroke-width="2"/></svg> Acceso denegado: No puedes guardar', 'error');
                    
                    // Notificar al servidor (logging)
                    fetch(form.action, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        body: JSON.stringify({ 
                            security_incident: true, 
                            reason: 'lector_attempted_save',
                            timestamp: new Date().toISOString()
                        })
                    }).catch(e => console.error('[LOG] Error reporting security incident:', e));
                    
                    return;
                }
                
                if (canEditToken && canEditToken.value === '0') {
                    // 🚫 ACCESO DENEGADO: Usuario no tiene permiso
                    console.error('[SECURITY] Usuario sin permiso de edición intentando guardar');
                    saving = false;
                    setStatus('<svg class="icon-inline" viewBox="0 0 24 24" fill="currentColor" style="width:1em;height:1em;vertical-align:-2px;margin-right:4px;"><circle cx="12" cy="12" r="10"/><path d="M8 8l8 8M16 8l-8 8" stroke="white" stroke-width="2"/></svg> No tienes permiso para guardar', 'error');
                    return;
                }

                const fd = new FormData(form);
                // remove submit button value if present
                fd.delete('guardar_plantilla');

                const resp = await fetch(form.action, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!resp.ok) {
                    const text = await resp.text();
                    throw new Error(text || 'Error al guardar');
                }

                const contentType = resp.headers.get('Content-Type') || '';
                if (contentType.includes('application/json')) {
                    const data = await resp.json();
                    if (data && data.success) {
                        dirty = false;
                        setStatus('Guardado', 'saved');
                        // update id_plantilla if server created new one
                        if (data.id) {
                            const hid = form.querySelector('input[name="id_plantilla"]');
                            if (hid) hid.value = data.id;
                        }
                    } else {
                        throw new Error((data && data.message) || 'Respuesta inesperada');
                    }
                } else {
                    // Non-JSON response (fallback): treat as success
                    dirty = false;
                    setStatus('Guardado', 'saved');
                }

            } catch (err) {
                console.error('Save error', err);
                setStatus('Error guardando', 'error');
            } finally {
                saving = false;
                // clear saved indicator after 2.5s
                setTimeout(()=>{ if (!dirty) setStatus('Listo'); }, 2500);
            }
        }

        // intercept form submit to use AJAX
        form.addEventListener('submit', function(e){
            e.preventDefault();
            ajaxSave();
        });

        // mark dirty on input changes and debounce
        form.addEventListener('input', function(e){
            dirty = true;
            setStatus('Sin guardar');
            debounceSave(1500);
        });

        // beforeunload warning
        window.addEventListener('beforeunload', function(e){
            if (dirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // initial status
        setStatus('Listo');
    })();
    </script>
    <script>
    // ============================
    // ESTUDIO AUTOCOMPLETE SYSTEM
    // ============================
    (function(){
        // Estudios anteriores del usuario (inyectados desde PHP)
        const estudiosData = <?php echo json_encode($estudiosAnteriores); ?>;
        console.log('Estudios cargados:', estudiosData);
        
        // Función para posicionar el dropdown
        function positionDropdown(input, dropdown) {
            const rect = input.getBoundingClientRect();
            dropdown.style.left = (rect.left) + 'px';
            dropdown.style.top = (rect.bottom) + 'px';
            dropdown.style.width = (rect.width) + 'px';
        }
        
        // Inicializar autocomplete para todos los inputs de estudio
        document.querySelectorAll('.estudio-input').forEach(input => {
            const container = input.closest('.estudio-autocomplete-container');
            const dropdown = container.querySelector('.estudio-autocomplete-dropdown');
            
            // Crear elementos del dropdown
            function renderDropdown(filter = '') {
                dropdown.innerHTML = '';
                
                // Si no hay estudios, mostrar mensaje
                if (estudiosData.length === 0) {
                    if (filter.length > 0) {
                        dropdown.classList.remove('visible');
                    }
                    return;
                }
                
                const filtered = estudiosData.filter(estudio => 
                    estudio.toLowerCase().includes(filter.toLowerCase())
                );
                
                if (filtered.length === 0) {
                    dropdown.classList.remove('visible');
                    return;
                }
                
                filtered.forEach(estudio => {
                    const item = document.createElement('div');
                    item.className = 'estudio-autocomplete-item';
                    item.textContent = estudio;
                    item.style.cursor = 'pointer';
                    
                    item.addEventListener('click', function() {
                        input.value = estudio.toUpperCase();
                        dropdown.classList.remove('visible');
                        dropdown.innerHTML = '';
                        input.focus();
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                    
                    item.addEventListener('mouseover', function() {
                        document.querySelectorAll('.estudio-autocomplete-item').forEach(i => i.classList.remove('selected'));
                        item.classList.add('selected');
                    });
                    
                    dropdown.appendChild(item);
                });
                
                positionDropdown(input, dropdown);
                dropdown.classList.add('visible');
            }
            
            // Eventos del input
            input.addEventListener('focus', function() {
                if (estudiosData.length > 0) {
                    renderDropdown(input.value);
                }
            });
            
            input.addEventListener('input', function() {
                input.value = input.value.toUpperCase();
                if (input.value.length > 0 || estudiosData.length === 0) {
                    renderDropdown(input.value);
                } else {
                    renderDropdown('');
                }
            });
            
            input.addEventListener('keydown', function(e) {
                const items = dropdown.querySelectorAll('.estudio-autocomplete-item');
                const selected = dropdown.querySelector('.estudio-autocomplete-item.selected');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (items.length === 0) return;
                    
                    if (!selected) {
                        items[0].classList.add('selected');
                    } else {
                        const nextItem = selected.nextElementSibling;
                        if (nextItem) {
                            selected.classList.remove('selected');
                            nextItem.classList.add('selected');
                        }
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (items.length === 0) return;
                    
                    if (selected) {
                        const prevItem = selected.previousElementSibling;
                        if (prevItem) {
                            selected.classList.remove('selected');
                            prevItem.classList.add('selected');
                        } else {
                            selected.classList.remove('selected');
                        }
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (selected) {
                        selected.click();
                    } else {
                        dropdown.classList.remove('visible');
                    }
                } else if (e.key === 'Escape') {
                    dropdown.classList.remove('visible');
                    dropdown.innerHTML = '';
                }
            });
            
            // Cerrar dropdown al hacer click fuera
            document.addEventListener('click', function(e) {
                if (!container.contains(e.target)) {
                    dropdown.classList.remove('visible');
                    dropdown.innerHTML = '';
                }
            });
            
            // Repositionar al hacer scroll
            window.addEventListener('scroll', function() {
                if (dropdown.classList.contains('visible')) {
                    positionDropdown(input, dropdown);
                }
            });
        });
    })();
    </script>
    <script>
    // ============================
    // KEYBOARD SHORTCUTS SYSTEM
    // ============================
    (function(){
        const helpModal = document.getElementById('help-modal');
        const helpBtn = document.getElementById('help-btn');
        const helpClose = document.getElementById('help-close');
        const form = document.getElementById('form-guardar-plantilla');
        const isReadOnly = <?php echo json_encode($isReadOnly); ?>;
        const canEdit = <?php echo json_encode($canEdit); ?>;
        
        // Help modal controls
        if (helpBtn && helpModal && helpClose) {
            helpBtn.addEventListener('click', function() {
                helpModal.style.display = 'flex';
            });
            
            helpClose.addEventListener('click', function() {
                helpModal.style.display = 'none';
            });
            
            helpModal.addEventListener('click', function(e) {
                if (e.target === helpModal) {
                    helpModal.style.display = 'none';
                }
            });
            
            helpModal.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    helpModal.style.display = 'none';
                }
            });
        }
        
        // Keyboard shortcuts listener
        document.addEventListener('keydown', function(e) {
            // Ignora atajos si estamos en modo lectura
            if (isReadOnly) return;
            
            // Ctrl+S: Guardar plantilla
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const submitBtn = form.querySelector('button[name="guardar_plantilla"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.click();
                    showToast('📾 Plantilla guardada con Ctrl+S');
                }
                return;
            }
            
            // Ctrl+N: Agregar fila de Trabajo
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                const btn = document.getElementById('btn-agregar-trabajo');
                if (btn && !btn.disabled) {
                    btn.click();
                    showToast('➕ Nueva fila de Trabajo agregada con Ctrl+N');
                }
                return;
            }
            
            // Ctrl+Alt+V: Agregar fila de Gasto Variable
            if (e.ctrlKey && e.altKey && e.key === 'v') {
                e.preventDefault();
                const btn = document.getElementById('btn-agregar-gasto-variable');
                if (btn && !btn.disabled) {
                    btn.click();
                    showToast('➕ Nueva fila de Gasto Variable agregada con Ctrl+Alt+V');
                }
                return;
            }
            
            // Ctrl+Alt+F: Agregar fila de Gasto Fijo
            if (e.ctrlKey && e.altKey && e.key === 'f') {
                e.preventDefault();
                const btn = document.getElementById('btn-agregar-gasto-fijo');
                if (btn && !btn.disabled) {
                    btn.click();
                    showToast('➕ Nueva fila de Gasto Fijo agregada con Ctrl+Alt+F');
                }
                return;
            }
            
            // Ctrl+Shift+P: Compartir plantilla
            if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                e.preventDefault();
                const shareBtn = form.querySelector('.share-btn');
                if (shareBtn && !shareBtn.disabled) {
                    shareBtn.click();
                    showToast('<svg class="icon-inline" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2v11m-7-2l7-7 7 7M2 20h20v2H2z"/></svg> Abriendo diálogo de compartir con Ctrl+Shift+P');
                } else {
                    showToast('<svg class="icon-inline" viewBox="0 0 24 24" fill="currentColor" style="width:1em;height:1em;vertical-align:-2px;margin-right:4px;"><circle cx="12" cy="12" r="10"/><path d="M8 8l8 8M16 8l-8 8" stroke="white" stroke-width="2"/></svg> No tienes permisos para compartir esta plantilla');
                }
                return;
            }
            
            // Ctrl+Shift+E: Exportar plantilla
            if (e.ctrlKey && e.shiftKey && e.key === 'E') {
                e.preventDefault();
                const exportBtn = document.getElementById('export-btn');
                if (exportBtn && !exportBtn.disabled) {
                    exportBtn.click();
                    showToast('📥 Menú de exportación abierto con Ctrl+Shift+E');
                }
                return;
            }
            
            // ?: Mostrar/ocultar ayuda
            if (e.key === '?' || (e.shiftKey && e.key === '/')) {
                e.preventDefault();
                if (helpModal) {
                    helpModal.style.display = helpModal.style.display === 'none' ? 'flex' : 'none';
                }
                return;
            }
            
            // Escape: Cerrar cualquier modal abierto
            if (e.key === 'Escape') {
                if (helpModal && helpModal.style.display === 'flex') {
                    e.preventDefault();
                    helpModal.style.display = 'none';
                }
            }
        });
        
        // Toast notification system
        function showToast(message) {
            // Crear contenedor de toast si no existe
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;pointer-events:none;';
                document.body.appendChild(toastContainer);
            }
            
            // Crear elemento de toast
            const toast = document.createElement('div');
            toast.style.cssText = `
                background:#333;
                color:#fff;
                padding:12px 16px;
                border-radius:6px;
                margin-bottom:10px;
                box-shadow:0 4px 12px rgba(0,0,0,0.15);
                animation:slideIn 0.3s ease-out;
                max-width:300px;
                word-wrap:break-word;
                pointer-events:auto;
            `;
            toast.textContent = message;
            toastContainer.appendChild(toast);
            
            // Auto-remove after 3 seconds
            setTimeout(function() {
                toast.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 3000);
        }
        
        // Add animations to document
        if (!document.getElementById('keyboard-shortcuts-styles')) {
            const style = document.createElement('style');
            style.id = 'keyboard-shortcuts-styles';
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    })();
    </script>
</html>
