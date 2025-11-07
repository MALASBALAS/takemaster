<?php
require_once __DIR__ . '/bootstrap.php';
start_secure_session();

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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Nav</title>
</head>
<body>
    <!-- Menu Arriba -->
    <div class="topnav">
    <div class="menu-toggle" onclick="toggleMenu()">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="menu-links">
        <!-- Botones izquierda -->
        <a href="/index.php">Inicio</a>
        <a href="/pags/about.php">Sobre nosotros</a>
        <a href="/pags/contact.php">Contacto</a>
    </div>
    <!-- Botones derecha -->
    <div class="topnav-right">
        <div class="dropdown">
            <?php echo $boton; ?>
        </div>
    </div>
</div>


    <!-- Imagen header -->
    <div class="header">
        <img onclick="location.href='/index.php'" src="/img/TAKEMASTER2.png" alt="Logotipo de TAKEMASTER" width="50" height="50">
        <h1>TAKEMASTER</h1>
    </div>
    <!-- Boton inicio de sesion, cambiar si esta iniciado sesion o no -->
    <script>
        function toggleMenu() {
            const menu = document.querySelector('.menu-links');
            menu.classList.toggle('active');
        }
    </script>
</body>
</html>
