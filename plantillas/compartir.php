<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
require_once __DIR__ . '/../src/nav/config.php';
start_secure_session();

if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$username = $_SESSION['username'];
$idPlantilla = (int)($_GET['id'] ?? 0);

// Verificar que la plantilla pertenece al usuario
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

$enlaceCompartir = BASE_URL . "/misdatos?id=" . urlencode((string)$idPlantilla);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Compartir Plantilla - <?php echo htmlspecialchars($plantilla['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
    <h1>Compartir Plantilla</h1>
    <p>Comparte este enlace para que otros puedan ver tu plantilla:</p>
    <a href="<?php echo htmlspecialchars($enlaceCompartir, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($enlaceCompartir, ENT_QUOTES, 'UTF-8'); ?></a>
</body>
</html>
