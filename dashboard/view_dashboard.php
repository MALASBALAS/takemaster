<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

if (!isset($_SESSION['username'])) {
    header("Location: /../auth/login.php");
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

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Plantilla - <?php echo htmlspecialchars($plantilla['nombre']); ?></title>
</head>
<body>
    <div>
        <?php include __DIR__ . '/../plantillas/miplantilla.php'; ?>
    </div>
</body>
</html>
