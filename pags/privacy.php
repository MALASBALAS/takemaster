<?php
require __DIR__ . '/../src/nav/bootstrap.php';
start_secure_session();
require_once __DIR__ . '/../src/nav/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Política de Privacidad - TAKEMASTER</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/style.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/balas.ico" type="image/x-icon">
    <style>
        .privacy-container {
            max-width: 900px;
            margin: 20px auto;
            background-color: #222;
            padding: 20px;
            border-radius: 10px;
            color: #fff;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
            line-height: 1.6;
        }

        .privacy-container h1, 
        .privacy-container h2, 
        .privacy-container h3 {
            color: #ff0000;
        }

        .privacy-container ul {
            margin-left: 20px;
        }

        .privacy-container ul li {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../src/nav/topnav.php'; ?>

    <div class="container">
        <section class="center">
            <h1>Política de Privacidad</h1>
            <p>En <b>Takemaster</b>, valoramos tu privacidad y nos comprometemos a proteger tus datos personales.</p>
        </section>
        <hr>

        <!-- Contenedor de política de privacidad -->
        <div class="privacy-container">
            <h2>1. Información recopilada</h2>
            <p>Recopilamos la siguiente información para ofrecerte un mejor servicio:</p>
            <ul>
                <li>Datos personales: Nombre, correo electrónico, y otros datos proporcionados durante el registro.</li>
                <li>Datos de uso: Información sobre cómo utilizas nuestra plataforma.</li>
                <li>Cookies: Utilizamos cookies para mejorar tu experiencia.</li>
            </ul>

            <h2>2. Uso de la información</h2>
            <p>Los datos recopilados se utilizan para:</p>
            <ul>
                <li>Proporcionar y personalizar nuestros servicios.</li>
                <li>Comunicarnos contigo en relación a tu cuenta o consultas.</li>
                <li>Mejorar la funcionalidad de la plataforma.</li>
            </ul>

            <h2>3. Compartición de información</h2>
            <p>No compartimos tus datos personales con terceros, excepto en los siguientes casos:</p>
            <ul>
                <li>Cuando es requerido por la ley.</li>
                <li>Con tu consentimiento explícito.</li>
            </ul>

            <h2>4. Seguridad de los datos</h2>
            <p>Implementamos medidas técnicas y organizativas para proteger tu información personal contra accesos no autorizados, pérdida o robo.</p>

            <h2>5. Tus derechos</h2>
            <p>Como usuario, tienes los siguientes derechos:</p>
            <ul>
                <li>Acceder a tus datos personales.</li>
                <li>Solicitar la corrección de datos inexactos.</li>
                <li>Solicitar la eliminación de tus datos.</li>
            </ul>

            <h2>6. Cambios en esta política</h2>
            <p>Podemos actualizar esta política de privacidad en cualquier momento. Notificaremos cualquier cambio importante a través de la plataforma o por correo electrónico.</p>

            <h2>7. Contacto</h2>
            <p>Si tienes preguntas sobre esta política de privacidad, contáctanos a través de nuestra página de <a href="<?= BASE_URL ?>/pags/contact.php">Contacto</a>.</p>
        </div>
    </div>

    <?php include __DIR__ . '/../src/nav/footer.php'; ?>
</body>
</html>
