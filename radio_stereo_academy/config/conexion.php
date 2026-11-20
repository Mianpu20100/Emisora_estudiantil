<?php
/**
 * RADIO STEREO ACADEMY NS
 * Conexión PDO con MySQL
 */

declare(strict_types=1);

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'radio_stereo_academy');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Opciones de conexión PDO
$opcionesPDO = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conexion = new PDO(
        'mysql:host=' . DB_HOST .
        ';dbname=' . DB_NAME .
        ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        $opcionesPDO
    );

} catch (PDOException $error) {
    // Registrar el error sin mostrar información sensible al visitante.
    error_log(
        '[Radio Stereo Academy NS] Error de conexión: ' .
        $error->getMessage()
    );

    http_response_code(500);

    exit(
        'No fue posible conectar con la base de datos. ' .
        'Por favor, inténtalo más tarde.'
    );
}
