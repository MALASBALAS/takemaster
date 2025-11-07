<?php
require __DIR__ . '/src/nav/bootstrap.php';
start_secure_session();
require_once __DIR__ . '/src/nav/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>INICIO - TAKEMASTER</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/styles.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/balas.ico" type="image/x-icon">
</head>

<body>
<div>
    <dialog id="cookie-popup">
        <p>Esta página web usa <b>Cookies</b> para una mejor experiencia con el usuario.</p>
        <p>¿Desea continuar?</p>
        <form method="dialog">
            <button id="accept-button"><u>Aceptar</u></button>
            <a href="https://thebalasfamily.com/"><button type="button">Salir</button></a>
        </form>
    </dialog>
</div>

    <?php include __DIR__ . '/src/nav/topnav.php'; ?>

    <div class="container">
        <!-- Contenido principal -->
        <section class="center">
            <h2>Organiza tu trabajo como actor</h2>
            <p><s>¿Cuantos takes hice a comienzo del mes?</s></p>
            <p><strong>Contabiliza tus días laborales y ganancias</strong> esperadas con <i>Takemaster</i>, la web para <b>actores de doblaje en España</b>.</p>
            <a href="<?= BASE_URL ?>/auth/register.php" class="cta center">Empieza ahora</a>
        </section>
        <hr>
        <section>
            <div class="row">
                <div class="column left" style="background-color:#111;">
                    <h2 class="center">¿Qué es lo que buscamos?</h2>
                    <p class="center">Queremos ofrecer una <b>herramienta fácil</b>, sencilla, en la que tú seas el protagonista de la historia.</p>
                </div>
                <div class="column middle" style="background-color:#222;">
                    <h2 class="center">Rápido y sencillo</h2>
                    <p class="center">Registra tus datos de forma ágil y sin complicaciones, optimizando tu tiempo de trabajo.</p>
                    <a href="#NuestrosServicios" class="cta center">Más información</a>
                </div>
                <div class="column right" style="background-color:#111;">
                    <h2 class="center">Control total</h2>
                    <p>Mantén un control detallado de tus días laborales y ganancias para una gestión eficaz de tu carrera.</p>
                    <a href="/pags/contact.php" class="cta">Más información</a>
                </div>
            </div>
        </section>
        <hr>

        <section class="center">
            <h2>Una idea, una solución</h2>
            <p>«Papá, he perdido la agenda y no sé qué días trabajo ni cuántos takes he hecho en el mes»</p>
            <p><sub>- Álvaro Balas</sub></p>
        </section>
        <hr>
        <div class="center">
            <h2>¿Como se ve el panel?</h2>
            <p>El panel es un panel sencillo y facil de usar pensado como una tabla 50/30/20, con posibilidad de estadisticas y graficos privada.</p>
            <br>
            <img src="/src/img/firstlook.png" alt="Imagen del primer vistazo de la app web.", width="70%">
        </div>
        <hr>

        <section class="center">
            <h2>Nuestra historia</h2>
            <p>Takemaster es una página web de organización de tareas <u>creada por un actor para actores</u>, que permite contabilizar los días trabajados y los ingresos esperados, con un diseño sencillo y minimalista. Con una organización mensual y anual mucho más sencilla y desde cualquier parte.</p>
            <a href="/pags/about.php" class="cta">¡Conócenos!</a>
        </section>
        <hr>

        <section class="center" id="NuestrosServicios">
            <h2>Nuestros Servicios Principales</h2>
            <p>Descubre en qué destacamos y ayúdate a organizar tus días de trabajo y ganancias esperadas.</p>
            <div class="row">
                <div class="column left" style="background-color:#111;">
                    <h2 class="center">Diario</h2>
                    <p class="center">Registra tu actividad diaria de manera rápida y sencilla para un control preciso.</p>
                </div>
                <div class="column middle" style="background-color:#222;">
                    <h2 class="center">Organización</h2>
                    <p class="center">Organiza tu trabajo mensual y anual para un seguimiento eficiente de tus actividades.</p>
                </div>
                <div class="column right" style="background-color:#111;">
                    <h2 class="center">Calculadora</h2>
                    <p>Calcula tus ganancias esperadas de forma automática y precisa.</p>
                </div>
            </div>
        </section>
        <hr>

        <section class="center">
            <h2>Únete a Takemaster</h2>
            <p>Empieza a organizar tu carrera como actor con Takemaster y alcanza tus metas profesionales.</p>
            <a href="<?= BASE_URL ?>/auth/register.php" class="cta">Empieza ahora</a>
        </section>
        <br>
    </div>
    <?php include __DIR__ . '/src/nav/footer.php'; ?>
</body>
<script>
    // Mostrar el popup al cargar la página
    window.onload = () => {
        const popup = document.getElementById("cookie-popup");
        popup.showModal();
    };
</script>
</html>
