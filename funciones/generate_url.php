<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    die("Debe iniciar sesión para generar una URL");
}

// Obtener el ID del dashboard desde la solicitud (por ejemplo, a través de POST o GET)
if (isset($_GET['dashboard_id'])) {
    $dashboard_id = $_GET['dashboard_id'];
} else {
    die("ID del dashboard no especificado");
}

// Verificar que el dashboard pertenece al usuario autenticado
$username = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id FROM dashboards WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $dashboard_id, $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Generar la URL única usando BASE_URL
    require_once __DIR__ . '/../src/nav/config.php';
    $unique_url = BASE_URL . '/dashboard/view_dashboard.php?dashboard_id=' . urlencode((string)$dashboard_id);
    echo "URL para compartir: <a href='{$unique_url}'>{$unique_url}</a>";
} else {
    echo "Dashboard no encontrado o no tienes permiso para generar la URL";
}
?>
