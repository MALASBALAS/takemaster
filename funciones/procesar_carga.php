<?php
require __DIR__ . '/../src/nav/bootstrap.php';
start_secure_session();

if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

// Cargar datos del formulario JSON
$datos_json = @file_get_contents(__DIR__ . '/../dashboard/db/plantillas.json');
$datos_formulario = json_decode($datos_json, true);

// Obtener los datos del usuario de la sesión
$username = $_SESSION['username'];

// Verificar si hay datos para el usuario en el formulario JSON
if (isset($datos_formulario[$username])) {
    // Obtener los datos del usuario
    $datos_usuario = $datos_formulario[$username];

    // Asignar los datos del usuario a variables individuales
    $estudio = $datos_usuario['estudio'];
    $tipo_trabajo = $datos_usuario['tipo_trabajo'];
    $cgs = $datos_usuario['cgs'];
    $takes = $datos_usuario['takes'];
    $total = $datos_usuario['total'];
    $tipo_gasto_var = $datos_usuario['tipo_gasto_var'];
    $descripcion_gasto_var = $datos_usuario['descripcion_gasto_var'];
    $monto_gasto_var = $datos_usuario['monto_gasto_var'];
    $tipo_gasto_fijo = $datos_usuario['tipo_gasto_fijo'];
    $descripcion_gasto_fijo = $datos_usuario['descripcion_gasto_fijo'];
    $monto_gasto_fijo = $datos_usuario['monto_gasto_fijo'];
} else {
    // Si no hay datos para el usuario, redirigir a la página principal o mostrar un mensaje de error
    header("Location: /index.php");
    exit;
}
?>
