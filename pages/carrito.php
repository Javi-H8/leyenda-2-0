<?php
// pages/carrito.php

declare(strict_types=1);
session_start();

// 1) Header: inicia sesión, define BASE_URL, CSRF_TOKEN, carga $pdo y cart_functions
include __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/cart_functions.php';
?>
<!-- 1.a) CSS específico del carrito -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/carrito.css">
<?php

// CSRF token
$csrfToken = get_csrf_token();

// 2) Tomamos el carrito de sesión
$cart = $_SESSION['cart'] ?? [];

// 3) Procesar cupón si viene por POST
$couponError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    if (! verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $couponError = 'Error de seguridad. Vuelve a intentarlo.';
    } else {
        $code   = trim((string)$_POST['coupon_code']);
        $result = apply_coupon($code, $pdo);
        if ($result === false) {
            $couponError = 'Cupón no válido o caducado.';
            unset($_SESSION['applied_coupon']);
        } else {
            $_SESSION['applied_coupon'] = $result;
            decrement_coupon_use($result['code'], $pdo);
        }
    }
}

// 4) Si el carrito está vacío
if (empty($cart)): ?>
  <main class="container carrito-page" aria-labelledby="cart-title">
    <h1 id="cart-title">Tu Carrito de Compras</h1>
    <p class="empty-cart">
      Tu carrito está vacío. <a href="<?= BASE_URL ?>/index.php">¡Sigue navegando!</a>
    </p>
  </main>
<?php
  include __DIR__ . '/../includes/footer.php';
  exit;
endif;

// 5) Obtención de items
$variantIds = array_keys($cart);
$ph         = implode(',', array_fill(0, count($variantIds), '?'));
$sql = "
  SELECT
    v.id                               AS variant_id,
    p.id                               AS product_id,
    p.nombre                           AS product_name,
    v.talla                            AS size,
    v.color                            AS color,
    COALESCE(v.precio, p.precio_base)  AS price,
    v.stock                            AS stock,
    p.slug                             AS slug,
    (
      SELECT pi.ruta
        FROM producto_imagenes pi
       WHERE pi.producto_id = p.id
         AND pi.principal = 1
       ORDER BY pi.created_at ASC
       LIMIT 1
    ) AS image
  FROM producto_variantes v
  JOIN productos p ON p.id = v.producto_id
  WHERE v.id IN ($ph)
    AND v.eliminado_en IS NULL
";
$stmt = $pdo->prepare($sql);
foreach ($variantIds as $i => $vid) {
    $stmt->bindValue($i+1, $vid, PDO::PARAM_INT);
}
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6) Cálculo de subtotales y total bruto
$totalRaw = 0.0;
foreach ($items as &$it) {
    $qty = max(1, min((int)$cart[$it['variant_id']], $it['stock']));
    $it['quantity'] = $qty;
    $it['price']    = (float)$it['price'];
    $it['subtotal'] = $it['price'] * $qty;
    $totalRaw += $it['subtotal'];
}
unset($it);

// 7) Descuento de cupón
$discount = 0.0;
if (! empty($_SESSION['applied_coupon'])) {
    $c = $_SESSION['applied_coupon'];
    $discount = ($c['type'] === 'percent')
      ? $totalRaw * ($c['value']/100)
      : min($c['value'], $totalRaw);
}
$totalAfterDiscount = $totalRaw - $discount;

// 8) Envío
$freeThreshold = 50.00;
$shippingFee   = 5.99;
$shippingCost  = ($totalAfterDiscount >= $freeThreshold) ? 0.0 : $shippingFee;

// 9) Total final
$totalFinal = $totalAfterDiscount + $shippingCost;

// 10) Productos relacionados
$related = get_related_products($variantIds, 4, $pdo);

