<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
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
    // Obtener los datos del formulario
    $idPlantilla = (int)($_POST['id_plantilla'] ?? 0);
    $estudio = trim((string)($_POST['estudio'] ?? ''));
    $tipo_trabajo = trim((string)($_POST['tipo_trabajo'] ?? ''));
    $cgs = trim((string)($_POST['cgs'] ?? ''));
    $takes = trim((string)($_POST['takes'] ?? ''));
    $total = trim((string)($_POST['total'] ?? ''));
    $tipo_gasto_var = $_POST['tipo_gasto_var'];
    $descripcion_gasto_var = $_POST['descripcion_gasto_var'];
    $monto_gasto_var = $_POST['monto_gasto_var'];
    $tipo_gasto_fijo = $_POST['tipo_gasto_fijo'];
    $descripcion_gasto_fijo = $_POST['descripcion_gasto_fijo'];
    $monto_gasto_fijo = $_POST['monto_gasto_fijo'];

    // Leer el archivo JSON de plantillas
    $json_path = __DIR__ . '/db/plantillas.json';
    $datos_json = @file_get_contents($json_path);
    $plantillas = json_decode($datos_json, true);

    // Buscar la plantilla por ID en el JSON
    foreach ($plantillas as &$plantilla_json) {
        if ($plantilla_json['id'] == $idPlantilla) {
            // Actualizar los datos de la plantilla
            $plantilla_json['estudio'] = $estudio;
            $plantilla_json['tipo_trabajo'] = $tipo_trabajo;
            $plantilla_json['cgs'] = $cgs;
            $plantilla_json['takes'] = $takes;
            $plantilla_json['total'] = $total;
            $plantilla_json['tipo_gasto_var'] = $tipo_gasto_var;
            $plantilla_json['descripcion_gasto_var'] = $descripcion_gasto_var;
            $plantilla_json['monto_gasto_var'] = $monto_gasto_var;
            $plantilla_json['tipo_gasto_fijo'] = $tipo_gasto_fijo;
            $plantilla_json['descripcion_gasto_fijo'] = $descripcion_gasto_fijo;
            $plantilla_json['monto_gasto_fijo'] = $monto_gasto_fijo;
            break;
        }
    }

    // Convertir nuevamente los datos a formato JSON
    $nuevos_datos_json = json_encode($plantillas, JSON_PRETTY_PRINT);

    // Guardar los datos actualizados en el archivo JSON
    @file_put_contents($json_path, $nuevos_datos_json);

    // Redireccionar a la página de visualización del dashboard
    header("Location: view_dashboard.php?id=" . urlencode((string)$idPlantilla));
    exit;
}
?>
