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

include 'src/nav/topnav.php';
  // Incluir la barra de navegación superior

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
    <div class="container">
        <h1><?php echo htmlspecialchars($plantilla['nombre']); ?></h1>

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
                </tr>
            </thead>
            <tbody>
                <!-- Filas dinámicas para agregar detalles del trabajo -->
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
                </tr>
            </tbody>
        </table>

        <!-- Botón para agregar fila -->
        <button type="button" id="btn-agregar-fila">Agregar Fila</button>

<!-- Tabla para gastos variables -->
<h2>Gastos Variables</h2>
<table id="tabla-gastos-variables">
    <thead>
        <tr>
            <th>Tipo de Gasto</th>
            <th>Descripción</th>
            <th>Monto</th>
        </tr>
    </thead>
    <tbody>
        <!-- Filas dinámicas para agregar detalles de los gastos variables -->
        <tr>
            <td>
                <select name="tipo_gasto[]" required>
                    <option value="Ocio">Ocio</option>
                    <option value="Necesidades">Necesidades</option>
                    <option value="Otros">Otros</option>
                </select>
            </td>
            <td><input type="text" name="descripcion_gasto[]" placeholder="Descripción"></td>
            <td><input type="number" name="monto_gasto[]" placeholder="Monto" required></td>
        </tr>
    </tbody>
</table>

<!-- Botón para agregar fila de gasto variable -->
<button type="button" id="btn-agregar-gasto-variable">Agregar Gasto Variable</button>

<!-- Tabla para gastos fijos -->
<h2>Gastos Fijos</h2>
<table id="tabla-gastos-fijos">
    <thead>
        <tr>
            <th>Tipo de Gasto</th>
            <th>Descripción</th>
            <th>Monto</th>
        </tr>
    </thead>
    <tbody>
        <!-- Filas dinámicas para agregar detalles de los gastos fijos -->
        <tr>
            <td>
                <select name="tipo_gasto[]" required>
                    <option value="Casa">Casa</option>
                    <option value="Hipoteca">Hipoteca</option>
                    <option value="Alquiler">Alquiler</option>
                    <option value="Coche">Coche</option>
                    <option value="Luz">Luz</option>
                    <option value="Agua">Agua</option>
                    <option value="Otros">Otros</option>
                </select>
            </td>
            <td><input type="text" name="descripcion_gasto[]" placeholder="Descripción"></td>
            <td><input type="number" name="monto_gasto[]" placeholder="Monto" required></td>
        </tr>
    </tbody>
</table>

<!-- Botón para agregar fila de gasto fijo -->
<button type="button" id="btn-agregar-gasto-fijo">Agregar Gasto Fijo</button>

<!-- Casilla para el total del mes -->
<div id="total-mes">
    <h2>Total Mes</h2>
    <p id="total-mes-valor">0.00 €</p>
</div>


        <!-- Botón de guardar -->
        <form method="post" action="guardar_plantilla.php">
            <input type="hidden" name="id_plantilla" value="<?php echo $idPlantilla; ?>">
            <button type="submit" name="guardar_plantilla" class="btn-guardar-plantilla">Guardar</button>
        </form>
    </div>

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

    // Función para calcular el total del mes al cambiar los montos de gastos variables
    $('#tabla-gastos-variables tbody').on('input', 'input[name="monto_gasto[]"]', function() {
        calcularTotalMes();
    });

    // Función para calcular el total del mes al cambiar los montos de gastos fijos
    $('#tabla-gastos-fijos tbody').on('input', 'input[name="monto_gasto[]"]', function() {
        calcularTotalMes();
    });

    // Agregar fila automáticamente al completar la fila anterior
    $('#tabla-trabajo tbody').on('input', 'input[name="estudio[]"], select[name="tipo_trabajo[]"]', function() {
        var ultimaFila = $('#tabla-trabajo tbody tr').last();
        var estudio = ultimaFila.find('input[name="estudio[]"]').val();
        var tipoTrabajo = ultimaFila.find('select[name="tipo_trabajo[]"]').val();

        // Verificar si la última fila está completa
        if (estudio !== "" && tipoTrabajo !== "") {
            agregarFila();
        }
    });

    // Agregar fila automáticamente al completar la fila anterior en gastos variables
$('#tabla-gastos-variables tbody').on('input', 'input[name="monto_gasto[]"], select[name="tipo_gasto[]"]', function() {
    var ultimaFila = $('#tabla-gastos-variables tbody tr').last();
    var descripcion = ultimaFila.find('input[name="monto_gasto[]"]').val();
    var tipoGasto = ultimaFila.find('select[name="tipo_gasto[]"]').val();

    // Verificar si la última fila está completa
    if (descripcion !== "" && tipoGasto !== "") {
        agregarFilaGastoVariable();
    }
});

// Agregar fila automáticamente al completar la fila anterior en gastos fijos
$('#tabla-gastos-fijos tbody').on('input', 'input[name="monto_gasto[]"], select[name="tipo_gasto[]"]', function() {
    var ultimaFila = $('#tabla-gastos-fijos tbody tr').last();
    var descripcion = ultimaFila.find('input[name="monto_gasto[]"]').val();
    var tipoGasto = ultimaFila.find('select[name="tipo_gasto[]"]').val();

    // Verificar si la última fila está completa
    if (descripcion !== "" && tipoGasto !== "") {
        agregarFilaGastoFijo();
    }
});


    // Botón para agregar fila
    $('#btn-agregar-fila').click(function() {
        agregarFila();
    });

    // Función para agregar fila
    function agregarFila() {
        // Clonar la última fila de la tabla y agregarla al final
        var nuevaFila = $('#tabla-trabajo tbody tr:last').clone();

        // Limpiar los campos de la nueva fila
        nuevaFila.find('input[type="text"], input[type="number"]').val('');

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

        // Limpiar los campos de la nueva fila
        nuevaFila.find('input[type="text"], input[type="number"]').val('');

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

        // Limpiar los campos de la nueva fila
        nuevaFila.find('input[type="text"], input[type="number"]').val('');

        // Agregar la nueva fila a la tabla
        $('#tabla-gastos-fijos tbody').append(nuevaFila);
    }

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
            var monto = parseFloat($(this).find('input[name="monto_gasto[]"]').val());
            if (!isNaN(monto)) {
                totalMes -= monto; // Los gastos se restan, por eso se usa el operador de resta
            }
        });

        // Sumar los montos de los gastos fijos
        $('#tabla-gastos-fijos tbody tr').each(function() {
            var monto = parseFloat($(this).find('input[name="monto_gasto[]"]').val());
            if (!isNaN(monto)) {
                totalMes -= monto; // Los gastos se restan, por eso se usa el operador de resta
            }
        });

        // Actualizar el valor en el HTML
        $('#total-mes-valor').text(totalMes.toFixed(2) + ' €');
    }
});


    </script>
</body>
</html>
