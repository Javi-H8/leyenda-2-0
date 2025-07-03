<?php
declare(strict_types=1);

// Iniciar sesión si no está
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a BD
require_once __DIR__ . '/../config/database.php';

/**
 * Devuelve la instancia PDO global.
 */
function getDB(): PDO
{
    global $pdo;
    return $pdo;
}

/**
 * Obtiene o genera un token CSRF.
 */
function get_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica la validez de un token CSRF.
 */
function verify_csrf_token(string $token): bool
{
    return !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Asegura que exista el array de carrito en sesión.
 */
function ensure_cart(): void
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

/**
 * Agrega una variante al carrito de sesión.
 * @throws InvalidArgumentException, Exception
 */
function cart_add_session(int $variantId, int $qty = 1): void
{
    ensure_cart();
    if ($qty < 1) {
        throw new InvalidArgumentException("Cantidad inválida: $qty");
    }

    $db   = getDB();
    $stmt = $db->prepare("
        SELECT stock
          FROM producto_variantes
         WHERE id = :id
           AND eliminado_en IS NULL
    ");
    $stmt->execute([':id' => $variantId]);
    $stock = (int)$stmt->fetchColumn();

    if ($stock < 1) {
        throw new Exception("Variante no encontrada o sin stock (ID $variantId).");
    }
    if ($qty > $stock) {
        throw new Exception("Cantidad solicitada ($qty) excede stock disponible ($stock).");
    }

    $_SESSION['cart'][$variantId] = ($_SESSION['cart'][$variantId] ?? 0) + $qty;
    error_log("Cart➕: variante $variantId, qty añadida $qty, nueva qty {$_SESSION['cart'][$variantId]}");
}

/**
 * Actualiza la cantidad de una variante en sesión.
 * Si $qty <= 0, elimina la variante.
 * @throws InvalidArgumentException, Exception
 */
function cart_update_session(int $variantId, int $qty): void
{
    ensure_cart();
    if (!isset($_SESSION['cart'][$variantId])) {
        throw new InvalidArgumentException("Variante no en carrito: $variantId");
    }

    if ($qty > 0) {
        $db   = getDB();
        $stmt = $db->prepare("
            SELECT stock
              FROM producto_variantes
             WHERE id = :id
               AND eliminado_en IS NULL
        ");
        $stmt->execute([':id' => $variantId]);
        $stock = (int)$stmt->fetchColumn();

        if ($qty > $stock) {
            throw new Exception("Cantidad ($qty) > stock disponible ($stock).");
        }
        $_SESSION['cart'][$variantId] = $qty;
        error_log("Cart✏️: variante $variantId actualizada a qty $qty");
    } else {
        unset($_SESSION['cart'][$variantId]);
        error_log("Cart❌: variante $variantId eliminada");
    }
}

/**
 * Elimina una variante del carrito de sesión.
 * @throws InvalidArgumentException
 */
function cart_remove_session(int $variantId): void
{
    ensure_cart();
    if (!isset($_SESSION['cart'][$variantId])) {
        throw new InvalidArgumentException("Variante no en carrito: $variantId");
    }
    unset($_SESSION['cart'][$variantId]);
    error_log("Cart🗑️: variante $variantId eliminada");
}

/**
 * Vacía todo el carrito de sesión.
 */
function cart_clear_session(): void
{
    unset($_SESSION['cart']);
    error_log("Cart🧹: carrito de sesión vaciado");
}

/**
 * Recupera los ítems completos del carrito de sesión.
 */
function cart_get_items(): array
{
    ensure_cart();
    $variants = array_keys($_SESSION['cart']);
    if (empty($variants)) {
        return [];
    }

    $ph   = implode(',', array_fill(0, count($variants), '?'));
    $sql  = "
      SELECT
        v.id               AS variant_id,
        p.id               AS product_id,
        p.nombre           AS name,
        p.slug             AS slug,
        v.talla            AS size,
        v.color            AS color,
        COALESCE(v.precio, p.precio_base) AS price,
        v.stock            AS stock,
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

    $stmt = getDB()->prepare($sql);
    foreach ($variants as $i => $vid) {
        $stmt->bindValue($i+1, $vid, PDO::PARAM_INT);
    }
    $stmt->execute();

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $vid = (int)$row['variant_id'];
        $qty = max(1, min((int)$_SESSION['cart'][$vid], (int)$row['stock']));
        $items[] = [
            'variant_id' => $vid,
            'product_id' => (int)$row['product_id'],
            'name'       => (string)$row['name'],
            'slug'       => (string)$row['slug'],
            'size'       => (string)$row['size'],
            'color'      => (string)$row['color'],
            'price'      => (float)$row['price'],
            'stock'      => (int)$row['stock'],
            'image'      => (string)($row['image'] ?? ''),
            'quantity'   => $qty,
            'subtotal'   => round($qty * (float)$row['price'], 2),
        ];
    }

    return $items;
}

/**
 * Cantidad total de unidades en el carrito.
 */
function cart_item_count(): int
{
    ensure_cart();
    return array_sum($_SESSION['cart']);
}

/**
 * Importe total SIN descuentos ni envíos.
 */
function cart_total_amount(): float
{
    $total = 0.0;
    foreach (cart_get_items() as $it) {
        $total += $it['subtotal'];
    }
    return round($total, 2);
}

/* -------------------------------------------------------------------
   Funciones adicionales para carritos “potentes”
------------------------------------------------------------------- */

/**
 * Carga carrito persistente de un usuario logueado.
 * Devuelve [variant_id => quantity].
 */
function load_user_cart(int $userId, PDO $pdo): array
{
    $stmt = $pdo->prepare("
      SELECT variant_id, quantity
        FROM user_cart
       WHERE user_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cart = [];
    foreach ($rows as $r) {
        $cart[(int)$r['variant_id']] = (int)$r['quantity'];
    }
    return $cart;
}

/**
 * Aplica un cupón y devuelve datos si es válido, o false si no.
 */
function apply_coupon(string $code, PDO $pdo)
{
    $code = trim(strtoupper($code));
    $stmt = $pdo->prepare("
      SELECT code, type, value, expires_at, uses_left, min_purchase
        FROM coupons
       WHERE code = :code
         AND (expires_at IS NULL OR expires_at > NOW())
         AND (uses_left IS NULL OR uses_left > 0)
    ");
    $stmt->execute([':code' => $code]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$c) {
        return false;
    }
    return [
        'code'        => $c['code'],
        'type'        => $c['type'],       // 'percent' | 'fixed'
        'value'       => (float)$c['value'],
        'expires_at'  => $c['expires_at'],
        'uses_left'   => $c['uses_left'],
        'min_purchase'=> (float)$c['min_purchase'],
    ];
}

/**
 * Reduce en 1 el contador de usos de un cupón.
 */
function decrement_coupon_use(string $code, PDO $pdo): void
{
    $stmt = $pdo->prepare("
      UPDATE coupons
         SET uses_left = uses_left - 1
       WHERE code = :code
         AND uses_left > 0
    ");
    $stmt->execute([':code' => $code]);
}

/**
 * Obtiene hasta $limit productos relacionados para cross-sell.
 */
function get_related_products(array $variantIds, int $limit, PDO $pdo): array
{
    if (empty($variantIds)) {
        return [];
    }
    $ph = implode(',', array_fill(0, count($variantIds), '?'));
    $sql = "
      SELECT DISTINCT
        p.slug,
        pi.ruta    AS image,
        p.nombre   AS name,
        COALESCE(v.precio, p.precio_base) AS price
      FROM productos p
      JOIN producto_variantes v ON v.producto_id = p.id
      JOIN producto_imagenes pi
        ON pi.producto_id = p.id
       AND pi.principal = 1
      WHERE v.id IN ($ph)
        AND v.eliminado_en IS NULL
      ORDER BY RAND()
      LIMIT ?
    ";

    $stmt = $pdo->prepare($sql);
    $i = 1;
    foreach ($variantIds as $vid) {
        $stmt->bindValue($i++, $vid, PDO::PARAM_INT);
    }
    $stmt->bindValue($i, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calcula totales (subtotal, descuento, envío y total final).
 *
 * @param array $items      El array de cart_get_items() o similar.
 * @param array $coupon     Datos de cupón (o null).
 * @param float $freeThresh Importe mínimo para envío gratis.
 * @param float $fee        Coste de envío si aplica.
 * @return array            ['subtotal','discount','shipping','total']
 */
function calculate_totals(
    array $items,
    ?array $coupon = null,
    float $freeThresh = 50.00,
    float $fee = 5.99
): array {
    $subtotal = 0.0;
    foreach ($items as $it) {
        $subtotal += $it['subtotal'];
    }

    $discount = 0.0;
    if ($coupon) {
        if ($coupon['type'] === 'percent') {
            $discount = $subtotal * ($coupon['value'] / 100);
        } else {
            $discount = min($subtotal, $coupon['value']);
        }
    }

    $after    = $subtotal - $discount;
    $shipping = ($after >= $freeThresh) ? 0.0 : $fee;
    $total    = $after + $shipping;

    return [
        'subtotal' => round($subtotal, 2),
        'discount' => round($discount, 2),
        'shipping' => round($shipping, 2),
        'total'    => round($total, 2),
    ];
}
