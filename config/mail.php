<?php
// config/mail.php
// --------------------------------------------------
// PHPMailer manual (sin Composer) + Gmail SMTP
// --------------------------------------------------

// Credenciales de tu cuenta Gmail
$mailUser     = 'leyendaclothes@gmail.com';
$mailPass     = 'Leyenda88.';        // App Password o contraseña de Gmail
$mailFromName = 'Leyenda Clothes';

// Carga manual de las clases de PHPMailer
require __DIR__ . '/../vendor/phpmailer/PHPMailer/src/Exception.php';
require __DIR__ . '/../vendor/phpmailer/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../vendor/phpmailer/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Devuelve un PHPMailer configurado para enviar correos vía Gmail SMTP.
 *
 * @return PHPMailer
 * @throws Exception si ocurre un error en la configuración
 */
function getMailer(): PHPMailer {
    global $mailUser, $mailPass, $mailFromName;

    $mail = new PHPMailer(true);

    // Servidor SMTP de Gmail
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $mailUser;
    $mail->Password   = $mailPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Usa STARTTLS
    $mail->Port       = 587;

    // Remitente
    $mail->setFrom($mailUser, $mailFromName);

    // Formato de correo
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    return $mail;
}
