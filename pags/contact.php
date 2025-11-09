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
    <link rel="shortcut icon" href="<?= BASE_URL ?>/src/img/favicon.png" type="image/png">
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
        <section class="contact-section">
            <form id="contact-form" class="contact-form" novalidate>
                <h2>Envíanos un mensaje</h2>

                <!-- Nombre -->
                <div class="form-group">
                    <label for="name">Nombre completo *</label>
                    <input type="text" id="name" name="name" placeholder="Tu nombre completo" required>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Correo electrónico *</label>
                    <input type="email" id="email" name="email" placeholder="tu@email.com" required>
                </div>

                <!-- Teléfono -->
                <div class="form-group">
                    <label for="phone">Teléfono</label>
                    <input type="tel" id="phone" name="phone" placeholder="+34 xxx xxx xxx" inputmode="tel">
                </div>

                <!-- Mensaje -->
                <div class="form-group">
                    <label for="message">Mensaje *</label>
                    <textarea id="message" name="message" placeholder="Cuéntanos en qué estás interesado y cómo podemos ayudarte..." rows="5" required></textarea>
                </div>

                <!-- Tipo de consulta -->
                <div class="form-section">
                    <h3>Tipo de consulta</h3>
                    <div class="row">
                        <div class="column left">
                            <label for="soporte">
                                <input type="checkbox" id="soporte" name="tipo[]" value="Soporte">
                                <span>Soporte</span>
                            </label>
                        </div>
                        <div class="column middle">
                            <label for="colaboracion">
                                <input type="checkbox" id="colaboracion" name="tipo[]" value="Colaboración">
                                <span>Colaboración</span>
                            </label>
                        </div>
                        <div class="column right">
                            <label for="sugerencias">
                                <input type="checkbox" id="sugerencias" name="tipo[]" value="Sugerencias">
                                <span>Sugerencias</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Prioridad -->
                <div class="form-section">
                    <h3>Prioridad</h3>
                    <div class="row">
                        <div class="column left">
                            <label for="alta">
                                <input type="radio" id="alta" name="prioridad" value="Alta">
                                <span>Alta</span>
                            </label>
                        </div>
                        <div class="column middle">
                            <label for="media">
                                <input type="radio" id="media" name="prioridad" value="Media" checked>
                                <span>Media</span>
                            </label>
                        </div>
                        <div class="column right">
                            <label for="baja">
                                <input type="radio" id="baja" name="prioridad" value="Baja">
                                <span>Baja</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Feedback mensaje -->
                <div id="form-feedback" class="form-feedback" style="display: none;"></div>

                <!-- Botón de envío -->
                <button type="submit" id="submit-btn" class="btn-submit">Enviar Mensaje</button>
            </form>
        </section>
    </div>

    <?php include __DIR__ . '/../src/nav/footer.php'; ?>

    <!-- Modal (oculto por defecto) -->
    <div id="modal" class="modal" style="display: none;">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <h2 id="modal-title">Mensaje enviado</h2>
            <p id="modal-message">Gracias por contactarnos. Nos pondremos en contacto contigo lo antes posible.</p>
            <button class="close-modal" onclick="closeModal()">Cerrar</button>
        </div>
    </div>

    <script>
        const form = document.getElementById('contact-form');
        const feedback = document.getElementById('form-feedback');
        const submitBtn = document.getElementById('submit-btn');
        const modal = document.getElementById('modal');

        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            // Limpiar feedback anterior
            feedback.style.display = 'none';
            feedback.className = 'form-feedback';
            
            // Cambiar estado del botón
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';

            try {
                // Recopilar datos del formulario
                const formData = new FormData(form);
                const data = Object.fromEntries(formData);

                // Aquí iría la llamada a tu backend
                // const response = await fetch('<?php echo BASE_URL; ?>/funciones/enviar_contacto.php', {
                //     method: 'POST',
                //     headers: { 'Content-Type': 'application/json' },
                //     body: JSON.stringify(data)
                // });

                // Por ahora simulamos éxito
                showSuccess('Mensaje enviado correctamente. Te contactaremos pronto.');
                form.reset();

            } catch (error) {
                showError('Hubo un problema al enviar tu mensaje. Inténtalo de nuevo.');
                console.error('Error:', error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar Mensaje';
            }
        });

        function showSuccess(message) {
            feedback.className = 'form-feedback success';
            feedback.textContent = message;
            feedback.style.display = 'block';
            
            // Mostrar modal después de 500ms
            setTimeout(() => {
                document.getElementById('modal-title').textContent = 'Mensaje enviado';
                document.getElementById('modal-message').textContent = message;
                modal.style.display = 'flex';
            }, 500);
        }

        function showError(message) {
            feedback.className = 'form-feedback error';
            feedback.textContent = message;
            feedback.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    </script>
</body>
</html>
