<?php
// ───────────── INIT & SECURITY ─────────────
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php'; // conexión PDO
// ───────────── CABECERAS HTTP DEFENSIVAS ─────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self';");

// ───────────── COOKIES DE SESIÓN MÁS SEGURAS ─────────────
ini_set('session.cookie_secure',   '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

// ───────────── SESSION & CSRF ─────────────
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ───────────── CARGAR CATEGORÍAS ─────────────
try {
    $stmtCat = $pdo->query("
      SELECT id, nombre, slug
      FROM categorias
      WHERE deleted_at IS NULL
      ORDER BY nombre
    ");
    $categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit('Error al cargar categorías.');
}

// ───────────── FILTROS GET ─────────────
$where  = ["p.activo = 1", "p.deleted_at IS NULL"];
$params = [];

// 1. Filtrar por categoría si no es “all”
if (
    isset($_GET['categoria']) &&
    preg_match('/^[a-z0-9\-]+$/i', $_GET['categoria']) &&
    $_GET['categoria'] !== 'all'
) {
    $where[]             = "c.slug = :cat_slug";
    $params[':cat_slug'] = $_GET['categoria'];
}

// 2. Filtrar por búsqueda de nombre si existe
if (!empty($_GET['busqueda'])) {
    $where[]           = "p.nombre LIKE :busq";
    $params[':busq']   = '%' . trim($_GET['busqueda']) . '%';
}

// 3. Construir la cláusula WHERE final
$sqlWhere = implode(' AND ', $where);

// ───────────── CARGAR PRODUCTOS CON FILTROS ─────────────
try {
    $stmtProd = $pdo->prepare("
      SELECT p.id, p.nombre, p.slug, p.descripcion, p.precio_base, c.slug AS cat_slug
      FROM productos p
      JOIN categorias c ON p.categoria_id = c.id
      WHERE $sqlWhere
      ORDER BY p.created_at DESC
    ");
    $stmtProd->execute($params);
    $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al cargar productos: ' . $e->getMessage());
    exit('Error al cargar productos.');
}

// ───────────── PREPARAR CONSULTA DE IMÁGENES ─────────────
$stmtImg = $pdo->prepare("
  SELECT ruta 
  FROM producto_imagenes
  WHERE producto_id = :pid
  AND principal = 1
  ORDER BY created_at DESC
  LIMIT 1
");
$stmtImg->setFetchMode(PDO::FETCH_COLUMN, 0);
$stmtImg->execute([':pid' => 0]); // Placeholder para evitar error inicial


// … tu código de INIT, seguridad y carga de filtros …

// ── PAGINACIÓN ───────────────────────────────────────────────
$page    = isset($_GET['page']) && ctype_digit($_GET['page']) && $_GET['page']>0
           ? (int)$_GET['page'] : 1;
$perPage = 12;  // ajusta a cuántos productos quieres por “página”
$offset  = ($page - 1) * $perPage;

// ── CARGAR PRODUCTOS CON FILTROS (ahora con LIMIT/OFFSET) ────
try {
    $sql = "
      SELECT p.id, p.nombre, p.slug, p.descripcion, p.precio_base, c.slug AS cat_slug
      FROM productos p
      JOIN categorias c ON p.categoria_id = c.id
      WHERE $sqlWhere
      ORDER BY p.created_at DESC
      LIMIT :limit OFFSET :offset
    ";
    $stmtProd = $pdo->prepare($sql);
    // liga tus parámetros de filtro…
    foreach ($params as $k => $v) {
      $stmtProd->bindValue($k, $v);
    }
    // y los de paginación:
    $stmtProd->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmtProd->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmtProd->execute();
    $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al cargar productos: '.$e->getMessage());
    exit('Error al cargar productos.');
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LEYENDA – Nuestros Productos</title>

  <!-- CSS -->
  <link rel="stylesheet" href="../assets/css/grid.css">
  <link rel="stylesheet" href="../assets/css/header.css">
  <link rel="stylesheet" href="../assets/css/footer.css">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/productos.css"> 

  <!-- JS -->
  <script src="../assets/js/main.js" defer></script>
  <script src="../assets/js/productos.js" defer></script>
</head>
<body class="productos-page">

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container">
  <h1 class="text-center">Nuestros Productos</h1>

  <!-- ───────────── FILTROS ───────────── -->
  <section id="filtros" class="flex items-center justify-between wrap-gap">
    <div class="filter-group">
      <label for="categoria">Categoría:</label>
      <select id="categoria" name="categoria">
        <option value="all">Todas</option>
        <?php foreach ($categorias as $cat): ?>
          <option value="<?= htmlspecialchars($cat['slug'], ENT_QUOTES) ?>">
            <?= htmlspecialchars($cat['nombre'], ENT_QUOTES) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label for="buscador">Buscar:</label>
      <input type="search" id="buscador" placeholder="Buscar producto…" aria-label="Buscar productos">
    </div>
    <div class="column-switcher" role="group" aria-label="Columnas">
      <span>Columnas:</span>
      <button type="button" data-cols="1" class="col-btn" aria-pressed="false">1</button>
      <button type="button" data-cols="2" class="col-btn active" aria-pressed="true">2</button>
    </div>
  </section>

  <!-- ───────────── LISTA DE PRODUCTOS ───────────── -->
  <section id="lista-productos" class="productos dos-por-linea">
    <?php if (empty($productos)): ?>
      <p class="no-products">No hay productos disponibles.</p>
    <?php else: ?>
      <?php foreach ($productos as $p):
// ───────────── PREPARAR CONSULTA DE IMÁGENES (única por producto) ─────────────
        $stmtImg = $pdo->prepare("
          SELECT ruta 
          FROM producto_imagenes
          WHERE producto_id = :pid
            AND principal = 1
          ORDER BY created_at DESC
          LIMIT 1
        ");
        $stmtImg->execute([':pid' => (int)$p['id']]);
        $img = $stmtImg->fetchColumn() ?: 'placeholder.png';
      ?>
        <article class="card" data-categoria="<?= htmlspecialchars($p['cat_slug'], ENT_QUOTES) ?>">
          <a href="producto.php?slug=<?= urlencode($p['slug']) ?>" class="card-img">
            <img src="../assets/images/<?= htmlspecialchars($img, ENT_QUOTES) ?>"
                 alt="Foto de <?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
                 loading="lazy">
          </a>
          <div class="card-content">
            <h2><?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?></h2>
            <div class="precio">€<?= number_format((float)$p['precio_base'], 2, ',', '.') ?></div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
