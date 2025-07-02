<?php
// config/database.php
// --------------------------------------------------
// Configuración de conexión a la base de datos MySQL
// Usando PDO con máxima seguridad y flexibilidad
// --------------------------------------------------

/**
 * Recomendación: almacena tus credenciales en variables de entorno
 * (.env) y recupéralas con getenv(), por ejemplo:
 *
 *   DB_HOST=localhost
 *   DB_NAME=tienda_ropa
 *   DB_USER=mi_usuario
 *   DB_PASS=mi_password
 *
 * y luego:
 *   $host = getenv('DB_HOST');
 *   $db   = getenv('DB_NAME');
 *   ...
 */

// Parámetros de conexión
$host   = 'localhost';
$db     = 'tienda_ropa';
$user   = 'root';        // <<< asegúrate de que aquí pones root
$pass   = '';            // <<< y aquí tu contraseña (vacía en XAMPP por defecto)
$charset= 'utf8mb4';          // juego de caracteres

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Opciones recomendadas para PDO
$options = [
    // Fuerza que lance excepciones ante errores
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Desactiva emulación de prepared statements para seguridad
    PDO::ATTR_EMULATE_PREPARES   => false,
    // Modo de fetch por defecto: asociativo
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Activa persistent connections (opcional, medir rendimiento)
    // PDO::ATTR_PERSISTENT       => true,
];

try {
    // Crear instancia de PDO
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // En desarrollo puedes mostrar $e->getMessage()
    // En producción, registra el error y muestra un mensaje genérico
    error_log('Error de conexión BD: ' . $e->getMessage());
    exit('Error al conectar con la base de datos.');
}
