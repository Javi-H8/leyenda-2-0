<?php
// api/floating_cart.php
declare(strict_types=1);

// 1) Forzar HTTPS y salida JSON
if (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'on')
    || (empty($_SERVER['HTTPS']) && ($_SERVER['SERVER_PORT'] ?? '') !== '443')
) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['error'=>'HTTPS obligatorio']));
}
header('Content-Type: application/json; charset=utf-8');

// 2) Sesión y funciones de carrito
session_start();
require_once __DIR__ . '/../includes/cart_functions.php';

// 3) Sólo GET permitido
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit(json_encode(['error'=>'Método no permitido']));
}

// 4) Recoger datos
$items  = cart_get_items();            // ítems completos
$count  = cart_item_count();           // total unidades
$totals = calculate_totals($items);    // subtotal, discount, shipping, total

// 5) Responder
echo json_encode([
    'count'    => $count,
    'items'    => $items,
    'subtotal' => $totals['subtotal'],
    'discount' => $totals['discount'],
    'shipping' => $totals['shipping'],
    'total'    => $totals['total'],
], JSON_UNESCAPED_UNICODE);
