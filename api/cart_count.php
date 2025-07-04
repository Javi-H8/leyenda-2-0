<?php
// api/cart_count.php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/cart_functions.php';

// ────────────────────────────────────────────────────────────
// Sólo GET permitido
// ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit(json_encode(['error' => 'Método no permitido']));
}

// ────────────────────────────────────────────────────────────
// Obtener items del carrito y sumar cantidades
// ────────────────────────────────────────────────────────────
$items = cart_get_items(); // tu función existente
$totalQty = array_reduce($items, function($sum, $item) {
    return $sum + (int)($item['quantity'] ?? 0);
}, 0);

// ────────────────────────────────────────────────────────────
// Responder JSON
// ────────────────────────────────────────────────────────────
echo json_encode(['count' => $totalQty]);
