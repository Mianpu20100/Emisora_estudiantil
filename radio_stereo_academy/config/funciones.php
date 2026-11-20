<?php
declare(strict_types=1);
/**
 * RADIO STEREO ACADEMY NS
 * Funciones generales y seguridad
 */

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SESIONES SEGURAS
|--------------------------------------------------------------------------
*/

function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (headers_sent()) {
        throw new RuntimeException(
            'No se puede iniciar la sesión porque ya se enviaron encabezados.'
        );
    }

    $esHttps = (
        isset($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    );

    session_name('RADIO_STEREO_SESSION');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $esHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

/*
|--------------------------------------------------------------------------
| PROTECCIÓN CONTRA XSS
|--------------------------------------------------------------------------
*/

function e(?string $valor): string
{
    return htmlspecialchars(
        $valor ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| REDIRECCIONES
|--------------------------------------------------------------------------
*/

function redireccionar(string $ruta): void
{
    header('Location: ' . $ruta);
    exit;
}

/*
|--------------------------------------------------------------------------
| TOKEN CSRF
|--------------------------------------------------------------------------
*/

function generarTokenCSRF(): string
{
    iniciarSesionSegura();

    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['token_csrf'];
}

function campoCSRF(): string
{
    return '<input type="hidden" name="token_csrf" value="' .
        e(generarTokenCSRF()) .
        '">';
}

function verificarTokenCSRF(): bool
{
    iniciarSesionSegura();

    $tokenRecibido = $_POST['token_csrf'] ?? '';
    $tokenGuardado = $_SESSION['token_csrf'] ?? '';

    if (
        !is_string($tokenRecibido) ||
        !is_string($tokenGuardado) ||
        $tokenRecibido === '' ||
        $tokenGuardado === ''
    ) {
        return false;
    }

    return hash_equals(
        $tokenGuardado,
        $tokenRecibido
    );
}

function exigirTokenCSRF(): void
{
    if (!verificarTokenCSRF()) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga la página e inténtalo nuevamente.');
    }
}

/*
|--------------------------------------------------------------------------
| MENSAJES TEMPORALES
|--------------------------------------------------------------------------
*/

function establecerMensaje(
    string $tipo,
    string $mensaje
): void {
    iniciarSesionSegura();

    $tiposPermitidos = [
        'exito',
        'error',
        'advertencia',
        'info'
    ];

    if (!in_array($tipo, $tiposPermitidos, true)) {
        $tipo = 'info';
    }

    $_SESSION['mensaje_temporal'] = [
        'tipo' => $tipo,
        'texto' => $mensaje
    ];
}

function obtenerMensaje(): ?array
{
    iniciarSesionSegura();

    if (!isset($_SESSION['mensaje_temporal'])) {
        return null;
    }

    $mensaje = $_SESSION['mensaje_temporal'];

    unset($_SESSION['mensaje_temporal']);

    return $mensaje;
}

/*
|--------------------------------------------------------------------------
| CONTROL DE ADMINISTRADOR
|--------------------------------------------------------------------------
*/

function administradorAutenticado(): bool
{
    iniciarSesionSegura();

    return isset($_SESSION['administrador_id']) &&
        (int) $_SESSION['administrador_id'] === 1;
}

function exigirAdministrador(): void
{
    if (!administradorAutenticado()) {
        redireccionar('login.php');
    }
}

/*
|--------------------------------------------------------------------------
| SLUG PARA NOTICIAS
|--------------------------------------------------------------------------
*/

function generarSlug(string $texto): string
{
    $texto = trim($texto);

    if (function_exists('iconv')) {
        $convertido = iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $texto
        );

        if ($convertido !== false) {
            $texto = $convertido;
        }
    }

    $texto = strtolower($texto);

    $texto = preg_replace(
        '/[^a-z0-9]+/',
        '-',
        $texto
    ) ?? '';

    return trim($texto, '-');
}

/*
|--------------------------------------------------------------------------
| FORMATO DE FECHAS
|--------------------------------------------------------------------------
*/

function fechaFormateada(
    ?string $fecha,
    string $formato = 'd/m/Y'
): string {
    if (empty($fecha)) {
        return '';
    }

    try {
        $fechaObjeto = new DateTime($fecha);

        return $fechaObjeto->format($formato);

    } catch (Exception $error) {
        return '';
    }
}

/*
|--------------------------------------------------------------------------
| FORMATO DE HORAS
|--------------------------------------------------------------------------
*/

function horaFormateada(?string $hora): string
{
    if (empty($hora)) {
        return '';
    }

    try {
        $fecha = new DateTime($hora);

        return $fecha->format('h:i A');

    } catch (Exception $error) {
        return '';
    }
}

/*
|--------------------------------------------------------------------------
| UTILIDADES
|--------------------------------------------------------------------------
*/

function obtenerConfiguracion(
    PDO $conexion,
    string $clave,
    string $valorPredeterminado = ''
): string {
    $consulta = $conexion->prepare(
        'SELECT valor
         FROM configuracion
         WHERE clave = :clave
         LIMIT 1'
    );

    $consulta->execute([
        ':clave' => $clave
    ]);

    $resultado = $consulta->fetchColumn();

    return $resultado !== false
        ? (string) $resultado
        : $valorPredeterminado;
}
