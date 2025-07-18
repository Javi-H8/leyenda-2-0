<?php
declare(strict_types=1);

/**
 * login.php — Autenticación de usuario moderno
 * 
 * 1) HTTPS forzado + HSTS
 * 2) Sesión con cookies Secure, HttpOnly y SameSite=Strict
 * 3) Cabeceras de seguridad HTTP
 * 4) CSRF protection
 * 5) Throttling: bloqueo tras N intentos en T segundos
 * 6) Validación de input y password_verify()
 * 7) session_regenerate_id() al loguear
 * 8) Redirección a index.php
 */

// 1) Forzar HTTPS y HSTS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

// 2) Configurar cookies de sesión antes de session_start() :contentReference[oaicite:5]{index=5}
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $_SERVER['HTTP_HOST'],
    'secure'   => true,               // Sólo HTTPS :contentReference[oaicite:6]{index=6}
    'httponly' => true,               // No accesible desde JS :contentReference[oaicite:7]{index=7}
    'samesite' => 'Strict',           // Mitiga CSRF :contentReference[oaicite:8]{index=8}
]);
session_start();

// 3) Cargar configuración y helpers
require_once __DIR__ . '/config/database.php';  // $pdo (PDO preparado)
require_once __DIR__ . '/functions.php';         // csrf_token(), verify_csrf(), etc.

// 4) Cabeceras adicionales de seguridad
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self';");

// 5) Throttling: 5 intentos en 15 minutos
$maxAttempts = 5;
$lockoutSecs = 15 * 60;
if (!isset($_SESSION['login_attempts'], $_SESSION['first_attempt_time'])) {
    $_SESSION['login_attempts']           = 0;
    $_SESSION['first_attempt_time']       = time();
} elseif ($_SESSION['login_attempts'] >= $maxAttempts) {
    if (time() - $_SESSION['first_attempt_time'] < $lockoutSecs) {
        $error = 'Demasiados intentos. Prueba de nuevo más tarde.';
    } else {
        // Resetear contador tras tiempo de bloqueo
        $_SESSION['login_attempts']         = 0;
        $_SESSION['first_attempt_time']     = time();
    }
}

// 6) Procesar POST
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    verify_csrf(); // OWASP CSRF :contentReference[oaicite:9]{index=9}

    // Sanear y validar input
    $email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || $password === '') {
        $error = 'Introduce correo y contraseña.';
    } else {
        // 6.1) Recuperar usuario
        $stmt = $pdo->prepare("
            SELECT id, password_hash, email_verified_at, activo
              FROM usuarios
             WHERE email = :email
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // 6.2) Verificar credenciales y estado
        if (
            $user &&
            (int)$user['activo'] === 1 &&
            $user['email_verified_at'] !== null &&
            password_verify($password, $user['password_hash']) // Argon2id o bcrypt :contentReference[oaicite:10]{index=10}
        ) {
            // 7) Regenerar ID de sesión para prevenir fijación :contentReference[oaicite:11]{index=11}
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];

            // Redirigir a la página principal
            header('Location: index.php', true, 302);
            exit;
        }

        // Credenciales inválidas
        $error = 'Credenciales inválidas.';
        $_SESSION['login_attempts']++;
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="auth-wrapper">
  <div class="auth-card">
    <h1>Entrar</h1>

    <?php if (!empty($error)): ?>
      <div class="auth-error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form method="post" novalidate class="auth-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">

      <label>
        Correo electrónico
        <input type="email" name="email" required>
      </label>

      <label for="password">Contraseña</label>
      <input
        id="password"
        type="password"
        name="password"
        required
        placeholder="••••••••"
      />
      
      <button type="submit">Entrar</button>

      <p class="auth-alt">
        ¿No tienes cuenta? <a href="register.php">Regístrate</a>
      </p>
    </form>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
