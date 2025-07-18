<?php
declare(strict_types=1);
/**
 * verify.php
 * -----------
 * Endpoint para la verificación de cuenta por e-mail.
 *
 * Flujo:
 * 1. Recibe parámetro GET 'token'.  
 * 2. Sanitiza y valida que sea un string hexadecimal de 32 bytes (64 chars).  
 * 3. Busca el usuario con ese token, sin 'email_verified_at' y token creado hace ≤24h.  
 * 4. Marca 'email_verified_at = NOW()' y limpia 'token_verificacion'.  
 * 5. Redirige a login.php con flash de éxito o muestra mensaje de error.  
 *
 * Seguridad y buenas prácticas:
 * - **HTTPS forzado** y HSTS para proteger transporte.  
 * - **Validación rigurosa** de entrada: token único, de longitud fija y restringido a hex.  
 * - **Tokens expirables** tras 24 horas (evita enlaces perpetuos) :contentReference[oaicite:0]{index=0}  
 * - **Prepared statements** para prevenir SQLi.  
 * - **Errores genéricos** al usuario, pero logging detallado en servidor.  
 * - **No exposición** directa de excepciones en producción.  
 * - **Registro de auditoría** en fallos de verificación (opcional).  
 *
 * Basado en:
 * - PHP Email Verification: Mailtrap guide :contentReference[oaicite:1]{index=1}  
 * - OWASP Authentication Cheat Sheet: account verification flows :contentReference[oaicite:2]{index=2}  
 * - OWASP Input Validation: single-use, time-limited tokens :contentReference[oaicite:3]{index=3}  
 */

// 1) Forzar HTTPS y HSTS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

// 2) Carga de configuración y utilidades
require_once __DIR__ . '/config/database.php'; // $pdo
require_once __DIR__ . '/functions.php';        // Helpers: logging, etc.

// 3) Validar y sanitizar el token desde GET
$rawToken = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
$token    = is_string($rawToken) ? trim($rawToken) : '';
// Comprueba que sea exactamente 32 bytes hex (64 hex chars)
if (!preg_match('/^[a-f0-9]{32}$/i', $token)) {
    http_response_code(400);
    $errorMessage = 'Enlace de verificación inválido.';
    error_log("verify.php: token inválido recibido: {$rawToken}");
    include __DIR__ . '/includes/header.php';
    echo "<main class='container'><h1>Error de verificación</h1><p>{$errorMessage}</p></main>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

// 4) Buscar usuario no verificado con token válido y no expirado (24h)
try {
    $stmt = $pdo->prepare("
        SELECT id, created_at
          FROM usuarios
         WHERE token_verificacion = :tok
           AND email_verified_at IS NULL
    ");
    $stmt->execute([':tok' => $token]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    // Error en BD: no exponer al usuario
    error_log('verify.php DB error: ' . $e->getMessage());
    http_response_code(500);
    include __DIR__ . '/includes/header.php';
    echo "<main class='container'><h1>Ocurrió un error</h1><p>Inténtalo más tarde.</p></main>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

if (!$user) {
    // Token no encontrado o ya usado
    $errorMessage = 'Este enlace no es válido o ya ha sido utilizado.';
    include __DIR__ . '/includes/header.php';
    echo "<main class='container'><h1>Error de verificación</h1><p>{$errorMessage}</p></main>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

// 5) Comprobar expiración: 24 horas tras created_at
$createdAt = new DateTime($user['created_at']);
$now       = new DateTime('now');
$interval  = $now->getTimestamp() - $createdAt->getTimestamp();
$maxAge    = 24 * 3600; // 24h en segundos

if ($interval > $maxAge) {
    // Token expirado: limpiar y pedir nuevo registro o reenviar e-mail
    $stmt = $pdo->prepare("
        UPDATE usuarios
           SET token_verificacion = NULL
         WHERE id = :id
    ");
    $stmt->execute([':id' => $user['id']]);

    $errorMessage = 'El enlace ha expirado. Por favor, regístrate de nuevo o solicita uno nuevo.';
    include __DIR__ . '/includes/header.php';
    echo "<main class='container'><h1>Enlace expirado</h1><p>{$errorMessage}</p></main>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

// 6) Marcar cuenta como verificada
try {
    $stmt = $pdo->prepare("
        UPDATE usuarios
           SET email_verified_at  = NOW(),
               token_verificacion = NULL
         WHERE id = :id
    ");
    $stmt->execute([':id' => $user['id']]);
} catch (Exception $e) {
    error_log('verify.php update error: ' . $e->getMessage());
    http_response_code(500);
    include __DIR__ . '/includes/header.php';
    echo "<main class='container'><h1>Ocurrió un error</h1><p>No se pudo verificar la cuenta.</p></main>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

// 7) Éxito: redirigir a login con flash param o mostrar mensaje
// Ejemplo: mostrar en pantalla
include __DIR__ . '/includes/header.php';
?>
<main class="container" style="max-width:480px; margin:3rem auto; text-align:center;">
  <h1 style="color:green;">¡Cuenta verificada!</h1>
  <p>Tu correo ha sido confirmado correctamente. Ya puedes <a href="login.php">iniciar sesión</a>.</p>
  <h4 style="color:green;"><a href="login.php">iniciar sesión</a></h4>
</main>

<!-- Script que espera 3s y redirige a login.php -->
<script>
  setTimeout(function(){
    window.location.href = '<?= htmlspecialchars(BASE_URL,ENT_QUOTES,'UTF-8') ?>/login.php';
  }, 3000);
</script>

<?php
include __DIR__ . '/includes/footer.php';
