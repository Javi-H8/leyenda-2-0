<?php
// añadir_producto.php
// Página independiente, protegida con HTTP Basic Auth (mismas credenciales que BD),
// con CSRF, validación estricta, transacción segura y medidas anti-fuerza bruta.

// ====================
// 0) INCLUIMOS LA CONFIG BD
// ====================
require_once dirname(__DIR__) . '/config/database.php';
// define: $host, $db, $charset, $user, $pass, $pdo

// ====================
// 1) PROTECCIÓN ANTI-CACHE
// ====================
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// ====================
// 2) PROTECCIÓN ANTI-FUERZA BRUTA (simple con sesión)
// ====================
session_start();
if (!isset($_SESSION['failed_logins'])) {
    $_SESSION['failed_logins'] = 0;
    $_SESSION['first_failed_time'] = time();
}
// después de 5 intentos en menos de 5 minutos, bloqueamos 15 minutos
if ($_SESSION['failed_logins'] >= 5 && time() - $_SESSION['first_failed_time'] < 300) {
    http_response_code(429);
    exit('Demasiados intentos. Vuelve a intentarlo más tarde.');
}
if (time() - $_SESSION['first_failed_time'] > 300) {
    // reiniciar contadores tras 5 min
    $_SESSION['failed_logins'] = 0;
    $_SESSION['first_failed_time'] = time();
}

// ====================
// 3) HTTP BASIC AUTH (mismas credenciales que BD: $user / $pass)
// ====================
if (
    !isset($_SERVER['PHP_AUTH_USER'])
    || !isset($_SERVER['PHP_AUTH_PW'])
    || !hash_equals($user, $_SERVER['PHP_AUTH_USER'])
    || !hash_equals($pass, $_SERVER['PHP_AUTH_PW'])
) {
    $_SESSION['failed_logins']++;
    header('WWW-Authenticate: Basic realm="Área restringida"');
    header('HTTP/1.0 401 Unauthorized');
    exit('No autorizado.');
}

// acceso correcto: restablecer contador
$_SESSION['failed_logins'] = 0;

// ====================
// 4) PREPARAR CSRF
// ====================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ====================
// 5) INICIALIZAR VARIABLES
// ====================
$errors  = [];
$success = false;

// ====================
// 6) PROCESAR ENVÍO
// ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 6.1) Verificar CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Token CSRF inválido.';
    }

    // 6.2) Recoger y sanear
    $nombre      = substr(trim($_POST['nombre'] ?? ''), 0, 150);
    $slug        = substr(trim($_POST['slug'] ?? ''), 0, 150);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = filter_var($_POST['precio'] ?? '', FILTER_VALIDATE_FLOAT);
    $categoriaId = filter_var($_POST['categoria_id'] ?? '', FILTER_VALIDATE_INT);
    $rutaImagen  = trim($_POST['ruta_imagen'] ?? '');

    // 6.3) Validaciones adicionales
    if ($nombre === '') {
        $errors[] = 'El nombre es obligatorio.';
    }
    if ($slug === '' || !preg_match('/^[a-z0-9\-]+$/', $slug)) {
        $errors[] = 'El slug debe ser minúsculas, números y guiones.';
    }
    if ($descripcion === '' || mb_strlen($descripcion) > 1000) {
        $errors[] = 'La descripción es obligatoria y ≤ 1000 caracteres.';
    }
    if ($precio === false || $precio < 0 || $precio > 99999.99) {
        $errors[] = 'El precio debe ser un número entre 0 y 99 999.99.';
    }
    if ($categoriaId === false || $categoriaId <= 0) {
        $errors[] = 'Categoría inválida.';
    }
    if ($rutaImagen === '' || !preg_match('/^[a-z0-9_\/\.\-]+$/i', $rutaImagen)) {
        $errors[] = 'Ruta de imagen inválida.';
    }

    // 6.4) Si todo OK, insertar en BD
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insertar producto
            $insertProd = $pdo->prepare("
                INSERT INTO productos
                    (categoria_id, nombre, slug, descripcion, precio_base)
                VALUES
                    (:cat, :name, :slug, :desc, :price)
            ");
            $insertProd->execute([
                ':cat'   => $categoriaId,
                ':name'  => $nombre,
                ':slug'  => $slug,
                ':desc'  => $descripcion,
                ':price' => $precio,
            ]);
            $productId = $pdo->lastInsertId();

            // Crear variante genérica
            $sku = bin2hex(random_bytes(8));
            $insertVar = $pdo->prepare("
                INSERT INTO producto_variantes
                    (producto_id, talla, color, sku, stock, creado_en)
                VALUES
                    (:pid, NULL, NULL, :sku, 0, NOW())
            ");
            $insertVar->execute([
                ':pid' => $productId,
                ':sku' => $sku,
            ]);
            $variantId = $pdo->lastInsertId();

            // Asociar imagen
            $insertImg = $pdo->prepare("
                INSERT INTO producto_imagenes
                    (variante_id, ruta, principal)
                VALUES
                    (:vid, :ruta, 1)
            ");
            $insertImg->execute([
                ':vid'  => $variantId,
                ':ruta' => $rutaImagen,
            ]);

            $pdo->commit();
            $success = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error al añadir producto: ' . $e->getMessage());
            $errors[] = 'Error interno al guardar el producto.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Producto</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        .errors { background: #fcc; padding:1em; border:1px solid #f00; }
        .success{ background: #cfc; padding:1em; border:1px solid #0a0; }
        form > div { margin: 0.8em 0; }
        label { display:block; margin-bottom:0.2em; }
        input, textarea, select { width:100%; padding:0.5em; }
        button { padding:0.7em 1.2em; }
    </style>
</head>
<body>

<?php if ($success): ?>
    <div class="success">✅ Producto añadido correctamente.</div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="errors"><ul>
        <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<form method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <div>
        <label for="nombre">Nombre:</label>
        <input id="nombre" name="nombre" maxlength="150" required value="<?= htmlspecialchars($nombre ?? '') ?>">
    </div>

    <div>
        <label for="slug">Slug:</label>
        <input id="slug" name="slug" maxlength="150" pattern="[a-z0-9\-]+" required value="<?= htmlspecialchars($slug ?? '') ?>">
    </div>

    <div>
        <label for="descripcion">Descripción:</label>
        <textarea id="descripcion" name="descripcion" maxlength="1000" required><?= htmlspecialchars($descripcion ?? '') ?></textarea>
    </div>

    <div>
        <label for="precio">Precio (€):</label>
        <input id="precio" name="precio" type="number" step="0.01" min="0" max="99999.99" required value="<?= htmlspecialchars($precio ?? '') ?>">
    </div>

    <div>
        <label for="categoria_id">Categoría:</label>
        <select id="categoria_id" name="categoria_id" required>
            <option value="">-- Selecciona --</option>
            <?php
            $cats = $pdo->query("SELECT id, nombre FROM categorias WHERE deleted_at IS NULL")->fetchAll();
            foreach ($cats as $c):
                $sel = (isset($categoriaId) && $categoriaId == $c['id']) ? 'selected' : '';
            ?>
            <option value="<?= $c['id'] ?>" <?= $sel ?>><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label for="ruta_imagen">Ruta de imagen:</label>
        <input id="ruta_imagen" name="ruta_imagen" pattern="[A-Za-z0-9_\/\.\-]+" required value="<?= htmlspecialchars($rutaImagen ?? '') ?>">
    </div>

    <button type="submit">Añadir Producto</button>
</form>

</body>
</html>
