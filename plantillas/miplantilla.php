<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$username = $_SESSION['username'];
$idPlantilla = $_GET['id'];

// Obtener la plantilla de la base de datos
$stmt = $conn->prepare("SELECT * FROM plantillas WHERE id = ? AND username = ?");
$stmt->bind_param("is", $idPlantilla, $username);
$stmt->execute();
$result = $stmt->get_result();
$plantilla = $result->fetch_assoc();
$stmt->close();

if (!$plantilla) {
    echo "Plantilla no encontrada.";
    exit;
}

// Decode stored contenido JSON into arrays for rendering
$contenido = [];
if (!empty($plantilla['contenido'])) {
    $decoded = json_decode($plantilla['contenido'], true);
    if (is_array($decoded)) $contenido = $decoded;
}
$trabajoRows = $contenido['trabajo'] ?? [];
$gastosVariablesRows = $contenido['gastos_variables'] ?? [];
$gastosFijosRows = $contenido['gastos_fijos'] ?? [];

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
    <title>Ver Plantilla - <?php echo htmlspecialchars($plantilla['nombre']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/style-table.css">
    <style>
        /* Estilos adicionales para la página */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
            text-align: izq;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../src/nav/topnav.php'; ?>

    <div class="container center">
        <h1><?php echo htmlspecialchars($plantilla['nombre']); ?></h1>

    <form method="post" action="<?php echo BASE_URL; ?>/dashboard/guardar_plantilla.php">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="id_plantilla" value="<?php echo $idPlantilla; ?>">

        <!-- Tabla para el trabajo -->
        <h2>Trabajo</h2>
        <table id="tabla-trabajo">
            <thead>
                <tr>
                    <th>Estudio</th>
                    <th>Tipo de Trabajo</th>
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
                            <td><input type="text" name="estudio[]" value="<?php echo htmlspecialchars($r['estudio'] ?? '', ENT_QUOTES); ?>" placeholder="Estudio de doblaje"></td>
                            <td>
                                <select name="tipo_trabajo[]" required>
                                    <?php
                                    $opts = ['Cine','Serie','Prueba','Spot','Publicidad','Direccion Cine','Direccion Serie','Personalizado'];
                                    $sel = $r['tipo'] ?? '';
                                    foreach ($opts as $opt) {
                                        $s = ($opt === $sel) ? 'selected' : '';
                                        echo "<option value=\"".htmlspecialchars($opt, ENT_QUOTES) ."\" $s>".htmlspecialchars($opt)."</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td><input type="number" name="cgs[]" value="<?php echo htmlspecialchars($r['cgs'] ?? '', ENT_QUOTES); ?>" placeholder="CGs" required></td>
                            <td><input type="number" name="takes[]" value="<?php echo htmlspecialchars($r['takes'] ?? '', ENT_QUOTES); ?>" placeholder="Takes" required></td>
                            <td>
                                <!-- Este campo será calculado automáticamente -->
                                <input type="text" name="total[]" readonly>
                            </td>
                            <td><button type="button" class="btn-delete-row">Eliminar</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td><input type="text" name="estudio[]" placeholder="Estudio de doblaje"></td>
                        <td>
                            <select name="tipo_trabajo[]" required>
                                <option value="Cine">Cine</option>
                                <option value="Serie">Serie</option>
                                <option value="Prueba">Prueba</option>
                                <option value="Spot">Spot</option>
                                <option value="Publicidad">Publicidad</option>
                                <option value="Direccion Cine">Dirección Cine</option>
                                <option value="Direccion Serie">Dirección Serie</option>
                                <option value="Personalizado">Personalizado</option>
                            </select>
                        </td>
                        <td><input type="number" name="cgs[]" placeholder="CGs" required></td>
                        <td><input type="number" name="takes[]" placeholder="Takes" required></td>
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
                        <select name="tipo_gasto_var[]" required>
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
                    <td><input type="number" name="monto_gasto_var[]" value="<?php echo htmlspecialchars($gv['monto'] ?? '', ENT_QUOTES); ?>" placeholder="Monto" required></td>
                    <td><button type="button" class="btn-delete-row">Eliminar</button></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td>
                    <select name="tipo_gasto_var[]" required>
                        <option value="Ocio">Ocio</option>
                        <option value="Necesidades">Necesidades</option>
                        <option value="Otros">Otros</option>
                    </select>
                </td>
                <td><input type="text" name="descripcion_gasto_var[]" placeholder="Descripción"></td>
                <td><input type="number" name="monto_gasto_var[]" placeholder="Monto" required></td>
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
                        <select name="tipo_gasto_fijo[]" required>
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
                    <td><input type="number" name="monto_gasto_fijo[]" value="<?php echo htmlspecialchars($gf['monto'] ?? '', ENT_QUOTES); ?>" placeholder="Monto" required></td>
                    <td><button type="button" class="btn-delete-row">Eliminar</button></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td>
                    <select name="tipo_gasto_fijo[]" required>
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
                <td><input type="number" name="monto_gasto_fijo[]" placeholder="Monto" required></td>
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


        <!-- Botón de guardar -->
            <div style="margin-top:18px; text-align:center;">
                <button type="submit" name="guardar_plantilla" class="button-submit">Guardar plantilla</button>
                <span id="save-status" class="save-status" aria-live="polite" style="display:inline-flex;align-items:center;">
                    <span class="status-text">Listo</span>
                </span>
            </div>
        </form>
    </div>
    <br>
    <?php include __DIR__ . '/../src/nav/footer.php'; ?>


    <!-- Script JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
$(document).ready(function() {
    // Función para calcular el total al cambiar los valores de CGs o Takes
    $('#tabla-trabajo tbody').on('input', 'input[name="cgs[]"], input[name="takes[]"]', function() {
        var fila = $(this).closest('tr');
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

        // Limpiar los campos de la nueva fila y resetear selects
        nuevaFila.find('input[type="text"], input[type="number"]').val('');
        nuevaFila.find('input[name="total[]"]').val('');
        nuevaFila.find('select').prop('selectedIndex', 0);

        // Agregar la nueva fila a la tabla
        $('#tabla-trabajo tbody').append(nuevaFila);
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
            row.find('input[type="text"], input[type="number"]').val('');
            row.find('select').prop('selectedIndex', 0);
            row.find('input[name="total[]"]').val('');
        }
        calcularTotalMes();
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

        // Asignar los valores correspondientes de acuerdo con el tipo de trabajo
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
            case "Personalizado":
                // Asignar los valores correspondientes para el tipo de trabajo personalizado (si existe)
                break;
            default:
                break;
        }

        // Calcular el total
        var total = (cgs * valorCgs) + (takes * valorTakes);

        // Asignar el total al campo correspondiente en la fila
        fila.find('input[name="total[]"]').val(total.toFixed(2));
    }

    // Función para calcular el total del mes
    function calcularTotalMes() {
        var totalMes = 0;

        // Sumar los totales de la tabla de trabajo
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

    // Initial calculation for loaded rows
    $('#tabla-trabajo tbody tr').each(function() { calcularTotal($(this)); });
    calcularTotalMes();
});


    </script>
    <script>
    (function(){
        // AJAX save + autosave for plantilla form
        const form = document.querySelector('form[action="/dashboard/guardar_plantilla.php"]');
        if (!form) return;

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
</body>
</html>
