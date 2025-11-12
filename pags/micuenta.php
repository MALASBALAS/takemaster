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

// Procesar eliminación de plantilla AQUÍ, antes de renderizar HTML
// Esto asegura que la redirección funciona correctamente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_plantilla'])) {
    if (!validate_csrf()) {
        http_response_code(400);
        die('CSRF inválido');
    }
    
    // Usar función de seguridad para eliminación con auditoría
    require_once __DIR__ . '/../funciones/plantillas_security.php';
    
    $resultado = eliminar_plantilla_segura(
        $conn,
        (int)$_POST['eliminar_plantilla'],
        $username,
        get_client_ip()
    );
    
    if (!$resultado['success']) {
        error_log('[micuenta.php] Error eliminando plantilla: ' . $resultado['error']);
        http_response_code(400);
        die('Error: ' . $resultado['error']);
    }
    
    error_log('[micuenta.php] Plantilla eliminada (soft delete) por usuario ' . $username);
    
    // Redirigir al dashboard después de eliminar
    header("Location: " . BASE_URL . "/pags/micuenta.php?section=dashboard", true, 302);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi cuenta</title>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/src/img/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <meta name="base-url" content="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>">
    <style>
        /* Layout: fixed left sidebar and content to the right */
        :root { --account-sidebar-width: 220px; --topnav-height: 64px; }
        .container { display: block; }
        /* Sidebar: fixed on desktop, collapsible on small screens */
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
            transition: transform 0.22s ease;
            will-change: transform;
        }

        /* Hidden state for mobile (translate out of view) */
        .sidebar--hidden { transform: translateX(-110%); }

        /* Overlay when sidebar is open on mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 105;
        }

        .content {
            margin-left: calc(var(--account-sidebar-width) + 24px);
            padding: 24px;
            transition: margin-left 0.18s ease;
        }

        /* Controls bar placed below the topnav with action buttons.
           Use a simple full-width bar (no rounded corners) placed under the fixed topnav. */
        .below-topnav-actions {
            position: relative;
            margin-top: calc(var(--topnav-height) + 6px);
            left: 0;
            z-index: 120;
            display: flex;
            gap: 8px;
            align-items: center;
            background: transparent; /* transparent background to blend with header */
            padding: 6px 12px;
            border-radius: 0;
            box-shadow: none;
            border: none;
            justify-content: flex-start;
            width: 100%;
        }
        .below-topnav-actions .action-btn {
            background: transparent;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.95rem;
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
        }
        .below-topnav-actions .action-btn:hover { background: rgba(11,105,255,0.06); color: var(--color-primary); }
        /* keep legacy single button hidden (no longer used) */
        .sidebar-toggle-btn { display: none !important; }
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
            .sidebar--hidden { transform: translateX(-110%); }
            .content {
                margin-left: calc(var(--account-sidebar-width) + 16px);
                padding: 12px;
            }
            /* allow content and sidebar to scroll independently on small screens */
            .sidebar { overflow-y: auto; }
            /* keep controls visible on small screens (they are fixed centered below topnav) */
            .below-topnav-actions{display:flex}
            .sidebar-overlay{display:none}
        }
        /* Mobile: hide sidebar by default, show overlay when opened */
        @media (max-width: 720px) {
            .sidebar{transform:translateX(-110%)}
            .sidebar--open{transform:translateX(0)}
            .sidebar-overlay{display:none}
            .sidebar-overlay.active{display:block}
            .content{margin-left:12px;padding:12px}
            /* Make the secondary navbar fit small screens: full-width, padded */
            .below-topnav-actions{
                position: sticky;
                background-color: white;
                left:0;
                transform:none;
                width: calc(100% - 24px);
                padding: 6px 8px;
                top: calc(var(--topnav-height) + 8px);
                justify-content: flex-start;
            }
            .below-topnav-actions .action-btn{ padding:8px 12px; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/nav/topnav.php'; ?>
    <!-- Secondary navbar (white with blue tones) below the main topnav -->
    <nav class="below-topnav-actions" role="navigation" aria-label="Barra secundaria">
        <a class="action-btn" href="/pags/micuenta.php?section=dashboard" aria-label="Ir a Dashboard">Dashboard</a>
        <a class="action-btn" href="/pags/micuenta.php?section=configuracion" aria-label="Configuración">Configuración</a>
        <a class="action-btn" href="/auth/logout.php" aria-label="Cerrar Sesión">Salir</a>
    </nav>
    <div class="container">
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
                // IMPORTANTE: Los POSTs en dashboard deben procesarse ANTES de incluir el archivo
                // para que el exit() funcione correctamente sin que micuenta.php continúe renderizando
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // Procesar POST de dashboard aquí, FUERA de la inclusión
                    include __DIR__ . '/../dashboard/dashboard.php';
                    // Si llegamos aquí, el POST fue procesado y se hizo exit en dashboard.php
                } else {
                    // GET: incluir normalmente para mostrar la página
                    include __DIR__ . '/../dashboard/dashboard.php';
                }
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
    <script>
        (function(){
            const toggle = document.getElementById('sidebarToggleBtn');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            function openSidebar(){ sidebar.classList.add('sidebar--open'); overlay.classList.add('active'); }
            function closeSidebar(){ sidebar.classList.remove('sidebar--open'); overlay.classList.remove('active'); }
            toggle && toggle.addEventListener('click', function(e){
                if (sidebar.classList.contains('sidebar--open')) closeSidebar(); else openSidebar();
            });
            overlay && overlay.addEventListener('click', closeSidebar);
            // ensure sidebar visible on resize >720
            window.addEventListener('resize', function(){ if (window.innerWidth>720){ sidebar.classList.remove('sidebar--open'); overlay.classList.remove('active'); } });
        })();
    </script>
</body>
</html>
