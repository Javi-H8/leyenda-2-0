<?php
// pages/tattoo.php — Web “Leyenda Barber & Tattoo” en Getafe, Los Molinos

declare(strict_types=1);
if (basename(__FILE__) !== basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Acceso denegado');
}

// 1) Imágenes disponibles (en assets/images)
$images = [
    'studio' => 'tienda-4.jpg',
    'artist' => 'artist.jpg',
    'tattoos' => [
        // Originales
        
        '21d15d51-1f63-4726-a2c4-ded07ffe0cd8.JPG',
        '3fdc4bc9-5e6b-4799-8d44-fe4ba5011f30.JPG',
        '4bffd227-af5a-445c-bbc4-e7e91ff77e77.JPG',
        '4d9083ae-60ee-424e-bab8-ddd815c61271.JPG',
        
        
        // Nuevas
        '49fa56d2-59e9-40b6-b4e6-302124b71ad5.JPG',
        '0857f40c-23d7-4757-9bdb-4d91d4035506.JPG',
        'ac4952d3-27f5-4035-b62e-25c4bdc8e9ac.JPG',
        'b6a0a063-e676-41eb-a188-84cdae822d04.JPG',
        'ba267795-8eff-4806-9fb8-64504b755582.JPG',
        'c569df66-0bc5-496a-ac27-c311b000bc26.JPG',
        '5228fdf0-2f24-45ff-9b11-e10f59a82d63.JPG'
    ]
];

// 2) Metadatos de cada tatuaje
$tattoo_data = [
    '21d15d51-1f63-4726-a2c4-ded07ffe0cd8.JPG' => ['Fine Line Minimalista',       'Líneas ultrafinas con single needle.'],
    '3fdc4bc9-5e6b-4799-8d44-fe4ba5011f30.JPG' => ['Dotwork Mandala',             'Puntillismo para texturas geométricas.'],
    '4bffd227-af5a-445c-bbc4-e7e91ff77e77.JPG' => ['Script Lettering',            'Caligrafía a máquina con variación de grosor.'],
    '4d9083ae-60ee-424e-bab8-ddd815c61271.JPG' => ['Blackwork Bold',              'Relleno total de tinta negra para alto contraste.'],
// Metadatos para nuevas
    '49fa56d2-59e9-40b6-b4e6-302124b71ad5.JPG'  => ['Máscaras Duales',             'Diseño surrealista de dos máscaras entrelazadas.'],
    '0857f40c-23d7-4757-9bdb-4d91d4035506.JPG'  => ['Caligrafía Sutil',            'Tatuaje en línea fina con frase en cursiva delicada.'],
    'ac4952d3-27f5-4035-b62e-25c4bdc8e9ac.JPG'  => ['Rosas y Lettering',           'Rosas realistas combinadas con lettering en negrita.'],
    'b6a0a063-e676-41eb-a188-84cdae822d04.JPG'  => ['Realismo en Brazo',           'Retrato realista de rostro con sombreado suave.'],
    'ba267795-8eff-4806-9fb8-64504b755582.JPG'  => ['Minimalista Pets',            'Siluetas de gato y perro en single line art.'],
    'c569df66-0bc5-496a-ac27-c311b000bc26.JPG'  => ['Memento Mori Geométrico',     'Cráneo y reloj con elementos geométricos.'],
    '5228fdf0-2f24-45ff-9b11-e10f59a82d63.JPG'  => ['Cadena Rota',                 'Cadena en negro con detalle de eslabón roto.']
];

// 3) Métodos de tatuaje para cards
$tattoo_methods = [
    'Pop Art'          => 'Contornos gruesos y rellenos planos, inspirados en arte pop y cómics.',
    'Fine Line'        => 'Líneas ultra finas con single needle para máximo detalle.',
    'Dotwork'          => 'Sombras y texturas con puntillismo manual, ideal para mandalas.',
    'Script Lettering' => 'Caligrafía precisa a máquina con variación de grosor.',
    'Blackwork'        => 'Relleno sólido en negro para alto contraste.',
    'Old School'       => 'Estilo clásico con líneas marcadas y paleta reducida.',
    'Logo & Branding'  => 'Reproducción exacta de logotipos vectoriales.'
];

// 4) Datos de la artista
$artist = [
    'name'        => 'Altivia',
    'location'    => 'Av. del Ingenioso Hidalgo, nº 3, 28906 Getafe, Madrid',
    'specialties' => ['Fine Line', 'Realismo', 'Blackwork', 'Diseños personalizados'],
    'instagram'   => 'https://www.instagram.com/leyenda_tattoo06'
];

