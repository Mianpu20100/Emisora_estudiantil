<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/funciones.php';

iniciarSesionSegura();

// Eliminar los datos de la sesión.
$_SESSION = [];

// Eliminar la cookie de sesión.
if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $parametros['path'],
            'domain' => $parametros['domain'],
            'secure' => $parametros['secure'],
            'httponly' => $parametros['httponly'],
            'samesite' => 'Lax'
        ]
    );
}

session_destroy();

header('Location: login.php');
exit;
