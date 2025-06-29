<?php
// includes/header.php

// Definir la URL base de la aplicación (ajusta si tu proyecto está en otra carpeta)
define('BASE_URL', '/Leyenda%202.0');

// Obtener el nombre del script actual para posibles lógicas de "activo"
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LEYENDA – Primavera-Verano</title>

  <!-- Estilos -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/grid.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/header.css">

  <!-- Script principal -->
  <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
</head>
<body>

  <!-- Top Bar -->
  <div class="top-bar">ENVÍOS GRATIS A PARTIR DE 80€</div>

  <!-- Header principal -->
  <header class="site-header">
    <div class="container header-inner">
      
      <!-- Logo: siempre vuelve al home -->
      <a href="<?= BASE_URL ?>/index.php" class="logo">LEYENDA</a>

      <!-- Botón hamburguesa (móvil) -->
      <button
        id="menu-toggle"
        class="hamburger"
        type="button"
        aria-label="Abrir menú"
        aria-expanded="false"
      >
        ☰
      </button>

      <!-- Menú principal -->
      <nav id="main-nav" class="nav-menu" aria-label="Menú principal">
        <ul>
          <!-- Home: apunta a la sección #slider de index.php -->
          <li>
            <a 
              href="<?= BASE_URL ?>/index.php#slider"
              <?= ($currentPage === 'index.php') ? '' : '' /* Opcional: agregar clase 'active' si quieres */ ?>
            >Home</a>
          </li>

          <!-- Categorías: sección #categories de index.php -->
          <li>
            <a href="<?= BASE_URL ?>/pages/tattoo.php">Tatoo</a>
          </li>

          <!-- Productos: página productos.php -->
          <li>
            <a href="<?= BASE_URL ?>/pages/productos.php">Productos</a>
          </li>

          <!-- Lookbook: sección #lookbook-video de index.php -->
          <li>
            <a href="<?= BASE_URL ?>/index.php#lookbook-video">Lookbook</a>
          </li>

          <!-- Newsletter: sección #newsletter de index.php -->
          <li>
            <a href="<?= BASE_URL ?>/index.php#newsletter">Newsletter</a>
          </li>

          <!-- Contacto: sección #footer de index.php -->
          <li>
            <a href="<?= BASE_URL ?>/index.php#footer">Contacto</a>
          </li>
        </ul>
      </nav>

    </div>
  </header>
