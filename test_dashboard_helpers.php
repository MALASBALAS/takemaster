<?php
/**
 * test_dashboard_helpers.php
 * Prueba rápida de las funciones del dashboard
 */

require_once __DIR__ . '/src/nav/bootstrap.php';
require_once __DIR__ . '/src/nav/db_connection.php';
require_once __DIR__ . '/funciones/encryption.php';
require_once __DIR__ . '/funciones/dashboard_helpers.php';

start_secure_session();

if (!isset($_SESSION['username'])) {
    die("❌ No logeado");
}

$username = $_SESSION['username'];
echo "✓ Username: $username\n";

// Test 1: Obtener email
echo "\n--- Test 1: obtener_email_usuario ---\n";
$email = obtener_email_usuario($conn, $username);
echo "Email: " . ($email ? $email : "NULL ❌") . "\n";

// Test 2: Plantillas propias
echo "\n--- Test 2: obtener_plantillas_propias ---\n";
$propias = obtener_plantillas_propias($conn, $username);
echo "Plantillas propias: " . count($propias) . "\n";
if (count($propias) > 0) {
    echo "Primera: " . $propias[0]['nombre'] . " (ID: " . $propias[0]['id'] . ")\n";
}

// Test 3: Plantillas compartidas
echo "\n--- Test 3: obtener_plantillas_compartidas ---\n";
if ($email) {
    $compartidas = obtener_plantillas_compartidas($conn, $email);
    echo "Plantillas compartidas: " . count($compartidas) . "\n";
    if (count($compartidas) > 0) {
        echo "Primera: " . $compartidas[0]['nombre'] . " (ID: " . $compartidas[0]['id'] . ", Rol: " . $compartidas[0]['rol'] . ")\n";
    }
} else {
    echo "Skipped - sin email\n";
}

// Test 4: Funciones disponibles
echo "\n--- Test 4: Funciones disponibles ---\n";
echo "✓ obtener_plantillas_propias: " . (function_exists('obtener_plantillas_propias') ? 'SÍ' : 'NO') . "\n";
echo "✓ obtener_plantillas_compartidas: " . (function_exists('obtener_plantillas_compartidas') ? 'SÍ' : 'NO') . "\n";
echo "✓ extraer_totales_plantilla: " . (function_exists('extraer_totales_plantilla') ? 'SÍ' : 'NO') . "\n";
echo "✓ calcular_agregados: " . (function_exists('calcular_agregados') ? 'SÍ' : 'NO') . "\n";
echo "✓ obtener_permisos_rol: " . (function_exists('obtener_permisos_rol') ? 'SÍ' : 'NO') . "\n";
?>
