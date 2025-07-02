<?php
// api/cart.php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// 1) Sesión (CSRF y carrito)
session_start();

// 2) Sólo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Método no permitido, use POST']));
}

// 3) Leer JSON
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    exit(json_encode(['error' => 'JSON inválido']));
}

// 4) CSRF
$sent = $data['csrf'] ?? '';
if (!isset($_SESSION['csrf_token']) || $sent !== $_SESSION['csrf_token']) {
    http_response_code(403);
    exit(json_encode(['error' => 'Token CSRF inválido']));
}

// 5) Acción y parámetros
$action    = trim((string)($data['action'] ?? ''));
$productId = isset($data['productId']) ? (int)$data['productId'] : null;
$qty       = isset($data['quantity'])  ? max(1, (int)$data['quantity']) : 1;

// 6) Dependencias
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cart_functions.php';

try {
    switch ($action) {
        case 'add':
            if ($productId === null) {
                throw new Exception('Falta productId para añadir');
            }
            cart_add_session($productId, $qty);
            break;

        case 'update':
            if ($productId === null) {
                throw new Exception('Falta productId para actualizar');
            }
            cart_update_session($productId, $qty);
            break;

        case 'remove':
            if ($productId === null) {
                throw new Exception('Falta productId para eliminar');
            }
            cart_remove_session($productId);
            break;

        case 'clear':
            cart_clear_session();
            break;

        default:
            throw new Exception("Acción inválida: {$action}");
    }

    // 7) Devolver carrito actualizado
    $items = cart_get_items();
    echo json_encode([
        'success' => true,
        'items'   => $items
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
