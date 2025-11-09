<?php
// `footer.php` emite intencionadamente solo el fragmento del pie de página.
// Cuando se requiera el wrapper de página completo (acceso directo), definir
// RENDER_FULL_PAGE y las etiquetas de cierre correspondientes se emitirán
// después del footer.
// Incluir Font Awesome y estilos solo si no están ya presentes en la página
// padre (evita duplicar enlaces a recursos).
?>
<footer class="footer">
    <hr>
    <div class="footer-container">
        <!-- Primera fila -->
        <div class="footer-row">
            <!-- Bloque 1: Redes sociales -->
            <div class="footer-column">
                <h3>Síguenos</h3>
                <div class="social-icons">
                    <a href="https://x.com/alvaro_balas"><i class="fa fa-twitter"></i></a>
                    <a href="https://www.instagram.com/alvaro._.balas/"><i class="fa fa-instagram"></i></a>
                </div>
            </div>
            <!-- Bloque 2: Enlaces útiles -->
            <div class="footer-column">
                <h3>Enlaces útiles</h3>
                <ul class="enlaces">
                    <li><a href="/pags/about.php">Sobre nosotros</a></li>
                    <li><a href="/pags/contact.php">Contacto</a></li>
                    <li><a href="/pags/privacy.php">Política de privacidad</a></li>
                    <li><a href="/pags/terms.php">Términos y condiciones</a></li>
                </ul>
            </div>
        </div>

        <!-- Segunda fila -->
        <div class="footer-row footer-bottom">
            <p>&copy; 2024 Takemaster. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>

<?php if (defined('RENDER_FULL_PAGE') && RENDER_FULL_PAGE): ?>
    </body>
    </html>
<?php endif; ?>
