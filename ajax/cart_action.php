<?php
// ajax/cart_action.php

declare(strict_types=1);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/cart_functions.php';

try {
    // Leer JSON de la petición
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['action'], $data['csrf'])) {
        throw new Exception('Datos incompletos');
    }
    // Verificar CSRF
    if (!verify_csrf_token($data['csrf'])) {
        throw new Exception('Token CSRF inválido');
    }

    $action    = $data['action'];
    $variantId = isset($data['variant_id']) ? (int)$data['variant_id'] : null;
    $quantity  = isset($data['quantity'])   ? (int)$data['quantity']   : null;

    // Ejecutar la acción correspondiente
    switch ($action) {
        case 'update':
            if ($variantId === null || $quantity === null) {
                throw new Exception('Parámetros inválidos para actualización');
            }
            cart_update_session($variantId, $quantity);
            break;

        case 'remove':
            if ($variantId === null) {
                throw new Exception('ID de variante faltante');
            }
            cart_remove_session($variantId);
            break;

        case 'clear':
            cart_clear_session();
            break;

        default:
            throw new Exception('Acción no reconocida');
    }

    // Reconstruir estado del carrito
    $items = cart_get_items();
    $total = 0.0;
    foreach ($items as &$it) {
        // asegurar float
        $it['subtotal'] = round((float)$it['price'] * $it['quantity'], 2);
        $total += $it['subtotal'];
    }
    unset($it);

    // Responder
    echo json_encode([
        'success' => true,
        'items'   => $items,
        'total'   => round($total, 2),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
