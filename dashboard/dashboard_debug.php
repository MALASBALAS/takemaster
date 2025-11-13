<?php
/**
 * dashboard_debug.php
 * Versión de debug del dashboard para diagnosticar problemas
 */

require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
require_once __DIR__ . '/../funciones/encryption.php';
require_once __DIR__ . '/../funciones/dashboard_helpers.php';
start_secure_session();

// Verificación de inicio de sesión
if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$username = $_SESSION['username'];
$userEmail = obtener_email_usuario($conn, $username);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard DEBUG</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .debug-box { background: #fff; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #0b69ff; }
        .debug-box h3 { margin-top: 0; color: #0b69ff; }
        .debug-box pre { background: #f9f9f9; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .error { border-left-color: #dc3545; color: #dc3545; }
        .success { border-left-color: #28a745; color: #28a745; }
        a { color: #0b69ff; }
    </style>
</head>
<body>
    <h1>🔧 Dashboard DEBUG</h1>
    
    <div class="debug-box success">
        <h3>✓ Session Info</h3>
        <pre>Username: <?php echo htmlspecialchars($username); ?>
User Email: <?php echo htmlspecialchars($userEmail ?? 'NULL'); ?></pre>
    </div>

    <div class="debug-box">
        <h3>📊 Plantillas Propias</h3>
        <?php 
        $plantillas_propias = obtener_plantillas_propias($conn, $username);
        ?>
        <pre>Total: <?php echo count($plantillas_propias); ?>

<?php 
foreach ($plantillas_propias as $i => $p) {
    echo "[$i] ID: {$p['id']}, Nombre: {$p['nombre']}\n";
}
?></pre>
        <?php if (empty($plantillas_propias)): ?>
            <p style="color: #dc3545;"><strong>⚠️ NO PLANTILLAS ENCONTRADAS</strong></p>
        <?php endif; ?>
    </div>

    <div class="debug-box">
        <h3>📤 Plantillas Compartidas</h3>
        <?php 
        $plantillas_compartidas = obtener_plantillas_compartidas($conn, $userEmail);
        ?>
        <pre>Total: <?php echo count($plantillas_compartidas); ?>

<?php 
foreach ($plantillas_compartidas as $i => $p) {
    echo "[$i] ID: {$p['id']}, Nombre: {$p['nombre']}, Rol: {$p['rol']}\n";
}
?></pre>
        <?php if (empty($plantillas_compartidas)): ?>
            <p style="color: #999;">Sin plantillas compartidas</p>
        <?php endif; ?>
    </div>

    <div class="debug-box">
        <h3>🔗 Conexión a BD</h3>
        <pre><?php echo $conn ? "✓ Conectada" : "✗ No conectada"; ?>
Host: <?php echo htmlspecialchars($_SERVER['DB_HOST'] ?? 'N/A'); ?>
Database: <?php echo htmlspecialchars($_SERVER['DB_NAME'] ?? 'N/A'); ?></pre>
    </div>

    <hr>
    <p><a href="<?php echo BASE_URL; ?>/dashboard/dashboard.php">← Volver al Dashboard Normal</a></p>
</body>
</html>
