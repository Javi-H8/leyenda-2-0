<!-- includes/footer.php -->
<!-- ================== PIE DE PÁGINA ================== -->
<footer id="footer" class="site-footer">
  <div class="container footer-grid">
    <!-- Columna 1: Contacto -->
    <div class="footer-col">
      <h3>CONTACTO</h3>
      <address>
        <p>+34 658 73 27 88<br>
        <a href="mailto:leyenda@leyenda.es">leyenda@leyenda.es</a></p>
      </address>
    </div>

    <!-- Columna 2: Políticas -->
    <div class="footer-col">
      <h3>POLÍTICAS</h3>
      <ul>
        <li><a href="#">Política de Envío</a></li>
        <li><a href="#">Política de Devoluciones</a></li>
        <li><a href="#">Política de Cookies</a></li>
        <li><a href="#">Política de Privacidad</a></li>
      </ul>
    </div>

    <!-- Columna 3: Redes sociales -->
    <div class="footer-col">
      <h3>SÍGUENOS</h3>
      <ul class="social-list">
        <li><a href="#" aria-label="Instagram">Instagram</a></li>
        <li><a href="#" aria-label="TikTok">TikTok</a></li>
      </ul>
    </div>
  </div>

  <!-- Créditos finales -->
  <div class="footer-bottom">
    <p>&copy; 2025 LEYENDA</p>
  </div>
</footer>

<!-- ============================================= -->
<!-- BOTÓN FLOTANTE: siempre después del footer, pero antes de incluir los scripts -->
<div id="floating-cart" aria-label="Ver carrito">
  <svg xmlns="http://www.w3.org/2000/svg" fill="white" viewBox="0 0 24 24" aria-hidden="true">
    <path d="M7 4h-2l-3 7v2h2a3 3 0 1 0 6 0h6a3 3 0 1 0 6 0h2v-2l-3-7h-2l-1 2h-12l-1-2zm0 4h10l1.5 3h-13l1.5-3zm1 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm10 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
  </svg>
  <span id="cart-count">0</span>
</div>

<!-- === Floating Cart Modal === -->
<div id="floating-cart-modal" aria-hidden="true">
  <header>
    <span>Tu Carrito</span>
    <button id="floating-cart-close" aria-label="Cerrar">&times;</button>
  </header>
  <div class="items"></div>
  <footer>
    <button id="floating-cart-checkout">Ir a Pagar</button>
  </footer>
</div>

<!-- Incluye los scripts y el CSS nuevos -->
<link rel="stylesheet" href="/assets/css/floating-cart.css">
<script src="/assets/js/carrito.js" defer></script>
<script src="/assets/js/floating-cart.js" defer></script>
</body>
</html>
