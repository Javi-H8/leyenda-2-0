<?php
declare(strict_types=1);
// index.php – Página principal Leyenda 2.0

// 0. Arrancar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Incluir el header (que ahora también genera/define el CSRF_TOKEN)
include 'includes/header.php';

?>
<main>

  <!-- =============================================== -->
  <!-- HERO SLIDER + CUENTA ATRÁS -->
  <section id="slider" class="slider relative" aria-label="Presentación destacada">
    <?php
      $slides = [
        ['img'=>'assets/images/tienda-2.jpg','title'=>'EL ESPACIO LEYENDA','subtitle'=>'Visita nuestra tienda','cta'=>'VER UBICACIÓN','url'=>'#'],
        ['img'=>'assets/images/tienda-4.jpg','title'=>'AMBIENTE ÚNICO','subtitle'=>'Diseño y estilo','cta'=>'DESCÚBRELO','url'=>'/pages/tattoo.php'],
        ['img'=>'assets/images/tienda-3.jpg','title'=>'NUEVA COLECCIÓN','subtitle'=>'Primavera-Verano 2024','cta'=>'VER COLECCIÓN','url'=>'#'],
        ['img'=>'assets/images/tienda-5.jpg','title'=>'EXPERIENCIA LEYENDA','subtitle'=>'La moda que buscas','cta'=>'VER COLECCIÓN','url'=>'#'],
      ];
    ?>
    <div class="slides-wrapper">
      <?php foreach ($slides as $i => $s): ?>
        <div
          class="slide <?= $i===0?'active':''?>"
          style="background-image:url('<?=htmlspecialchars($s['img'],ENT_QUOTES,'UTF-8')?>')"
          role="img"
          aria-label="<?=htmlspecialchars($s['title'],ENT_QUOTES,'UTF-8')?>"
        >
          <div class="slide-content">
            <h1><?=htmlspecialchars($s['title'],ENT_QUOTES,'UTF-8')?></h1>
            <p><?=htmlspecialchars($s['subtitle'],ENT_QUOTES,'UTF-8')?></p>
            <a href="<?=htmlspecialchars($s['url'],ENT_QUOTES,'UTF-8')?>" class="cta" role="button">
              <?=htmlspecialchars($s['cta'],ENT_QUOTES,'UTF-8')?>
            </a>
          </div>
        </div>
      <?php endforeach;?>
    </div>
    <div id="countdown" class="countdown" aria-live="polite"></div>
  </section>

  <!-- =============================================== -->
  <!-- CATEGORÍAS DESTACADAS -->
  <section id="categories" class="categories container" aria-label="Categorías">
    <div class="categories-grid">
      <?php
          $cats = [
            [
              'img'  => 'assets/images/sample-1.JPG',
              'name' => 'Camisas',
              'url'  => 'pages/productos.php?cat=camisas'      // Aquí vamos a productos.php
            ],
            [
              'img'  => 'assets/images/sample-3.jpg',
              'name' => 'Camisetas',
              'url'  => 'pages/productos.php?cat=camisetas'    // idem
            ],
            [
              'img'  => 'assets/images/sample-6.jpg',
              'name' => 'Accesorios',
              'url'  => 'pages/productos.php?cat=accesorios'
            ],
            [
              'img'  => 'assets/images/tienda-4.jpg',
              'name' => 'Tattoo Studio',
              'url'  => 'pages/tattoo.php'                    // Aquí al tattoo.php
            ],
          ];
        foreach($cats as $c): ?>
        <a href="<?=htmlspecialchars($c['url'],ENT_QUOTES,'UTF-8')?>" class="category-card">
          <img src="<?=htmlspecialchars($c['img'],ENT_QUOTES,'UTF-8')?>" alt="<?=htmlspecialchars($c['name'],ENT_QUOTES,'UTF-8')?>" loading="lazy">
          <div class="overlay"><h3><?=htmlspecialchars($c['name'],ENT_QUOTES,'UTF-8')?></h3></div>
        </a>
      <?php endforeach;?>
    </div>
  </section>

  <!-- =============================================== -->
  <!-- PROMOCIÓN OUTLET -->
  <section class="promo-split container" aria-label="Leyenda Outlet">
    <div class="promo-text">
      <h2>LEYENDA OUTLET</h2>
      <p>Aprovecha nuestra colección de rebajas con diseños únicos, básicos y atemporales. Solo por tiempo limitado.</p>
      <a href="#" class="btn-cta">VER REBAJAS</a>
    </div>
    <div class="promo-image">
      <img src="assets/images/tienda-2.jpg" alt="Outlet Leyenda" loading="lazy">
    </div>
  </section>

  <!-- =============================================== -->
  <!-- PRODUCTOS DESTACADOS (Swiper) -->
  <section id="products" class="products-carousel swiper container" aria-labelledby="titulo-productos">
    <h2 id="titulo-productos" class="visually-hidden">Productos destacados</h2>
    <div class="swiper-wrapper">
      <?php
        $products = [
          ['img'=>'assets/images/sample-1.JPG','name'=>'Camisa Vestir Slim Fit','price'=>'€29,99'],
          ['img'=>'assets/images/sample-3.jpg','name'=>'Vestido Flujo','price'=>'€59,99'],
          ['img'=>'assets/images/sample-6.jpg','name'=>'Gorra Ajustable','price'=>'€14,99'],
        ];
        foreach($products as $p): ?>
        <div class="swiper-slide">
          <div class="product-card">
            <img src="<?=htmlspecialchars($p['img'],ENT_QUOTES,'UTF-8')?>" alt="<?=htmlspecialchars($p['name'],ENT_QUOTES,'UTF-8')?>" loading="lazy">
            <div class="overlay">
              <h3><?=htmlspecialchars($p['name'],ENT_QUOTES,'UTF-8')?></h3>
              <p class="price"><?=htmlspecialchars($p['price'],ENT_QUOTES,'UTF-8')?></p>
            </div>
          </div>
        </div>
      <?php endforeach;?>
    </div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>
    <div class="swiper-pagination"></div>
  </section>

  <!-- =============================================== -->
  <!-- SECCIÓN DIVIDIDA: LOOKBOOK + NEWSLETTER -->
  <div class="media-split">

    <!-- LOOKBOOK -->
    <section id="lookbook-video" class="lookbook-video" aria-labelledby="titulo-lookbook">
      <h2 id="titulo-lookbook"></h2>
      <video
        id="lookbook-player"
        poster="assets/images/sample-7.jpg"
        autoplay muted loop playsinline preload="auto"
      >
        <source src="assets/images/sample-7.mp4" type="video/mp4">
        Tu navegador no soporta la reproducción de video.
      </video>
    </section>

    <!-- NEWSLETTER -->
    <section id="newsletter" class="newsletter" aria-labelledby="titulo-newsletter">
      <video
        id="newsletter-bg"
        class="newsletter-bg"
        src="assets/videos/newsletter-bg.mp4"
        autoplay muted loop playsinline preload="auto"
      ></video>
      <div class="newsletter-overlay">
        <div class="newsletter-content">
          <h2 id="titulo-newsletter">SUSCRÍBETE A LA NEWSLETTER</h2>
          <p>Únete ahora a la Familia LEYENDA y sé el primero en enterarte de ofertas exclusivas para tu próxima compra.</p>
          <form action="subscribe.php" method="POST" aria-label="Formulario de suscripción">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'],ENT_QUOTES,'UTF-8')?>">
            <input type="email" name="email" placeholder="Tu correo electrónico" required>
            <button type="submit" class="btn-cta">SUSCRÍBETE</button>
          </form>
        </div>
      </div>
    </section>

  </div>

</main>

<?php include 'includes/footer.php'; ?>

<!-- =============================================== -->
<!-- SWIPER & MAIN JS -->
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css"/>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js" defer></script>
<script src="assets/js/main.js" defer></script>
