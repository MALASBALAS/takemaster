<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

// Verificación de inicio de sesión
if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi cuenta</title>
    <link rel="stylesheet" href="/../src/css/style.css">
    <style>
        .container {
            display: flex;
        }
        .sidebar {
            width: 20%;
            background-color: #181818;
            padding: 15px;
            box-shadow: 2px 0px 5px rgba(0,0,0,0.1);
        }
        .content {
            width: 80%;
            padding: 15px;
        }
        .sidebar a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #ffffff;
            width: auto;
            margin-bottom: 5px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 1rem;
        }
        .sidebar a:hover {
            background-color: #858585;
            color: black;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/nav/topnav.php';
 ?>
    <div class="container">
        <div class="sidebar">
            <a href="/pags/micuenta.php?section=configuracion">Configuración</a>
            <a href="/pags/micuenta.php?section=dashboard">Dashboard</a>
            <a href="/auth/logout.php">Salir</a>
        </div>
        <div class="content">
            <h2>Bienvenido, <?php echo htmlspecialchars($username); ?></h2>
            <?php
            if (isset($_GET['section'])) {
                $section = $_GET['section'];
                switch ($section) {
                    case 'configuracion':
                        include  __DIR__ . '/../dashboard/configuracion.php';
                        break;
                    case 'dashboard':
                        include __DIR__ . '/../dashboard/dashboard.php';
                        break;
                    default:
                        echo "<p>Seleccione una opción del menú.</p>";
                        break;
                }
            } else {
                echo "<p>Seleccione una opción del menú.</p>";
            }
            ?>
        </div>
    </div>
    <?php include __DIR__ . '/../src/nav/footer.php'; ?>
    </div>
</body>
</html>
