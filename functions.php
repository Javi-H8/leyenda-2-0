<?php
declare(strict_types=1);
// functions.php
// -----------------------------
// CSRF, generación de tokens y envío de e-mail.
// Colócalo en C:\xampp\htdocs\Leyenda 2.0\functions.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// ----------------------------------------------------------------------------
// 1) Sesión
// ----------------------------------------------------------------------------
// Arranca la sesión si no está activa. No modificar los parámetros de cookie aquí:
// ya los configuras en cada página antes de llamar a session_start().
if (session_status() === PHP_SESSION_NONE) {
}

// ----------------------------------------------------------------------------
// 2) CSRF Protection
// ----------------------------------------------------------------------------
/**
 * Devuelve el token CSRF almacenado en sesión, o lo genera si no existe.
 *
 * @return string Un token seguro de 64 hex caracteres.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica que el token CSRF enviado vía POST coincide con el almacenado en sesión.
 * Si no coincide, detiene la ejecución con código 400.
 *
 * @return void
 */
function verify_csrf(): void
{
    $session = $_SESSION['csrf_token'] ?? '';
    $posted  = $_POST['csrf_token']   ?? '';
    if (!hash_equals($session, $posted)) {
        http_response_code(400);
        exit('Error CSRF: token no válido.');
    }
}

// ----------------------------------------------------------------------------
// 3) Token de verificación de e-mail
// ----------------------------------------------------------------------------
/**
 * Genera un token aleatorio de 32 bytes (64 hex chars) para confirmación de e-mail.
 *
 * @return string
 */
function generateToken(): string
{
    return bin2hex(random_bytes(16));
}

// ----------------------------------------------------------------------------
// 4) Envío de correo de verificación
// ----------------------------------------------------------------------------
/**
 * Envía un correo de verificación al usuario con un enlace único.
 *
 * @param string $toEmail Dirección de destino.
 * @param string $toName  Nombre del destinatario (aparece en el saludo).
 * @param string $token   Token de verificación para incluir en la URL.
 *
 * @return bool True si se envió correctamente, false en caso de error.
 */
function sendVerificationEmail(string $toEmail, string $toName, string $token): bool
{
    // Asegúrate de haber incluido antes config/mail.php, que define getMailer()
    try {
        $mail = getMailer();

        // Opcional: modo debug (cambio en config/mail.php si lo deseas)
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = function($str, $level) {
        //     error_log("SMTP Debug level $level; message: $str");
        // };

        $mail->addAddress($toEmail, $toName);
        $mail->Subject = 'Activa tu cuenta en Leyenda Clothes';

        // Construye URL absoluta a verify.php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'];
        $path   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $url    = sprintf(
            '%s://%s%s/verify.php?token=%s',
            $scheme,
            $host,
            $path,
            urlencode($token)
        );

        // Cuerpo HTML
        $mail->Body = <<<HTML
<p>Hola <strong>{$toName}</strong>,</p>
<p>Gracias por registrarte en <em>Leyenda Clothes</em>.</p>
<p>Para activar tu cuenta, haz clic en el siguiente enlace:</p>
<p><a href="{$url}">Confirmar mi correo</a></p>
<p>Si no solicitaste esto, puedes ignorar este mensaje.</p>
HTML;

        $mail->send();
        return true;
    } catch (MailException $e) {
        // Registra el error para debug
        error_log('Mailer Error: ' . $e->getMessage());
        return false;
    }
}
