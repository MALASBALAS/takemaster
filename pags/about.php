<?php
require __DIR__ . '/../src/nav/bootstrap.php';
start_secure_session();
require_once __DIR__ . '/../src/nav/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sobre nosotros - TAKEMASTER</title>
     <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/balas.ico" type="image/x-icon">
   
</head>

<body>
    <?php include __DIR__ . '/../src/nav/topnav.php'; ?>

    <div class="container">
        <section class="center">
            <h1>Sobre nosotros</h1>
            <p>Conoce más acerca de <b>Takemaster</b>, la herramienta diseñada para <i>actores de doblaje</i>.</p>
        </section>
        <hr>

        <section class="center about-list">
            <h2>Nuestra misión</h2>
            <p>En <b>Takemaster</b>, buscamos ofrecer una plataforma <u>eficiente y sencilla</u> que permita a los actores de doblaje:</p>
            <ul>
                <li>Organizar sus días de trabajo.</li>
                <li>Calcular sus ingresos esperados.</li>
                <li>Gestionar sus actividades de manera profesional.</li>
            </ul>
        </section>
        <hr>

        <section class="center">
            <div class="about-column">
                <h2>Nuestro equipo</h2>
                <p>
                    Somos un grupo de <b>profesionales apasionados</b> por el arte del doblaje y la tecnología. Trabajamos para hacer tu vida laboral más sencilla.
                </p>
            </div>
            <div class="about-column">
                <h2>Nuestra visión</h2>
                <p>
                    Queremos ser la plataforma líder en la gestión de tareas y ganancias para actores de doblaje, combinando simplicidad, funcionalidad y diseño.
                </p>
            </div>
        </section>
        <hr>

        <section class="about-history">
    <div class="about-history-text">
        <h2 class="center">Nuestra historia</h2>
        <p>Takemaster nació por una necesidad mía, <b>Álvaro Balas</b>, actor de doblaje que necesitaba una herramienta que:</p>
        <ul>
            <li>Agilizará el cálculo de ingresos.</li>
            <li>Facilitará la organización del trabajo mensual.</li>
            <li>Permitiera gestionar las actividades de doblaje desde cualquier dispositivo.</li>
        </ul>
        <p>Desde su lanzamiento, hemos ayudado a decenas de actores a reorganizarse mejor mensualmente.</p>
    </div>
    <img src="<?= BASE_URL ?>/img/DSC0075_512.jpg" alt="Álvaro Balas">
</section>
        <hr>

        <section class="center">
            <h2>¿Listo para unirte a Takemaster?</h2>
            <p>Organiza tu carrera como actor de doblaje y lleva tu trabajo al siguiente nivel.</p>
            <a href="<?= BASE_URL ?>/auth/register.php" class="cta">¡Empieza ahora!</a>
        </section>
        <br>
    </div>

    <?php include __DIR__ . '/../src/nav/footer.php'; ?>
</body>
</html>
