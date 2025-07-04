<?php
// api/cart_items.php
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
// Leer items desde tu lógica actual
// ────────────────────────────────────────────────────────────
$rawItems = cart_get_items(); // devuelve array con al menos ['id','name','price','quantity']

$response = [
    'items' => [],
    'total' => 0.0,
];

foreach ($rawItems as $it) {
    // Sanitizar y asegurar tipos
    $id       = htmlspecialchars((string)($it['id']       ?? ''), ENT_QUOTES, 'UTF-8');
    $name     = htmlspecialchars((string)($it['name']     ?? ''), ENT_QUOTES, 'UTF-8');
    $price    = filter_var($it['price']    ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.0;
    $quantity = filter_var($it['quantity'] ?? 0, FILTER_VALIDATE_INT)   ?: 0;

    $subtotal = round($price * $quantity, 2);
    $response['items'][] = [
        'id'       => $id,
        'name'     => $name,
        'price'    => $price,
        'quantity' => $quantity,
        'subtotal' => $subtotal,
    ];
    $response['total'] += $subtotal;
}

// Redondear total
$response['total'] = round($response['total'], 2);

// ────────────────────────────────────────────────────────────
// Responder JSON
// ────────────────────────────────────────────────────────────
echo json_encode($response);
