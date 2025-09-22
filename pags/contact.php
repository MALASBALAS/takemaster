<?php
require __DIR__ . '/../src/nav/bootstrap.php';
start_secure_session();
require_once __DIR__ . '/../src/nav/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contacto - TAKEMASTER</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/style.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/balas.ico" type="image/x-icon">
    <style>
        .contact-form {
            max-width: 600px;
            margin: 20px auto;
            background-color: #222;
            padding: 20px;
            border-radius: 10px;
            color: #fff;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
        }

        .contact-form h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .contact-form label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .contact-form input[type="text"],
        .contact-form input[type="email"],
        .contact-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #181818;
            color: #fff;
        }

        .contact-form textarea {
            height: 100px;
            resize: none;
        }

        .contact-form button {
            width: 100%;
            padding: 10px;
            background-color: red;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }

        .contact-form button:hover {
            background-color: #ff5555;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: #222;
            padding: 20px;
            border-radius: 10px;
            color: #fff;
            text-align: center;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
        }

        .modal-content h2 {
            margin-bottom: 20px;
        }

        .close-modal {
            padding: 10px 20px;
            background-color: red;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .close-modal:hover {
            background-color: #ff5555;
        }
    </style>
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
                    <div class="column left" style="background-color:#111;">
                        <h3 class="center">Soporte</h3>
                        <p class="center">
                            <input type="checkbox" id="soporte" name="interes[]" value="Soporte">
                        </p>
                    </div>
                    <div class="column middle" style="background-color:#222;">
                        <h3 class="center">Colaboración</h3>
                        <p class="center">
                            <input type="checkbox" id="colaboracion" name="interes[]" value="Colaboración">
                        </p>
                    </div>
                    <div class="column right" style="background-color:#111;">
                        <h3 class="center">Sugerencias</h3>
                        <p class="center">
                            <input type="checkbox" id="sugerencias" name="interes[]" value="Sugerencias">
                        </p>
                    </div>
                </div>

                <br><hr><br>
                <h2 class="center">Prioridad:</h2>
                <div class="row">
                    <div class="column left" style="background-color:#111;">
                        <h3 class="center">Alta</h3>
                        <p class="center">
                            <input type="radio" id="alta" name="prioridad" value="Alta" required>
                        </p>
                    </div>
                    <div class="column middle" style="background-color:#222;">
                        <h3 class="center">Media</h3>
                        <p class="center">
                            <input type="radio" id="media" name="prioridad" value="Media">
                        </p>
                    </div>
                    <div class="column right" style="background-color:#111;">
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
