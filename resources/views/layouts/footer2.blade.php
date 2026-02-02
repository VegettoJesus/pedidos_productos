<footer class="footer">
    <div class="footer-container">
        <div>
            <h3 class="footer-title">High Technology Innovation</h3>
            <p>Líderes en tecnología innovadora y soluciones inteligentes para el futuro.</p>
        </div>
            
        <div>
            <h3 class="footer-title">Enlaces Rápidos</h3>
            <ul class="footer-links">
                <li><a href="#" class="footer-link">Inicio</a></li>
                <li><a href="#" class="footer-link">Productos</a></li>
                <li><a href="#" class="footer-link">Ofertas</a></li>
                <li><a href="#" class="footer-link">Nosotros</a></li>
            </ul>
        </div>
            
        <div>
            <h3 class="footer-title">Contacto</h3>
            <ul class="footer-links">
                <li><a href="tel:+1234567890" class="footer-link">+1 (234) 567-890</a></li>
                <li><a href="mailto:info@hitech.com" class="footer-link">info@hitech.com</a></li>
                <li class="footer-link">Av. Tecnología 123, Ciudad Digital</li>
            </ul>
        </div>
    </div>
        
    <div class="copyright">
        &copy; 2026 High Technology Innovation. Todos los derechos reservados.
    </div>
    <script src="{{ asset('js/tiendaMain.js') }}"></script>
    @isset($script)
        <script src="{{ asset($script) }}"></script>
    @endisset
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
</footer>