// 11) JSON-LD
$jsonLd = [
  "@context"        => "https://schema.org",
  "@type"           => "ShoppingCart",
  "name"            => "Carrito de compras",
  "itemListElement" => array_map(fn($it)=>[
    "@type"   => "Product",
    "name"    => $it['product_name'],
    "image"   => BASE_URL."/assets/images/".$it['image'],
    "sku"     => (string)$it['variant_id'],
    "offers"  => [
      "@type"         => "Offer",
      "price"         => number_format($it['price'],2,'.',''),
      "priceCurrency" => "EUR",
      "availability"  => $it['stock']>0
                          ? "https://schema.org/InStock"
                          : "https://schema.org/OutOfStock"
    ],
    "quantity" => $it['quantity']
  ], $items)
];
?>
<main class="container carrito-page" aria-labelledby="cart-title">

  <script type="application/ld+json">
    <?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) ?>
  </script>

  <!-- 1) Progreso de compra -->
  <nav class="checkout-steps" aria-label="Progreso de compra">
    <ol>
      <li class="completed">Carrito</li>
      <li>Envío</li>
      <li>Pago</li>
      <li>Confirmación</li>
    </ol>
  </nav>

  <h1 id="cart-title">Tu Carrito de Compras</h1>

  <!-- 2) Error de cupón -->
  <?php if ($couponError): ?>
    <div class="cart-message error"><?= htmlspecialchars($couponError, ENT_QUOTES) ?></div>
  <?php endif; ?>

  <!-- 3) Mensajes AJAX -->
  <div id="cart-message" role="alert" aria-live="polite" class="cart-message"></div>

  <!-- 4) Tabla de carrito -->
  <div id="cart-container">
    <table class="cart-table">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Variante</th>
          <th>Cantidad</th>
          <th>Precio uni.</th>
          <th>Subtotal</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr data-id="<?= $it['variant_id'] ?>">
          <td class="prod-info">
            <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($it['image'], ENT_QUOTES) ?>"
                 alt="<?= htmlspecialchars($it['product_name'], ENT_QUOTES) ?>"
                 width="60" height="60" loading="lazy">
            <span><?= htmlspecialchars($it['product_name'], ENT_QUOTES) ?></span>
          </td>
          <td><?= htmlspecialchars($it['size'],ENT_QUOTES) ?> / <?= htmlspecialchars($it['color'],ENT_QUOTES) ?></td>
          <td>
            <div class="qty-control">
              <button class="qty-decrease" aria-label="Disminuir cantidad">−</button>
              <input type="number" class="qty-input"
                     min="1" max="<?= $it['stock'] ?>"
                     value="<?= $it['quantity'] ?>"
                     aria-label="Cantidad de <?= htmlspecialchars($it['product_name'], ENT_QUOTES) ?>">
              <button class="qty-increase" aria-label="Aumentar cantidad">+</button>
            </div>
          </td>
          <td><?= number_format($it['price'],2,',','.') ?> €</td>
          <td class="subtotal"><?= number_format($it['subtotal'],2,',','.') ?> €</td>
          <td>
            <button class="btn-remove"
                    aria-label="Eliminar <?= htmlspecialchars($it['product_name'], ENT_QUOTES) ?>">
              Eliminar
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <!-- 5) Formulario de cupón -->
    <form method="POST" class="coupon-form" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
      <label for="coupon_code">¿Tienes un cupón?</label>
      <input type="text" id="coupon_code" name="coupon_code" placeholder="Código de cupón">
      <button type="submit" name="apply_coupon" class="btn btn-secondary">Aplicar</button>
    </form>

    <!-- 6) Resumen de precios -->
    <div class="cart-summary" aria-live="polite">
      <dl>
        <div><dt>Subtotal:</dt><dd><?= number_format($totalRaw,2,',','.') ?> €</dd></div>
        <?php if ($discount>0): ?>
        <div><dt>Descuento:</dt><dd>- <?= number_format($discount,2,',','.') ?> €</dd></div>
        <?php endif; ?>
        <div><dt>Envío:</dt>
          <dd><?= $shippingCost===0 ? 'Gratis' : number_format($shippingCost,2,',','.') . ' €' ?></dd>
        </div>
        <div class="total-line"><dt>Total:</dt>
          <dd><?= number_format($totalFinal,2,',','.') ?> €</dd></div>
      </dl>
    </div>

    <!-- 7) Acciones -->
    <div class="cart-actions">
      <button id="btn-clear" class="btn btn-secondary">Vaciar carrito</button>
      <a href="<?= BASE_URL ?>/pages/checkout.php" class="btn btn-primary">Ir a pagar</a>
    </div>
  </div>

  <!-- 8) Cross-sell -->
  <?php if (! empty($related)): ?>
  <section class="cart-related">
    <h2>También te puede interesar</h2>
    <ul class="related-list">
      <?php foreach ($related as $r): ?>
      <li>
        <a href="<?= BASE_URL ?>/producto/<?= htmlspecialchars($r['slug'], ENT_QUOTES) ?>">
          <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($r['image'], ENT_QUOTES) ?>"
               alt="<?= htmlspecialchars($r['name'], ENT_QUOTES) ?>" width="100" height="100">
          <span><?= htmlspecialchars($r['name'], ENT_QUOTES) ?></span>
          <span class="price"><?= number_format((float)$r['price'],2,',','.') ?> €</span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

</main>

<!-- 9) Definir endpoint AJAX y CSRF para el JS -->
<script>
  const CART_AJAX = {
    url: '<?= BASE_URL ?>/ajax/cart_action.php',
    csrf: '<?= $csrfToken ?>'
  };
</script>
<script src="<?= BASE_URL ?>/assets/js/carrito.js" defer></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
