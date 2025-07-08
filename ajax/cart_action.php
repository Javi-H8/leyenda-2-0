<?php
declare(strict_types=1);
/**
 * ajax/cart_action.php
 *
 * Endpoint AJAX para gestionar el carrito:
 *   - add:    Añade una variante con cantidad al carrito
 *   - update: Cambia la cantidad de una variante existente
 *   - remove: Elimina una variante del carrito
 *   - clear:  Vacía completamente el carrito
 *
 * Request:
 *   - Método: POST
 *   - Content-Type: application/json
 *   - Body JSON: {
 *       action:      "add"|"update"|"remove"|"clear",
 *       variant_id?: int,
 *       quantity?:   int,
 *       csrf:        string
 *     }
 *
 * Response JSON:
 *   - 200 OK: {
 *       success:   true,
 *       items:     [ { id, name, price, quantity, subtotal }, … ],
 *       total:     float,
 *       cartCount: int
 *     }
 *   - 4xx/5xx: {
 *       success: false,
 *       error:   string
 *     }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// 1️⃣ Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2️⃣ Cargar funciones de carrito y CSRF
require_once __DIR__ . '/../includes/cart_functions.php';

try {
    // 3️⃣ Leer y parsear JSON de entrada
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

    // 4️⃣ Validar campos básicos
    if (empty($data['action']) || !isset($data['csrf'])) {
        throw new RuntimeException('Datos incompletos', 400);
    }

    // 5️⃣ Verificar token CSRF
    if (!verify_csrf_token($data['csrf'])) {
        throw new RuntimeException('Token CSRF inválido', 400);
    }

    $action    = $data['action'];
    $variantId = isset($data['variant_id']) ? (int)$data['variant_id'] : null;
    $quantity  = isset($data['quantity'])   ? (int)$data['quantity']   : null;

    // 6️⃣ Ejecutar acción
    switch ($action) {

        case 'add':
            if ($variantId === null || $quantity === null) {
                throw new RuntimeException('Parámetros inválidos para añadir', 400);
            }
            // Función que añade o incrementa cantidad
            cart_add_session($variantId, max(1, $quantity));
            break;

        case 'update':
            if ($variantId === null || $quantity === null) {
                throw new RuntimeException('Parámetros inválidos para actualización', 400);
            }
            cart_update_session($variantId, max(1, $quantity));
            break;

        case 'remove':
            if ($variantId === null) {
                throw new RuntimeException('ID de variante faltante', 400);
            }
            cart_remove_session($variantId);
            break;

        case 'clear':
            cart_clear_session();
            break;

        default:
            throw new RuntimeException('Acción no reconocida', 400);
    }

    // 7️⃣ Reconstruir estado del carrito
    $items = cart_get_items();   // [{ id, name, price, quantity }, …]
    $total = 0.0;
    foreach ($items as &$it) {
        $it['subtotal'] = round((float)$it['price'] * $it['quantity'], 2);
        $total += $it['subtotal'];
    }
    unset($it);

    // 8️⃣ Calcular total de unidades
    $cartCount = array_reduce($items, fn($sum, $it) => $sum + (int)$it['quantity'], 0);

    // 9️⃣ Devolver respuesta
    echo json_encode([
        'success'   => true,
        'items'     => $items,
        'total'     => round($total, 2),
        'cartCount' => $cartCount,
    ], JSON_UNESCAPED_UNICODE);

} catch (JsonException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);

} catch (RuntimeException $e) {
    http_response_code($e->getCode() ?: 400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
}
