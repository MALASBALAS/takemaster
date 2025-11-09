<?php
require_once __DIR__ . '/bootstrap.php';
start_secure_session();

// Evitar el renderizado doble cuando el archivo se incluye varias veces
if (defined('TOPNAV_INCLUDED')) {
    return;
}
define('TOPNAV_INCLUDED', true);

// Comprobación de inicio de sesión
if (isset($_SESSION['username'])) {
    $username = htmlspecialchars($_SESSION['username']);
    $mensaje = "Mi cuenta <svg xmlns=\"http://www.w3.org/2000/svg\" height=\"1em\" viewBox=\"0 0 320 512\"><style>svg{fill:#ffffff}</style><path d=\"M137.4 374.6c12.5 12.5 32.8 12.5 45.3 0l128-128c9.2-9.2 11.9-22.9 6.9-34.9s-16.6-19.8-29.6-19.8L32 192c-12.9 0-24.6 7.8-29.6 19.8s-2.2 25.7 6.9 34.9l128 128z\"></path></svg>";
    $boton = "<button class=\"dropbtn\">$mensaje</button>";
    $boton .= "<div class=\"dropdown-content\">";
    $boton .= "<a href=\"/pags/micuenta.php?section=configuracion\">$username</a>";
    $boton .= "<a href=\"/auth/logout.php\">Cerrar Sesión</a>";
    $boton .= "</div>";
} else {
    $mensaje = "Iniciar sesión";
    $boton = "<button onclick=\"location.href='/auth/login.php'\" class=\"dropbtn\">$mensaje</button>";
}
?>

<?php
// Si la constante RENDER_FULL_PAGE está definida y es true, emitir el wrapper
// HTML completo (útil cuando se accede directamente a este archivo). Cuando se
// incluye desde otras páginas (uso normal), solo emitir el fragmento de
// navegación para evitar etiquetas DOCTYPE/HEAD/BODY duplicadas.
$renderFull = defined('RENDER_FULL_PAGE') && RENDER_FULL_PAGE;
if ($renderFull) :
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Nav</title>
</head>
<body>
<?php endif; ?>

<!-- Menu Arriba -->
<nav class="topnav" role="navigation" aria-label="Navegación principal">
    <button class="menu-toggle" id="main-menu-toggle" aria-expanded="false" aria-controls="main-menu" aria-label="Abrir menú" type="button">
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
    </button>
    <div class="menu-links" id="main-menu">
        <!-- Botones izquierda -->
        <a href="/index.php">Inicio</a>
        <a href="/pags/about.php">Sobre nosotros</a>
        <a href="/pags/contact.php">Contacto</a>
    </div>
    <!-- Botones derecha -->
    <div class="topnav-right" role="region" aria-label="Acciones de usuario">
        <div class="dropdown">
            <?php echo $boton; ?>
        </div>
    </div>
</nav>


<!-- Imagen header -->
<div class="header">
    <img onclick="location.href='/index.php'" src="/img/TAKEMASTER2.png" alt="Logotipo de TAKEMASTER" width="50" height="50">
    <h1>TAKEMASTER</h1>
</div>
<!-- Boton inicio de sesion, cambiar si esta iniciado sesion o no -->
<script>
(function() {
    'use strict';
    
    function toggleMenu(e) {
        if (e) e.preventDefault();
        const menu = document.getElementById('main-menu');
        const toggle = document.getElementById('main-menu-toggle');
        
        console.log('Toggle clicked!', menu, toggle); // Debug
        
        if (menu) {
            menu.classList.toggle('active');
            if (toggle) {
                const isExpanded = menu.classList.contains('active');
                toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
                console.log('Menu is now:', isExpanded ? 'OPEN' : 'CLOSED'); // Debug
            }
        }
    }
    
    // Wire the button when DOM is ready
    function init() {
        const toggleBtn = document.getElementById('main-menu-toggle');
        if (toggleBtn) {
            console.log('Menu toggle button found and wired!'); // Debug
            toggleBtn.addEventListener('click', toggleMenu);
        } else {
            console.warn('Menu toggle button NOT found!'); // Debug
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php if ($renderFull) : ?>
</body>
</html>
<?php endif; ?>
