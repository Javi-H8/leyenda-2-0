<?php
// ───────────── INIT & SECURITY ─────────────
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';   // Conexión PDO

// ───────────── HTTP HEADERS DEFENSIVE ─────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; font-src 'self';");

// ───────────── SESSION & CSRF ─────────────
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ───────────── VALIDATE SLUG ─────────────
$slug = filter_input(INPUT_GET, 'slug', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[a-z0-9\-]+$/i']
]);
if (!$slug) {
    http_response_code(400);
    exit('Solicitud inválida.');
}

try {
    // ───────────── FETCH PRODUCT ─────────────
    $stmt = $pdo->prepare("
      SELECT p.id, p.nombre, p.descripcion, p.precio_base, c.nombre AS categoria
      FROM productos p
      JOIN categorias c ON p.categoria_id = c.id
      WHERE p.slug = :slug AND p.activo = 1 AND p.deleted_at IS NULL
      LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$producto) {
        http_response_code(404);
        exit('Producto no encontrado.');
    }
    $prodId = (int)$producto['id'];

    // ───────────── FETCH IMAGES ─────────────
    $stmtImgs = $pdo->prepare("
      SELECT ruta, principal
      FROM producto_imagenes
      WHERE producto_id = :pid
      ORDER BY principal DESC, created_at ASC
    ");
    $stmtImgs->execute([':pid' => $prodId]);
    $imagenes = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);

    // ───────────── FETCH VARIANTS ─────────────
    $stmtVars = $pdo->prepare("
      SELECT v.id, v.talla, v.color, v.stock,
             COALESCE(v.precio, p.precio_base) AS precio
      FROM producto_variantes v
      JOIN productos p ON p.id = v.producto_id
      WHERE v.producto_id = :pid AND v.eliminado_en IS NULL
      ORDER BY v.talla, v.color
    ");
    $stmtVars->execute([':pid' => $prodId]);
    $variantes = $stmtVars->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Error en producto.php: ' . $e->getMessage());
    http_response_code(500);
    exit('Error interno.');
}
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($producto['nombre'], ENT_QUOTES) ?> · LEYENDA</title>
  <link rel="stylesheet" href="../assets/css/grid.css">
  <link rel="stylesheet" href="../assets/css/header.css">
  <link rel="stylesheet" href="../assets/css/footer.css">
  <link rel="stylesheet" href="../assets/css/producto.css">
</head>
<body class="producto-page">
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container producto-detail">

  <!-- 1) Breadcrumbs -->
  <nav class="breadcrumbs">
    <a href="index.php">Inicio</a> ›
    <a href="productos.php">Productos</a> ›
    <span><?= htmlspecialchars($producto['nombre'], ENT_QUOTES) ?></span>
  </nav>

  <div class="detalle-grid">
    <!-- 2) Miniaturas -->
    <div class="gallery-thumbs">
      <?php foreach ($imagenes as $i => $img): ?>
        <button class="thumb<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>">
          <img src="../assets/images/<?= htmlspecialchars($img['ruta'], ENT_QUOTES) ?>"
               alt="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES) ?>">
        </button>
      <?php endforeach; ?>
    </div>

    <!-- 3) Imagen principal -->
    <div class="gallery-main">
      <?php foreach ($imagenes as $i => $img): ?>
        <img src="../assets/images/<?= htmlspecialchars($img['ruta'], ENT_QUOTES) ?>"
             data-index="<?= $i ?>"
             class="main-img<?= $i === 0 ? ' visible' : '' ?>"
             alt="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES) ?>">
      <?php endforeach; ?>
    </div>

    <!-- 4) Información del producto -->
    <div class="product-info">
      <h1><?= htmlspecialchars($producto['nombre'], ENT_QUOTES) ?></h1>
      <p class="categoria"><?= htmlspecialchars($producto['categoria'], ENT_QUOTES) ?></p>
      <div class="price">€<?= number_format((float)$producto['precio_base'], 2, ',', '.') ?></div>

      <!-- Variantes (talla · color) -->
      <section class="detalles-variant">
        <label for="variante">Elige talla · color:</label>
        <select id="variante">
          <?php foreach ($variantes as $v):
            $stock = (int)$v['stock'];
            $label = "{$v['talla']} · {$v['color']}";
            if ($stock === 0) {
              $label .= " (Agotado)";
            } elseif ($stock <= 2) {
              $label .= " (Últimas {$stock})";
            }
          ?>
            <option
              value="<?= $v['id'] ?>"
              data-stock="<?= $stock ?>"
              data-precio="<?= number_format((float)$v['precio'], 2, '.', '') ?>"
              <?= $stock === 0 ? 'disabled' : '' ?>
            ><?= htmlspecialchars($label, ENT_QUOTES) ?></option>
          <?php endforeach; ?>
        </select>
        <div id="stock-aviso" class="low-stock"></div>
      </section>

      <!-- Precio dinámico y añadir al carrito -->
      <section class="precio-detalle">
        Precio: <span id="precio-actual">€<?= number_format((float)$producto['precio_base'], 2, ',', '.') ?></span>
      </section>
      <form method="post" action="../carrito.php" class="add-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="variante_id" id="input-variante" value="">
        <button type="submit" id="btn-carrito" class="add-to-cart">Añadir al carrito</button>
      </form>

      <!-- Información de envío/devolución -->
      <ul class="shipping-info">
        <li>Devolución gratuita</li>
        <li>Envío 3–5 días laborables</li>
        <li>Envío gratis a partir de 85€</li>
      </ul>

      <!-- Pestañas de descripción y cuidado -->
      <details class="prod-tab">
        <summary>Descripción</summary>
        <p><?= nl2br(htmlspecialchars($producto['descripcion'], ENT_QUOTES)) ?></p>
      </details>
      <details class="prod-tab">
        <summary>Material y cuidado</summary>
        <p>Instrucciones de lavado y cuidados.</p>
      </details>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/producto.js" defer></script>

</body>
</html>
