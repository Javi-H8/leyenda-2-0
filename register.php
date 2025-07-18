<?php
declare(strict_types=1);

// --------------------------------------------------
// register.php — Registro de usuario
// --------------------------------------------------
// • Argon2id (o bcrypt como fallback) para hashear la contraseña :contentReference[oaicite:0]{index=0}
// • No truncar, permitir Unicode y espacios en la contraseña :contentReference[oaicite:1]{index=1}
// • CSRF con token oculto :contentReference[oaicite:2]{index=2}
// • Sesión con cookies Secure, HttpOnly y SameSite=Strict :contentReference[oaicite:3]{index=3}
// • Cabeceras CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy
// • Validación rigurosa de inputs y mensajes genéricos
// --------------------------------------------------

// 1) Forzar HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// 2) Sesión con cookies seguras
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $_SERVER['HTTP_HOST'],
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// 3) Cabeceras de seguridad
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self';");

// 4) Cargas necesarias
require_once __DIR__ . '/config/database.php';  // $pdo
require_once __DIR__ . '/config/mail.php';      // getMailer()
require_once __DIR__ . '/functions.php';        // csrf, tokens, sendVerificationEmail()

$errors = [];

// 5) Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); // OWASP CSRF :contentReference[oaicite:4]{index=4}

    // 5.1 – Saneado
    $email     = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password  = $_POST['password'] ?? '';
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido  = trim($_POST['apellido'] ?? '');
    // Dirección opcional
    $calle     = trim($_POST['calle'] ?? '');
    $ciudad    = trim($_POST['ciudad'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $cp        = trim($_POST['cp'] ?? '');
    $pais      = trim($_POST['pais'] ?? '');

    // 5.2 – Validaciones básicas
    if (!$email) {
        $errors[] = 'Correo no válido.';
    }
    if (mb_strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($nombre === '' || $apellido === '') {
        $errors[] = 'Nombre y apellido son obligatorios.';
    }

    // 5.3 – Si todo OK, registro
    if (empty($errors)) {
        // 5.3.1 – Comprobar duplicados sin revelar cuál falla
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Ya existe una cuenta con esas credenciales.';
        } else {
            // 5.3.2 – Elegir algoritmo de hash seguro
            if (defined('PASSWORD_ARGON2ID')) {
                // Opciones recomendadas por OWASP para Argon2id :contentReference[oaicite:5]{index=5}
                $algo    = PASSWORD_ARGON2ID;
                $options = ['memory_cost' => 1<<17, 'time_cost' => 4, 'threads' => 2];
            } else {
                $algo    = PASSWORD_BCRYPT;
                $options = [];
            }
            $hash = password_hash($password, $algo, $options);

            // 5.3.3 – Insertar usuario con token de verificación
            $token = generateToken();
            $stmt = $pdo->prepare("
                INSERT INTO usuarios
                  (email, password_hash, nombre, apellido,
                   email_verified_at, token_verificacion,
                   activo, created_at)
                VALUES
                  (:email, :ph, :nom, :ape, 
                   NULL, :tok, 1, NOW())
            ");
            $stmt->execute([
                ':email'=> $email,
                ':ph'   => $hash,
                ':nom'  => $nombre,
                ':ape'  => $apellido,
                ':tok'  => $token,
            ]);
            $userId = (int)$pdo->lastInsertId();

            // 5.3.4 – Guardar dirección si la completó
            if ($calle && $ciudad && $cp && $pais) {
                $stmt2 = $pdo->prepare("
                    INSERT INTO direcciones
                      (usuario_id, etiqueta, calle,
                       ciudad, provincia, codigo_postal,
                       pais, created_at)
                    VALUES
                      (:uid, 'Principal', :calle,
                       :ciu, :prov, :cp, :pais, NOW())
                ");
                $stmt2->execute([
                    ':uid'   => $userId,
                    ':calle' => $calle,
                    ':ciu'   => $ciudad,
                    ':prov'  => $provincia,
                    ':cp'    => $cp,
                    ':pais'  => $pais,
                ]);
            }

            // 5.3.5 – Enviar e-mail de verificación
            sendVerificationEmail(
                $email,
                "{$nombre} {$apellido}",
                $token
            );

            // 5.3.6 – Mensaje final
            echo '<p style="color:green">'
               . '¡Registro OK! Revisa tu correo para activar la cuenta.'
               . '</p>';
            exit;
        }
    }
}
include __DIR__ . '/includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registro — Leyenda Clothes</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body {font-family:sans-serif; background:#f5f5f5; padding:20px;}
    form{max-width:400px;margin:auto;background:#fff;padding:20px;border-radius:4px;}
    label{display:block;margin-bottom:8px;}
    input{width:100%;padding:8px;margin-top:4px;}
    .errors{background:#fee;padding:10px;border:1px solid #f99;margin-bottom:10px;}
  </style>
</head>
<body>
  <h1>Crear cuenta</h1>
  <?php if ($errors): ?>
    <div class="errors">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <label>
      Correo electrónico
      <input type="email" name="email" required>
    </label>

    <label>
      Contraseña
      <input type="password" name="password" required minlength="8">
    </label>

    <label>
      Nombre
      <input type="text" name="nombre" required>
    </label>

    <label>
      Apellido
      <input type="text" name="apellido" required>
    </label>

    <hr>
    <p><em>Dirección (opcional)</em></p>

    <label>
      Calle
      <input type="text" name="calle">
    </label>
    <label>
      Ciudad
      <input type="text" name="ciudad">
    </label>
    <label>
      Provincia
      <input type="text" name="provincia">
    </label>
    <label>
      C.P.
      <input type="text" name="cp">
    </label>
    <label>
      País
      <input type="text" name="pais">
    </label>

    <button type="submit">Registrarme</button>
  </form>
  <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
