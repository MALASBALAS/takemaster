<?php
require __DIR__ . '/src/nav/bootstrap.php';
start_secure_session();
require_once __DIR__ . '/src/nav/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>INICIO - TAKEMASTER</title>
    <link rel="stylesheet" href="src\css\styles.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/src/img/favicon.png" type="image/png">
  
</head>

<body>
<div>
<dialog id="cookie-popup" role="dialog" aria-modal="true" aria-labelledby="cookie-title" aria-describedby="cookie-desc">
  <div class="cookie-content" role="document">
    <h3 id="cookie-title">Uso de Cookies</h3>
    <p id="cookie-desc">Esta página usa <strong>cookies</strong> para mejorar tu experiencia. ¿Deseas continuar?</p>

    <div class="cookie-actions">
      <button class="btn-accept" id="accept-button" type="button">Aceptar</button>
      <button class="btn-exit" id="exit-button" type="button">Salir</button>
    </div>
  </div>
</dialog>
</div>

    <?php include __DIR__ . '/src/nav/topnav.php'; ?>

    <div class="container">
        <!-- Contenido principal -->
     <section class="center">
    <h2>Optimiza tu gestión profesional como actor</h2>
   
    <p><strong>Registra tus jornadas laborales y proyecta tus ingresos</strong> con <i>Takemaster</i>, la plataforma especializada para <b>actores de doblaje en España</b>.</p>
    <a href="<?= BASE_URL ?>/auth/register.php" class="cta center">Comienza hoy</a>
</section>
<hr>

<section class="values-section">
    <div class="values-container">
        <div class="value-card">
            <h2>Nuestra misión</h2>
            <p>Desarrollamos una <b>herramienta intuitiva</b> que pone al profesional en el centro de su carrera.</p>
        </div>
        <div class="value-card">
            <h2>Agilidad y simplicidad</h2>
            <p>Introduce tus datos de forma rápida y sin complicaciones, maximizando tu productividad.</p>
            <a href="#NuestrosServicios" class="cta">Conoce más</a>
        </div>
        <div class="value-card">
            <h2>Gestión integral</h2>
            <p>Supervisa tus jornadas laborales y tus ingresos con precisión para una planificación estratégica.</p>
            <a href="/pags/contact.php" class="cta">Más información</a>
        </div>
    </div>
</section>
<hr>

<section class="center">
    <h2>De una necesidad, nace una solución</h2>
    <p>«Papá, he perdido la agenda y no sé qué días trabajo ni cuántos takes he hecho este mes»</p>
    <p><sub>- Álvaro Balas</sub></p>
</section>
<hr>

<div class="center">
    <h2>¿Cómo funciona el panel?</h2>
    <p>El panel está diseñado para ser claro y funcional, basado en el modelo 50/30/20, con acceso a estadísticas y gráficos privados.</p>
    <br>
</div>
<hr>



<section class="center">
    <h2>Nuestra historia</h2>
    <p>Takemaster es una página web de organización de tareas <u>creada por un actor para actores</u>, que permite registrar los días trabajados y los ingresos esperados, con un diseño sencillo y minimalista. Facilita la organización mensual y anual desde cualquier lugar.</p>
    <a href="/pags/about.php" class="cta">Conócenos</a>
</section>
<hr>

<section class="center" id="NuestrosServicios">
    <h2>Servicios principales</h2>
    <p>Descubre en qué destacamos y organiza tus jornadas laborales y tus ingresos estimados.</p>
    <div class="services-container">
        <div class="service-card">
            <h2>Registro diario</h2>
            <p>Registra tu actividad diaria de forma rápida y precisa para un control efectivo.</p>
        </div>
        <div class="service-card">
            <h2>Planificación</h2>
            <p>Organiza tu trabajo mensual y anual para un seguimiento eficiente de tus actividades.</p>
        </div>
        <div class="service-card">
            <h2>Proyección de ingresos</h2>
            <p>Calcula tus ganancias esperadas de forma automática y precisa.</p>
        </div>
    </div>
</section>
<hr>

<section class="center">
    <h2>Únete a Takemaster</h2>
    <p>Empieza a gestionar tu carrera como actor con Takemaster y alcanza tus metas profesionales.</p>
    <a href="<?= BASE_URL ?>/auth/register.php" class="cta">Comienza hoy</a>
</section>
<br>
    </div>
    <?php include __DIR__ . '/src/nav/footer.php'; ?>
</body>
<script>
    // Cookie consent logic with localStorage persistence
    window.addEventListener('DOMContentLoaded', () => {
        const popup = document.getElementById("cookie-popup");
        const acceptBtn = document.getElementById("accept-button");
        const exitBtn = document.getElementById("exit-button");
        
        // Check if user already responded
        const cookieConsent = localStorage.getItem('cookieConsent');
        
        if (!cookieConsent) {
            // Show dialog only if no previous response
            popup.showModal();
        }
        
        // Accept button: save consent and close
        acceptBtn.addEventListener('click', () => {
            localStorage.setItem('cookieConsent', 'accepted');
            popup.close();
        });
        
        // Exit button: save rejection and redirect or close
        exitBtn.addEventListener('click', () => {
            localStorage.setItem('cookieConsent', 'rejected');
            popup.close();
            // Optional: redirect user away if they reject
            // window.location.href = 'https://www.google.com';
        });
        
        // ESC key also closes (default <dialog> behavior already handles this)
        popup.addEventListener('close', () => {
            // Dialog closed by any means
        });
    });
</script>
</html>
