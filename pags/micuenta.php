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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <style>
        /* Layout: fixed left sidebar and content to the right */
        :root { --account-sidebar-width: 220px; --topnav-height: 64px; }
        .container { display: block; }
        .sidebar {
            position: fixed;
            left: 0;
            top: var(--topnav-height);
            bottom: 0;
            width: var(--account-sidebar-width);
            background-color: #181818;
            padding: 18px 12px;
            box-shadow: 2px 0 6px rgba(0,0,0,0.12);
            overflow-y: auto;
            z-index: 110;
        }
        .content {
            margin-left: calc(var(--account-sidebar-width) + 24px);
            padding: 24px;
        }
        /* Sidebar links */
        .sidebar a {
            display: block;
            padding: 10px 12px;
            text-decoration: none;
            color: #ffffff;
            margin-bottom: 6px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 1rem;
            border-radius: 6px;
        }
        .sidebar a:hover { background-color: #2f2f2f; color: #fff; }

        /* Make sure top of content is not hidden under topnav */
        .content h2 { margin-top: 6px; }

        /* Small screens: keep sidebar fixed to the left but use a smaller width to avoid heavy overlap
           This keeps the sidebar 'pegado' al borde izquierdo en modo móvil while keeping content readable. */
        @media (max-width: 900px) {
            :root { --account-sidebar-width: 180px; }
            .sidebar {
                position: fixed;
                left: 0;
                top: var(--topnav-height);
                bottom: 0;
                width: var(--account-sidebar-width);
                box-shadow: 2px 0 6px rgba(0,0,0,0.12);
                z-index: 120;
            }
            .content {
                margin-left: calc(var(--account-sidebar-width) + 16px);
                padding: 12px;
            }
            /* allow content and sidebar to scroll independently on small screens */
            .sidebar { overflow-y: auto; }
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
