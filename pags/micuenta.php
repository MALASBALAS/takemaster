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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi cuenta</title>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/src/img/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
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
                // Si el archivo del dashboard está disponible, incluirlo; de lo contrario renderizar un fallback simple
                            $dashboardFile = __DIR__ . '/../dashboard/dashboard.php';
                            if (is_readable($dashboardFile)) {
                                include $dashboardFile;
                            } else {
                                // Fallback: render a minimal dashboard block so the user sees content
                                ?>
                                <div class="content">
                                    <h2>Bienvenido, <?php echo htmlspecialchars($username); ?></h2>

                                    <meta charset="UTF-8">
                                    <title>Dashboard - Mi cuenta</title>
                                    <link rel="stylesheet" href="<?php echo rtrim(BASE_URL, '/'); ?>/src/css/styles.css">
                                    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <style>
                                        /* Minimal local styles copied from dashboard in case include is missing */
                                        .container { display:block; width:100%; box-sizing:border-box; padding:8px 12px; }
                                        .content { width:100%; max-width:calc(100% - var(--account-sidebar-width,0px) - 24px); padding:18px; box-sizing:border-box; margin:0 auto; background:transparent }
                                        .plantillas-lista { list-style:none; padding:0; margin:0; display:grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap:12px }
                                        .plantillas-item { background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.02)); padding:12px; border-radius:8px }
                                    </style>

                                    <div class="container">
                                    <div class="content">
                                        <h3>Crear Plantilla</h3>
                                        <form method="post">
                                            <?php echo csrf_input(); ?>
                                            <input type="text" name="nombre_plantilla" placeholder="Nombre de la plantilla" required>
                                            <button type="submit" name="crear_plantilla" class="btn-crear-plantilla">Crear Nueva Plantilla</button>
                                        </form>
                                        <h3>Plantillas</h3>
                                        <ul class="plantillas-lista">
                                        <!-- no plantillas available fallback -->
                                        </ul>

                                        <!-- Popup para compartir (fallback) -->
                                        <div id="popupCompartir" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border:1px solid #ccc; z-index:1000;">
                                            <h3>Compartir Plantilla</h3>
                                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <input type="email" id="email<?php echo $i; ?>" placeholder="Email <?php echo $i; ?>" <?php echo $i===1? 'required':''; ?>>
                                            <?php endfor; ?>
                                            <button onclick="compartirPlantilla()">Compartir</button>
                                            <button onclick="cerrarPopup()">Cerrar</button>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                                <script>
                                function abrirPopup(plantillaId){ document.getElementById('popupCompartir').style.display='block'; window.currentPlantillaId=plantillaId; }
                                function cerrarPopup(){ document.getElementById('popupCompartir').style.display='none'; }
                                function compartirPlantilla(){
                                    const emails=[]; for(let i=1;i<=10;i++){const e=document.getElementById('email'+i).value; if(e) emails.push(e);} 
                                    const xhr=new XMLHttpRequest(); xhr.open('POST','<?php echo rtrim(BASE_URL, '/'); ?>/plantillas/compartir_plantilla.php', true); xhr.setRequestHeader('Content-Type','application/json;charset=UTF-8');
                                    const meta=document.querySelector('meta[name="csrf-token"]'); if(meta) xhr.setRequestHeader('X-CSRF-Token', meta.getAttribute('content'));
                                    xhr.onload=function(){ 
                                        if(xhr.status===200){
                                            // Prefer the global Notice component when available
                                            if (window.Notice && typeof window.Notice.show === 'function') {
                                                window.Notice.show('success','Plantilla compartida con éxito.', 3000);
                                            } else {
                                                // Fallback inline banner near the popup
                                                let b = document.getElementById('share-inline-banner');
                                                if (!b) { b = document.createElement('div'); b.id = 'share-inline-banner'; b.style.position = 'fixed'; b.style.top = '12%'; b.style.left = '50%'; b.style.transform = 'translateX(-50%)'; b.style.zIndex = 1200; b.style.padding = '10px 14px'; b.style.borderRadius = '8px'; b.style.background = '#e6ffed'; b.style.color = '#064e3b'; document.body.appendChild(b); }
                                                b.textContent = 'Plantilla compartida con éxito.';
                                                setTimeout(function(){ if (b && b.parentNode) b.parentNode.removeChild(b); }, 3000);
                                            }
                                            cerrarPopup();
                                        } else {
                                            if (window.Notice && typeof window.Notice.show === 'function') {
                                                window.Notice.show('error','Error al compartir la plantilla.', 4000);
                                            } else {
                                                let b = document.getElementById('share-inline-banner');
                                                if (!b) { b = document.createElement('div'); b.id = 'share-inline-banner'; b.style.position = 'fixed'; b.style.top = '12%'; b.style.left = '50%'; b.style.transform = 'translateX(-50%)'; b.style.zIndex = 1200; b.style.padding = '10px 14px'; b.style.borderRadius = '8px'; b.style.background = '#fff5f5'; b.style.color = '#721c24'; document.body.appendChild(b); }
                                                b.textContent = 'Error al compartir la plantilla.';
                                                setTimeout(function(){ if (b && b.parentNode) b.parentNode.removeChild(b); }, 4000);
                                            }
                                        }
                                    }
                                    xhr.onerror = function(){ 
                                        if (window.Notice && typeof window.Notice.show === 'function') { 
                                            window.Notice.show('error','Error de red al compartir la plantilla.',4000); 
                                        } else { 
                                            let b = document.getElementById('share-inline-banner');
                                            if (!b) { b = document.createElement('div'); b.id = 'share-inline-banner'; b.style.position = 'fixed'; b.style.top = '12%'; b.style.left = '50%'; b.style.transform = 'translateX(-50%)'; b.style.zIndex = 1200; b.style.padding = '10px 14px'; b.style.borderRadius = '8px'; b.style.background = '#fff5f5'; b.style.color = '#721c24'; document.body.appendChild(b); }
                                            b.textContent = 'Error de red al compartir la plantilla.';
                                            setTimeout(function(){ if (b && b.parentNode) b.parentNode.removeChild(b); }, 4000);
                                        }
                                    };
                                    xhr.send(JSON.stringify({ plantillaId: window.currentPlantillaId, emails }));
                                }
                                </script>
                                <?php
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
