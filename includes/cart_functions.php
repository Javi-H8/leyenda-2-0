<?php
// includes/cart_functions.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function getDB(): PDO {
    global $pdo;
    return $pdo;
}

function ensure_cart(): void {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function cart_add_session(int $variantId, int $qty = 1): void {
    if ($qty < 1) {
        throw new InvalidArgumentException("Cantidad inválida: $qty");
    }

    $db = getDB();
    $stmt = $db->prepare("
        SELECT stock
          FROM producto_variantes
         WHERE id = ?
           AND eliminado_en IS NULL
    ");
    $stmt->execute([$variantId]);
    $stock = (int)$stmt->fetchColumn();
    if (!$stock && $stock !== 0) {
        throw new Exception("Variante no encontrada: $variantId");
    }
    if ($qty > $stock) {
        throw new Exception("No hay suficiente stock ({$stock})");
    }

    ensure_cart();
    if (isset($_SESSION['cart'][$variantId])) {
        $_SESSION['cart'][$variantId] += $qty;
    } else {
        $_SESSION['cart'][$variantId] = $qty;
    }
}

function cart_update_session(int $variantId, int $qty): void {
    ensure_cart();
    if (!isset($_SESSION['cart'][$variantId])) {
        throw new InvalidArgumentException("Variante no en carrito: $variantId");
    }

    if ($qty > 0) {
        $db = getDB();
        $stmt = $db->prepare("SELECT stock FROM producto_variantes WHERE id = ? AND eliminado_en IS NULL");
        $stmt->execute([$variantId]);
        $stock = (int)$stmt->fetchColumn();

        if ($qty > $stock) {
            throw new Exception("Cantidad excede stock disponible ({$stock})");
        }
        $_SESSION['cart'][$variantId] = $qty;
    } else {
        unset($_SESSION['cart'][$variantId]);
    }
}

function cart_remove_session(int $variantId): void {
    ensure_cart();
    if (!isset($_SESSION['cart'][$variantId])) {
        throw new InvalidArgumentException("Variante no en carrito: $variantId");
    }
    unset($_SESSION['cart'][$variantId]);
}

function cart_clear_session(): void {
    unset($_SESSION['cart']);
}

function cart_get_items(): array {
    ensure_cart();
    $variants = array_keys($_SESSION['cart']);
    if (empty($variants)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($variants), '?'));
    $sql = "
      SELECT
        v.id               AS variant_id,
        p.id               AS product_id,
        p.nombre           AS name,
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
      WHERE v.id IN ($placeholders)
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
        $items[] = [
            'variant_id' => $vid,
            'product_id' => (int)$row['product_id'],
            'name'       => (string)$row['name'],
            'size'       => (string)$row['size'],
            'color'      => (string)$row['color'],
            'price'      => (float)$row['price'],
            'stock'      => (int)$row['stock'],
            'image'      => (string)($row['image'] ?? ''),
            'quantity'   => (int)($_SESSION['cart'][$vid] ?? 0),
        ];
    }

    return $items;
}

function cart_item_count(): int {
    ensure_cart();
    return array_sum($_SESSION['cart']);
}

function cart_total_amount(): float {
    $total = 0.0;
    foreach (cart_get_items() as $it) {
        $total += $it['price'] * $it['quantity'];
    }
    return $total;
}
