<?php
require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

// If user is not logged in, send to login
if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

// Always redirect to the "Mi cuenta" dashboard to ensure the dashboard is shown from the account panel
$idPlantilla = isset($_GET['id']) ? intval($_GET['id']) : null;
if ($idPlantilla) {
    // Pass id as parameter if desired (optional)
    header("Location: " . BASE_URL . "/pags/micuenta.php?section=dashboard&open_id=" . $idPlantilla);
} else {
    header("Location: " . BASE_URL . "/pags/micuenta.php?section=dashboard");
}
exit;