// Función de escape HTML
function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Leyenda Barber & Tattoo | Getafe</title>
  <link rel="stylesheet" href="../assets/css/tattoo.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
  <style>
    body { display: flex; flex-direction: column; min-height: 100vh; margin: 0; }
    .page-wrapper { flex: 1; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <div class="page-wrapper">

    <!-- Sección Estudio -->
    <section class="studio-section">
      <div class="studio-bg" style="background-image:url('../assets/images/<?= esc($images['studio']) ?>')"></div>
      <div class="studio-overlay">
        <h2>Leyenda Barber & Tattoo</h2>
        <p>C/ Los Molinos 12, Getafe (Madrid) — Arte en piel con máxima higiene.</p>
      </div>
    </section>

    <!-- Carrusel de Tarjetas -->
    <section class="cards-carousel">
      <div class="swiper cards-swiper">
        <div class="swiper-wrapper">
          <?php foreach ($images['tattoos'] as $file):
              [$title, $desc] = $tattoo_data[$file];
          ?>
            <div class="swiper-slide card">
              <img src="../assets/images/<?= esc($file) ?>" alt="<?= esc($title) ?>" loading="lazy">
              <div class="card-content">
                <h3><?= esc($title) ?></h3>
                <p><?= esc($desc) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
      </div>
    </section>

    <!-- Métodos de Tatuaje como Cards -->
    <section id="methods" class="methods-section">
      <h2>Métodos de Tatuaje</h2>
      <div class="methods-grid">
        <?php foreach ($tattoo_methods as $method => $explanation): ?>
          <div class="method-card">
            <h3><?= esc($method) ?></h3>
            <p><?= esc($explanation) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Sección Artista -->
    <section id="artist" class="artist-section">
      <div class="artist-card">
        <img src="../assets/images/<?= esc($images['artist']) ?>"
             alt="<?= esc($artist['name']) ?>"
             class="artist-photo">
        <div class="artist-info">
          <h2><?= esc($artist['name']) ?></h2>
          <p><strong>Ubicación:</strong> <?= esc($artist['location']) ?></p>
          <p><strong>Especialidades:</strong>
            <?= esc(implode(' · ', $artist['specialties'])) ?>
          </p>
          <a href="<?= esc($artist['instagram']) ?>"
             target="_blank"
             rel="noopener"
             class="btn-accent btn-instagram"
             aria-label="Instagram de <?= esc($artist['name']) ?>">
            <!-- Inline SVG Instagram con degradado oficial -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">
              <defs>
                <linearGradient id="insta-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%"   stop-color="#f09433"/>
                  <stop offset="25%"  stop-color="#e6683c"/>
                  <stop offset="50%"  stop-color="#dc2743"/>
                  <stop offset="75%"  stop-color="#cc2366"/>
                  <stop offset="100%" stop-color="#bc1888"/>
                </linearGradient>
              </defs>
              <path fill="url(#insta-grad)" d="M12 2.163c3.204 0 3.584.012 4.849.07 
                1.366.062 2.633.344 3.608 1.319.975.975 1.257 2.242 
                1.319 3.608.058 1.265.069 1.645.069 4.849s-.012 
                3.584-.069 4.849c-.062 1.366-.344 
                2.633-1.319 3.608-.975.975-2.242 
                1.257-3.608 1.319-1.265.058-1.645.069-4.849.069s-3.584-.012-4.849-.069c-1.366-.062-2.633-.344-3.608-1.319-.975-.975-1.257-2.242-1.319-3.608C2.175 
                15.584 2.163 15.204 2.163 12s.012-3.584.069-4.849c.062-1.366.344-2.633 
                1.319-3.608.975-.975 2.242-1.257 3.608-1.319C8.416 
                2.175 8.796 2.163 12 2.163z"/>
              <path fill="#fff" d="M12 5.838a6.162 6.162 
                0 1 0 0 12.324A6.162 6.162 0 0 0 12 
                5.838zm0 10.162a3.999 3.999 
                0 1 1 0-7.998 3.999 3.999 0 0 1 0 7.998z"/>
              <circle cx="18.406" cy="5.594" r="1.44" fill="#fff"/>
            </svg>
          </a>
        </div>
      </div>
    </section>

    <!-- Contacto y Reservas -->
    <section id="contact" class="contact-section">
      <h2>Contacto y Reservas</h2>
      <form action="../contact_process.php" method="post" class="contact-form" novalidate>
        <input type="text"   name="name"    placeholder="Nombre" required>
        <input type="email"  name="email"   placeholder="Email"  required>
        <textarea name="message" placeholder="Mensaje" rows="4" required></textarea>
        <button type="submit">Enviar</button>
      </form>
    </section>

  </div>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <script>
  window.__TATTOO_SLIDE_COUNT = <?= count($images['tattoos']) ?>;
</script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script src="../assets/js/tattoo.js" defer></script>
</body>
</html>
