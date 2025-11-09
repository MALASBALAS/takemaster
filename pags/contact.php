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
    <title>Contacto - TAKEMASTER</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/balas.ico" type="image/x-icon">
   
</head>

<body>
    <?php include __DIR__ . '/../src/nav/topnav.php'; ?>

    <div class="container">
        <section class="center">
            <h1>Contacto</h1>
            <p>Si tienes alguna pregunta o necesitas más información sobre <b>Takemaster</b>, no dudes en contactarnos.</p>
        </section>
        <hr>

        <!-- Formulario de contacto -->
        <section>
            <form id="contact-form" class="contact-form">
                <h2>Contáctanos</h2>

                <label for="name">Nombre:</label>
                <input type="text" id="name" name="name" placeholder="Tu nombre" required>

                <label for="email">Correo electrónico:</label>
                <input type="email" id="email" name="email" placeholder="Tu correo" required>

                <label for="message">Mensaje:</label>
                <textarea id="message" name="message" placeholder="Escribe tu mensaje aquí..." required></textarea>

                <br><hr><br>

                <h2 class="center">Tipo:</h2>
                <div class="row">
                    <div class="column left" >
                        <h3 class="center">Soporte</h3>
                        <p class="center">
                            <input type="checkbox" id="soporte" name="interes[]" value="Soporte">
                        </p>
                    </div>
                    <div class="column middle" >
                        <h3 class="center">Colaboración</h3>
                        <p class="center">
                            <input type="checkbox" id="colaboracion" name="interes[]" value="Colaboración">
                        </p>
                    </div>
                    <div class="column right" >
                        <h3 class="center">Sugerencias</h3>
                        <p class="center">
                            <input type="checkbox" id="sugerencias" name="interes[]" value="Sugerencias">
                        </p>
                    </div>
                </div>

                <br><hr><br>
                <h2 class="center">Prioridad:</h2>
                <div class="row">
                    <div class="column left" >
                        <h3 class="center">Alta</h3>
                        <p class="center">
                            <input type="radio" id="alta" name="prioridad" value="Alta" required>
                        </p>
                    </div>
                    <div class="column middle" >
                        <h3 class="center">Media</h3>
                        <p class="center">
                            <input type="radio" id="media" name="prioridad" value="Media">
                        </p>
                    </div>
                    <div class="column right" >
                        <h3 class="center">Baja</h3>
                        <p class="center">
                            <input type="radio" id="baja" name="prioridad" value="Baja">
                        </p>
                    </div>
                </div>

                <br><hr><br>

                <!-- Botón de envío -->
                <button type="submit">Enviar</button>
            </form>
        </section>
    </div>

    <?php include __DIR__ . '/../src/nav/footer.php'; ?>

    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <h2>Mensaje enviado</h2>
            <p>Gracias por contactarnos. Nos pondremos en contacto contigo lo antes posible.</p>
            <button class="close-modal" onclick="closeModal()">Cerrar</button>
        </div>
    </div>

    <script>
        // Mostrar PopUp falso de mensaje enviado
        document.getElementById('contact-form').addEventListener('submit', function(event) {
            event.preventDefault();
            document.getElementById('modal').style.display = 'flex';
        });

        // Cerrar el popup
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }
    </script>
</body>
</html>
