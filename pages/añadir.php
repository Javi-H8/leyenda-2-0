<?php
// create_product.php – Crear/editar producto con variantes e imágenes por variante

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Cargar categorías
$categorias = $pdo->query("SELECT id, nombre FROM categorias WHERE deleted_at IS NULL ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Detectar modo edición
$isEdit = isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT);
$product = $variants = [];
if ($isEdit) {
    $pid = (int)$_GET['id'];
    $product = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND deleted_at IS NULL");
    $product->execute([$pid]);
    $product = $product->fetch(PDO::FETCH_ASSOC) ?: exit('Producto no encontrado');
    // Cargar variantes existentes
    $variants = $pdo->prepare("SELECT * FROM producto_variantes WHERE producto_id = ?");
    $variants->execute([$pid]);
    $variants = $variants->fetchAll(PDO::FETCH_ASSOC);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) $errors[] = 'Token CSRF inválido';
    // Validar datos básicos
    $categoria_id = filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT);
    $nombre       = trim($_POST['nombre'] ?? '');
    $slug         = preg_replace('/[^a-z0-9\-]/i','', trim($_POST['slug'] ?? ''));
    $desc         = trim($_POST['descripcion'] ?? '');
    $precio_base  = filter_input(INPUT_POST, 'precio_base', FILTER_VALIDATE_FLOAT);
    if (!$categoria_id) $errors[] = 'Categoría inválida';
    if ($nombre==='')   $errors[] = 'Nombre requerido';
    if ($slug==='')     $errors[] = 'Slug requerido';
    if ($desc==='')     $errors[] = 'Descripción requerida';
    if ($precio_base===false) $errors[] = 'Precio base inválido';
    
    // Recoger variantes
    $varsInput = $_POST['variant'] ?? [];
    if (!$isEdit && empty($varsInput)) {
        $errors[] = 'Al menos una variante requerida.';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            // Insertar/actualizar producto
            if ($isEdit) {
                $stmt = $pdo->prepare("UPDATE productos SET categoria_id=?, nombre=?, slug=?, descripcion=?, precio_base=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$categoria_id, $nombre, $slug, $desc, $precio_base, $pid]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO productos (categoria_id,nombre,slug,descripcion,precio_base,activo) VALUES (?,?,?,?,?,1)");
                $stmt->execute([$categoria_id, $nombre, $slug, $desc, $precio_base]);
                $pid = (int)$pdo->lastInsertId();
            }

            // Procesar variantes e imágenes específicas
            $varInsert = $pdo->prepare("INSERT INTO producto_variantes (producto_id,talla,color,sku,stock,precio) VALUES (?,?,?,?,?,?)");
            $imgInsert = $pdo->prepare("INSERT INTO producto_imagenes (variante_id,ruta,principal) VALUES (?,?,?)");

            foreach ($varsInput as $idx => $v) {
                // datos de la variante
                $talla = trim($v['talla']);
                $color = trim($v['color']);
                $sku   = trim($v['sku']);
                $stock = (int)$v['stock'];
                $vpre  = $v['precio'] !== '' ? (float)$v['precio'] : null;
                // Insertar variante
                $varInsert->execute([$pid, $talla, $color, $sku, $stock, $vpre]);
                $varId = (int)$pdo->lastInsertId();

                // Crear carpeta de imágenes
                $uploadDir = __DIR__ . "/../assets/images/products/{$pid}/{$varId}";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                // Manejar ficheros de esta variante
                if (isset($_FILES['images']['tmp_name'][$idx])) {
                    foreach ($_FILES['images']['tmp_name'][$idx] as $i => $tmp) {
                        if ($_FILES['images']['error'][$idx][$i] === UPLOAD_ERR_OK) {
                            $ext = pathinfo($_FILES['images']['name'][$idx][$i], PATHINFO_EXTENSION);
                            $file = uniqid('img_') . ".{$ext}";
                            move_uploaded_file($tmp, "{$uploadDir}/{$file}");
                            $relative = "products/{$pid}/{$varId}/{$file}";
                            $isPrim = $i===0 ? 1 : 0;
                            $imgInsert->execute([$varId, $relative, $isPrim]);
                        }
                    }
                }
            }

            $pdo->commit();
            header('Location: create_product.php?id='.$pid.'&success=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title><?= $isEdit ? 'Editar' : 'Crear' ?> Producto</title></head>
<body>
<h1><?= $isEdit ? 'Editar' : 'Crear' ?> Producto</h1>
<?php if ($errors): ?><ul><?php foreach($errors as $e):?><li><?=htmlspecialchars($e)?></li><?php endforeach;?></ul><?php endif; ?>
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?=$csrf?>">
  <label>Categoría
    <select name="categoria_id"><?php foreach($categorias as $c):?><option value="<?=$c['id']?>" <?=($product['categoria_id']??'')==$c['id']?'selected':''?>><?=htmlspecialchars($c['nombre'])?></option><?php endforeach;?></select>
  </label><br>
  <label>Nombre <input name="nombre" value="<?=htmlspecialchars($product['nombre']??'')?>"></label><br>
  <label>Slug   <input name="slug"   value="<?=htmlspecialchars($product['slug']??'')?>"></label><br>
  <label>Desc   <textarea name="descripcion"><?=htmlspecialchars($product['descripcion']??'')?></textarea></label><br>
  <label>Precio <input type="number" step="0.01" name="precio_base" value="<?=htmlspecialchars($product['precio_base']??'')?>"></label><br>

  <fieldset><legend>Variantes e Imágenes</legend>
    <button type="button" onclick="addVariant()">Agregar Variante</button>
    <div class="var-template" style="display:none;">
      <input name="variant[][talla]" placeholder="Talla">
      <input name="variant[][color]" placeholder="Color">
      <input name="variant[][sku]" placeholder="SKU">
      <input name="variant[][stock]" type="number" placeholder="Stock">
      <input name="variant[][precio]" type="number" step="0.01" placeholder="Precio opcional">
      <input name="images[]][files][]" type="file" multiple accept="image/*">
      <button type="button" onclick="this.parentNode.remove()">Eliminar</button>
    </div>
    <?php if($isEdit): foreach($variants as $v): ?>
      <div>
        <input name="variant[][talla]" value="<?=$v['talla']?>">
        <input name="variant[][color]" value="<?=$v['color']?>">
        <input name="variant[][sku]" value="<?=$v['sku']?>">
        <input name="variant[][stock]" type="number" value="<?=$v['stock']?>">
        <input name="variant[][precio]" type="number" step="0.01" value="<?=$v['precio']?>">
        <input name="images[][files][]" type="file" multiple>
      </div>
    <?php endforeach; endif; ?>
  </fieldset>

  <button type="submit"><?= $isEdit ? 'Actualizar' : 'Crear' ?></button>
</form>
<script>
function addVariant() {
  const tpl = document.querySelector('.var-template').cloneNode(true);
  tpl.style.display = 'grid';
  tpl.classList.remove('var-template');
  document.querySelector('fieldset').appendChild(tpl);
}
</script>
</body>
</html>
