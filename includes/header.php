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

// 1. Carga configuración y funciones de carrito
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/cart_functions.php';

// 2. Definir BASE_URL (ajusta si cambias de carpeta)
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Leyenda 2.0');
}

// 3. Página actual para marcar menú activo
$currentPage = basename($_SERVER['PHP_SELF']);

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
<meta name="base-url"  content="<?= BASE_URL ?>">
<meta name="csrf-token" content="<?= htmlspecialchars(CSRF_TOKEN, ENT_QUOTES, 'UTF-8') ?>">

  <!-- Favicon -->
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.ico">

  <!-- Estilos -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/grid.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/header.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/footer.css">

  <!-- Scripts -->
  <script src="<?= BASE_URL ?>/assets/js/main.js"    defer></script>
  
</head>
<body>

  <!-- Top Bar -->
  <div class="top-bar">ENVÍOS GRATIS A PARTIR DE 80€</div>

  <!-- Header principal -->
  <header class="site-header">
    <div class="container header-inner">
      
      <!-- Logo -->
      <a href="<?= BASE_URL ?>/index.php" class="logo">LEYENDA</a>

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
          <li><a href="<?= BASE_URL ?>/pages/tattoo.php"
                 <?= $currentPage === 'tattoo.php' ? 'class="active"' : '' ?>>Tattoo</a></li>
          <li><a href="<?= BASE_URL ?>/pages/productos.php"
                 <?= $currentPage === 'productos.php' ? 'class="active"' : '' ?>>Productos</a></li>
          <li><a href="<?= BASE_URL ?>/index.php#lookbook-video">Lookbook</a></li>
          <li><a href="<?= BASE_URL ?>/index.php#newsletter">Newsletter</a></li>
          <li><a href="<?= BASE_URL ?>/index.php#footer">Contacto</a></li>
          <li class="cart-link">
            <a href="<?= BASE_URL ?>/pages/carrito.php"
               <?= $currentPage === 'carrito.php' ? 'class="active"' : '' ?>
               aria-label="Ver carrito de compras">
              🛒 Carrito
              <?php if ($cartCount > 0): ?>
                <span class="cart-badge" aria-label="<?= $cartCount ?> ítems en el carrito">
                  <?= $cartCount ?>
                </span>
              <?php endif; ?>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </header>
