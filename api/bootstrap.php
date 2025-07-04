<?php
// api/bootstrap.php
declare(strict_types=1);

// 1) Forzar HTTPS
if (
    (!empty($_SERVER['HTTPS'])  && $_SERVER['HTTPS']  !== 'on')
    || (empty($_SERVER['HTTPS'])  && ($_SERVER['SERVER_PORT'] ?? '') !== '443')
) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['error' => 'HTTPS obligatorio']));
}

// 2) Cabeceras de seguridad adicionales (HSTS, CSP, XSS, Clickjacking, etc.)
header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header("Referrer-Policy: no-referrer");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; connect-src 'self';");

// 3) Sesión OWASP reforzada
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure',   '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Strict');
session_start();

// 4) Cabecera común JSON
header('Content-Type: application/json; charset=utf-8');
