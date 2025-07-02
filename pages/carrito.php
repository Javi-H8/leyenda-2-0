<?php
// pages/carrito.php

declare(strict_types=1);

// 1) Header: inicia sesión, define BASE_URL, CSRF_TOKEN, carga $pdo y cart_functions,
//    genera meta-tags para AJAX y carga carrito.js en el <head>
include __DIR__ . '/../includes/header.php';

// 2) Tomamos el carrito de sesión: [ variante_id => cantidad ]
$cart = $_SESSION['cart'] ?? [];

// 3) Si está vacío, mostramos mensaje y salimos
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

// 4) Preparamos una consulta para obtener de una sola vez:
//    variante_id, datos de producto, precio, stock e imagen principal.
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
";

$stmt = $pdo->prepare($sql);
foreach ($variantIds as $i => $vid) {
    $stmt->bindValue($i + 1, $vid, PDO::PARAM_INT);
}
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5) Calculamos subtotal por línea y total general
$total = 0.0;
foreach ($items as &$it) {
    $qty = (int)($cart[$it['variant_id']] ?? 0);
    $it['quantity'] = $qty;
    $it['subtotal'] = $it['price'] * $qty;
    $total += $it['subtotal'];
}
unset($it);
?>
<main class="container carrito-page" aria-labelledby="cart-title">
  <h1 id="cart-title">Tu Carrito de Compras</h1>

  <!-- Mensajes de acción AJAX -->
  <div id="cart-message" role="alert" aria-live="polite" class="cart-message"></div>

  <!-- Contenedor principal -->
  <div id="cart-container">
    <table class="cart-table">
      <thead>
        <tr>
          <th scope="col">Producto</th>
          <th scope="col">Variante</th>
          <th scope="col">Cantidad</th>
          <th scope="col">Precio uni.</th>
          <th scope="col">Subtotal</th>
          <th scope="col">Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
        <tr data-id="<?= $it['variant_id'] ?>">
          <td class="prod-info">
            <img
              src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($it['image'], ENT_QUOTES) ?>"
              alt="<?= htmlspecialchars($it['product_name'], ENT_QUOTES) ?>"
              width="60" height="60" loading="lazy"
            >
            <span><?= htmlspecialchars($it['product_name'], ENT_QUOTES) ?></span>
          </td>
          <td>
            <?= htmlspecialchars($it['size'], ENT_QUOTES) ?> /
            <?= htmlspecialchars($it['color'], ENT_QUOTES) ?>
          </td>
          <td>
            <input
              type="number"
              class="qty-input"
              min="1"
              max="<?= $it['stock'] ?>"
              value="<?= $it['quantity'] ?>"
              aria-label="Cantidad de <?= htmlspecialchars($it['product_name'], ENT_QUOTES) ?>"
            >
          </td>
          <td><?= number_format((float)$it['price'], 2, ',', '.') ?> €</td>
          <td class="subtotal"><?= number_format((float)$it['subtotal'], 2, ',', '.') ?> €</td>
          <td>
            <button class="btn-remove" aria-label="Eliminar <?= htmlspecialchars($it['product_name'], ENT_QUOTES) ?>">
              Eliminar
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="cart-summary" aria-live="polite">
      <span class="summary-label">Total:</span>
      <span class="cart-total"><?= number_format($total, 2, ',', '.') ?> €</span>
    </div>

    <div class="cart-actions">
      <button id="btn-clear" class="btn btn-secondary">Vaciar carrito</button>
      <a href="<?= BASE_URL ?>/pages/checkout.php" class="btn btn-primary">Ir a pagar</a>
    </div>
  </div>
</main>
<script src="<?= BASE_URL ?>/assets/js/carrito.js" defer></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
