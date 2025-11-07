<?php
require __DIR__ . '/../src/nav/bootstrap.php';
start_secure_session();
require_once __DIR__ . '/../src/nav/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Términos y Condiciones - TAKEMASTER</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/styles.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/balas.ico" type="image/x-icon">
    <style>
        .terms-container {
            max-width: 900px;
            margin: 20px auto;
            background-color: #222;
            padding: 20px;
            border-radius: 10px;
            color: #fff;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
            line-height: 1.6;
        }

        .terms-container h1, 
        .terms-container h2, 
        .terms-container h3 {
            color: #ff0000;
        }

        .terms-container ul {
            margin-left: 20px;
        }

        .terms-container ul li {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../src/nav/topnav.php'; ?>

    <div class="container">
        <section class="center">
            <h1>Términos y Condiciones</h1>
            <p>Lee detenidamente los términos y condiciones de uso antes de utilizar <b>Takemaster</b>.</p>
        </section>
        <hr>

        <!-- Contenedor de términos y condiciones -->
        <div class="terms-container">
            <h2>1. Introducción</h2>
            <p>Bienvenido a Takemaster. Al utilizar nuestra plataforma, aceptas cumplir con los siguientes términos y condiciones. Si no estás de acuerdo, por favor no utilices nuestros servicios.</p>

            <h2>2. Uso de la plataforma</h2>
            <p>Al registrarte, aceptas:</p>
            <ul>
                <li>No compartir tu cuenta con terceros.</li>
                <li>Proporcionar información precisa y actualizada durante el registro.</li>
                <li>Respetar las leyes aplicables al utilizar la plataforma.</li>
            </ul>

            <h2>3. Propiedad intelectual</h2>
            <p>Todos los derechos de autor, logotipos, imágenes y contenidos de Takemaster son propiedad exclusiva de Álvaro Balas y/o sus colaboradores. Queda estrictamente prohibido copiar, distribuir o utilizar nuestro contenido sin autorización previa.</p>

            <h2>4. Limitación de responsabilidad</h2>
            <p>Takemaster no se hace responsable por:</p>
            <ul>
                <li>Errores en el cálculo de tus ingresos.</li>
                <li>Interrupciones en el servicio debido a problemas técnicos.</li>
                <li>El uso indebido de la plataforma por parte de los usuarios.</li>
            </ul>

            <h2>5. Modificaciones a los términos</h2>
            <p>Nos reservamos el derecho de modificar estos términos y condiciones en cualquier momento. Te notificaremos sobre cambios importantes a través de la plataforma o tu correo electrónico registrado.</p>

            <h2>6. Contacto</h2>
            <p>Si tienes preguntas o inquietudes sobre estos términos y condiciones, contáctanos a través de nuestra página de <a href="<?= BASE_URL ?>/pags/contact.php">Contacto</a>.</p>
        </div>
    </div>

    <?php include __DIR__ . '/../src/nav/footer.php'; ?>
</body>
</html>
