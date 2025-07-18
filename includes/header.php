<?php
// includes/header.php

declare(strict_types=1);

// 0. Sesión + CSRF (único punto de arranque)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (!defined('CSRF_TOKEN')) {
    define('CSRF_TOKEN', $_SESSION['csrf_token']);
}

// 0bis) HTTP Security Headers – sin salida previa
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://unpkg.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; object-src 'none'; frame-ancestors 'none';");


// 1. Carga configuración y funciones de carrito
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/cart_functions.php';

// 1bis) Helpers generales (csrf_token, verify_csrf, sendVerificationEmail…)
require_once __DIR__ . '/../functions.php';

// 1ter) Si hay sesión iniciada, carga el nombre para el menú
$user = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ?");
    $stmt->execute([ $_SESSION['user_id'] ]);
    $user = $stmt->fetch();
}

// 2. Definir BASE_URL (ajusta si cambias de carpeta)
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Leyenda 2.0');
}

// 3. Página actual para marcar menú activo
// $currentPage = basename($_SERVER['PHP_SELF']);
  $currentPage = basename($_SERVER['SCRIPT_NAME']);

// 4. Contar ítems en carrito para mostrar un badge
$cartCount = cart_item_count();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Título dinámico: puedes sobreescribirlo en cada página -->
  <title><?= $pageTitle ?? 'LEYENDA – Primavera-Verano' ?></title>

  <!-- CSRF y Base URL para AJAX -->
  <meta name="base-url"  content="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="csrf-token" content="<?= htmlspecialchars(CSRF_TOKEN, ENT_QUOTES, 'UTF-8') ?>">

  <!-- Favicon -->
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.ico">

  <!-- Fuente elegante y fina para el logo -->
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;700&display=swap" rel="stylesheet" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>


  <!-- Estilos -->
  <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/css/grid.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/header.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/footer.css">

<?php if (in_array($currentPage, ['login.php','register.php'], true)): ?>
  <link 
    rel="stylesheet" 
    href="<?= BASE_URL ?>/assets/css/auth.css?v=<?= filemtime(__DIR__ . '/../assets/css/auth.css') ?>"
  >

<?php endif; ?>
  <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
  <script src="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/js/header-advanced.js" defer></script>
    <!-- JS sólo para login/register -->
  <?php if (in_array($currentPage, ['login.php','register.php'], true)): ?>
    <script 
      src="<?= BASE_URL ?>/assets/js/auth.js?v=<?= filemtime(__DIR__ . '/../assets/js/auth.js') ?>" 
      defer
    ></script>
  <?php endif; ?>
  <!-- Configuración global para carrito.js -->
  <script>
    window.CART_AJAX = {
      csrf: '<?= htmlspecialchars(CSRF_TOKEN, ENT_QUOTES, 'UTF-8') ?>',
      url : '<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/ajax/cart_action.php'
    };
  </script>

</head>
<body>

  <!-- Top Bar -->
  <div class="top-bar">ENVÍOS GRATIS A PARTIR DE 80€</div>

  <!-- Header principal -->
  <header class="site-header">
    <div class="container header-inner">

      <!-- Logo -->
      <a href="<?= BASE_URL ?>/index.php" class="logo">
        <span class="logo-main">LEYENDA</span>
        <span class="logo-sub">CLOTHES</span>
      </a>

      <!-- Botón hamburguesa (móvil) -->
      <button
        id="menu-toggle"
        class="hamburger"
        type="button"
        aria-label="Abrir menú"
        aria-expanded="false"
      >☰</button>

      <!-- Menú principal -->
      <nav id="main-nav" class="nav-menu" aria-label="Menú principal">
        <ul>
          <li><a href="<?= BASE_URL ?>/index.php#slider"
                 <?= $currentPage === 'index.php' ? 'class="active"' : '' ?>>Home</a></li>

          <li><a href="<?= BASE_URL ?>/pages/productos.php"
                 <?= $currentPage === 'productos.php' ? 'class="active"' : '' ?>>Productos</a></li>
           <li><a href="<?= BASE_URL ?>/pages/tattoo.php"
                 <?= $currentPage === 'tattoo.php' ? 'class="active"' : '' ?>>Tattoo Studio</a></li>
          <li><a href="<?= BASE_URL ?>/index.php#lookbook-video">Lookbook</a></li>
          <?php if ($user): ?>
            <li><a href="<?= BASE_URL ?>/dashboard.php">
                Hola, <?= htmlspecialchars($user['nombre'],ENT_QUOTES,'UTF-8') ?>
              </a>
            </li>
            <li><a href="<?= BASE_URL ?>/logout.php">Logout</a></li>
          <?php else: ?>
            <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
          <?php endif; ?>

        </ul>
      </nav>

      <!-- Carrito siempre visible -->
      <div class="site-header__cart" aria-live="polite">
        <a href="<?= BASE_URL ?>/pages/carrito.php"
           class="cart-link <?= $currentPage === 'carrito.php' ? 'active' : '' ?>"
           aria-label="Ver carrito de compras">
          🛒 Carrito
          <?php if ($cartCount > 0): ?>
            <span class="cart-badge" aria-label="<?= htmlspecialchars((string)$cartCount, ENT_QUOTES, 'UTF-8') ?> ítems en el carrito">
              <?= htmlspecialchars((string)$cartCount, ENT_QUOTES, 'UTF-8') ?>
            </span>
          <?php endif; ?>
        </a>
      </div>

    </div>
  </header>
