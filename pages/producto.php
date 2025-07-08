<?php
// pages/producto.php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header(
  "Content-Security-Policy: default-src 'self'; ".
  "img-src 'self' data:; ".
  "script-src 'self' 'unsafe-inline'; ".
  "style-src 'self' 'unsafe-inline'; ".
  "font-src 'self';"
);

require_once __DIR__ . '/../config/database.php';    // crea $pdo
require_once __DIR__ . '/../includes/cart_functions.php'; // cart_item_count()

// 1) Validar slug
$slug = filter_input(INPUT_GET, 'slug', FILTER_VALIDATE_REGEXP, [
  'options'=>['regexp'=>'/^[a-z0-9\-]+$/i']
]);
if (!$slug) {
    http_response_code(400);
    exit('Solicitud inválida');
}

try {
    // 2) DATOS PRINCIPALES
    $sqlProd = "
      SELECT p.id, p.nombre, p.descripcion, p.precio_base, c.nombre AS categoria
      FROM productos p
      JOIN categorias c ON c.id = p.categoria_id
      WHERE p.slug = :slug
        AND p.activo = 1
        AND p.deleted_at IS NULL
      LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlProd);
    $stmt->execute([':slug'=>$slug]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$prod) {
        http_response_code(404);
        exit('Producto no encontrado');
    }
    $prodId = (int)$prod['id'];

    // 3) IMÁGENES
    $sqlImgs = "
      SELECT ruta, principal
      FROM producto_imagenes
      WHERE producto_id = :pid
      ORDER BY principal DESC, created_at ASC
    ";
    $stmt = $pdo->prepare($sqlImgs);
    $stmt->execute([':pid'=>$prodId]);
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4) VARIANTES
    $sqlVars = "
      SELECT
        v.id,
        v.talla,
        v.color,
        v.stock,
        COALESCE(v.precio, p.precio_base) AS precio
      FROM producto_variantes v
      JOIN productos p ON p.id = v.producto_id
      WHERE v.producto_id = :pid
        AND v.eliminado_en IS NULL
      ORDER BY v.talla, v.color
    ";
    $stmt = $pdo->prepare($sqlVars);
    $stmt->execute([':pid'=>$prodId]);
    $rawVars = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5) Procesar variantes en estructura útil
    $variantes = [];
    foreach ($rawVars as $v) {
        $variantes[] = [
            'id'       => (int)$v['id'],
            'label'    => "{$v['talla']} · {$v['color']}",
            'stock'    => (int)$v['stock'],
            'precio'   => (float)$v['precio'],
            'disabled' => ((int)$v['stock'] === 0),
        ];
    }

} catch (PDOException $e) {
    error_log("Producto error: {$e->getMessage()}");
    http_response_code(500);
    exit('Error interno');
}

// Preparar título y JSON-LD
$pageTitle = htmlspecialchars($prod['nombre'], ENT_QUOTES) . ' · LEYENDA';
$ldVariants = array_map(fn($v)=>[
    '@type'    => 'Offer',
    'sku'      => $v['id'],
    'price'    => number_format($v['precio'],2,'.',''),
    'priceCurrency' => 'EUR',
    'availability'  => $v['stock']>0 ? 'InStock' : 'OutOfStock',
], $variantes);

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php include __DIR__ . '/../includes/header.php'; ?>
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/producto.css">
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Product",
    "name": <?= json_encode($prod['nombre']) ?>,
    "description": <?= json_encode($prod['descripcion']) ?>,
    "image": <?= json_encode(array_column($imagenes, 'ruta')) ?>,
    "offers": <?= json_encode($ldVariants) ?>
  }
  </script>
</head>
<body class="producto-page">

<main class="container producto-detail">
  <nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="<?= BASE_URL ?>/index.php">Inicio</a> ›
    <a href="<?= BASE_URL ?>/pages/productos.php">Productos</a> ›
    <span><?= htmlspecialchars($prod['nombre'],ENT_QUOTES) ?></span>
  </nav>

  <div class="detalle-grid">

    <div class="gallery-thumbs" role="tablist">
      <?php foreach($imagenes as $i=>$img): ?>
        <button class="thumb<?= $i?'':' active'?>"
                data-index="<?= $i ?>"
                aria-label="Imagen <?= $i+1 ?>">
          <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($img['ruta'],ENT_QUOTES) ?>"
               loading="lazy"
               alt="<?= htmlspecialchars($prod['nombre'],ENT_QUOTES) ?>">
        </button>
      <?php endforeach; ?>
    </div>

    <div class="gallery-main">
      <?php foreach($imagenes as $i=>$img): ?>
        <img class="main-img<?= $i?'':' visible'?>"
             data-index="<?= $i ?>"
             src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($img['ruta'],ENT_QUOTES) ?>"
             loading="lazy"
             alt="<?= htmlspecialchars($prod['nombre'],ENT_QUOTES) ?>">
      <?php endforeach; ?>
    </div>

    <div class="product-info">
      <h1><?= htmlspecialchars($prod['nombre'], ENT_QUOTES) ?></h1>
      <p class="categoria"><?= htmlspecialchars($prod['categoria'], ENT_QUOTES) ?></p>

      <div class="price">
        Precio: <span id="precio-actual">
          €<?= number_format((float)$prod['precio_base'],2,',','.') ?>
        </span>
      </div>

      <section class="detalles-variant">
        <label for="variante">Variante:</label>
        <select id="variante" aria-describedby="stock-aviso">
          <?php foreach($variantes as $v): ?>
            <option value="<?= $v['id'] ?>"
                    data-stock="<?= $v['stock'] ?>"
                    data-precio="<?= number_format($v['precio'],2,'.','') ?>"
                    <?= $v['disabled']?'disabled':''?>>
              <?= htmlspecialchars($v['label'],ENT_QUOTES) ?>
              <?= $v['disabled']?"(Agotado)":"" ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div id="stock-aviso" class="low-stock" aria-live="polite"></div>
      </section>

<div class="cantidad-wrapper">
  <input
    type="number"
    id="cantidad"
    class="cantidad-input"
    value="1"
    min="1"
    max="10"
    placeholder="0"
  >
</div>

<button
  id="btn-carrito"
  class="btn btn-primary add-to-cart"
  data-variant-id="<?= htmlspecialchars((string)$variantes[0]['id'], ENT_QUOTES, 'UTF-8') ?>"
  data-quantity="<?= htmlspecialchars('1', ENT_QUOTES, 'UTF-8') ?>"
  <?= empty($variantes) ? 'disabled="disabled"' : '' ?>
>
  Añadir al carrito
</button>
      <div id="producto-message" role="alert" aria-live="assertive"
           class="producto-message"></div>

      <ul class="shipping-info">
        <li>Devolución gratuita</li>
        <li>Envío 3–5 días</li>
        <li>Gratis a partir de 85€</li>
      </ul>

      <details class="prod-tab">
        <summary>Descripción</summary>
        <p><?= nl2br(htmlspecialchars($prod['descripcion'],ENT_QUOTES)) ?></p>
      </details>
      <details class="prod-tab">
        <summary>Cuidado</summary>
        <p>Instrucciones de lavado y cuidados.</p>
      </details>

    </div>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/js/carrito.js" defer></script>
<script src="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/js/producto.js" defer></script>

</body>
</html>
