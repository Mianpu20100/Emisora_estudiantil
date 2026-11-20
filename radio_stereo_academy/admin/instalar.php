<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| INSTALADOR DEL ADMINISTRADOR
| RADIO STEREO ACADEMY NS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/funciones.php';

iniciarSesionSegura();

$errores = [];
$instalacionCompletada = false;

// Comprobar si ya existe un administrador
$consulta = $conexion->query(
    'SELECT id FROM administrador WHERE id = 1 LIMIT 1'
);

$administradorExiste = (bool) $consulta->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$administradorExiste) {

    exigirTokenCSRF();

    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmarPassword = $_POST['confirmar_password'] ?? '';

    // Validar usuario
    if (
        $usuario === '' ||
        !preg_match('/^[a-zA-Z0-9_.-]{4,50}$/', $usuario)
    ) {
        $errores[] = 'El usuario debe tener entre 4 y 50 caracteres y solo puede contener letras, números, puntos, guiones y guiones bajos.';
    }

    // Validar contraseña
    if (!is_string($password) || strlen($password) < 12) {
        $errores[] = 'La contraseña debe tener al menos 12 caracteres.';
    }

    if ($password !== $confirmarPassword) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    // Crear administrador
    if (empty($errores)) {

        try {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $insertar = $conexion->prepare(
                'INSERT INTO administrador
                (id, usuario, password_hash)
                VALUES (1, :usuario, :password_hash)'
            );

            $insertar->execute([
                ':usuario' => $usuario,
                ':password_hash' => $passwordHash
            ]);

            establecerMensaje(
                'exito',
                'La cuenta administradora fue creada correctamente. Ya puedes iniciar sesión.'
            );

            redireccionar('login.php');

        } catch (PDOException $error) {

            error_log(
                '[Radio Stereo] Error al crear administrador: ' .
                $error->getMessage()
            );

            $errores[] = 'No fue posible crear la cuenta. Comprueba que no exista ya un administrador e inténtalo nuevamente.';

            // Actualizar el estado por si otra solicitud creó la cuenta.
            $consulta = $conexion->query(
                'SELECT id FROM administrador WHERE id = 1 LIMIT 1'
            );

            $administradorExiste = (bool) $consulta->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Instalar administrador | Radio Stereo Academy NS</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 25px;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top, #24564d, transparent 45%),
                #101b27;
            color: #fff;
        }

        .contenedor {
            width: 100%;
            max-width: 460px;
            padding: 35px;
            background: #1b2b3a;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(0,0,0,.35);
        }

        .logo {
            width: 75px;
            height: 75px;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #123f38;
            color: #45d6b0;
            font-size: 30px;
            font-weight: bold;
        }

        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .descripcion {
            text-align: center;
            color: #b8c6d3;
            line-height: 1.5;
            font-size: 14px;
            margin-bottom: 28px;
        }

        label {
            display: block;
            margin: 17px 0 8px;
            font-size: 14px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 14px;
            border-radius: 9px;
            border: 1px solid #405365;
            background: #101b27;
            color: #fff;
            outline: none;
            font-size: 15px;
        }

        input:focus {
            border-color: #45d6b0;
            box-shadow: 0 0 0 3px rgba(69,214,176,.12);
        }

        .ayuda {
            margin-top: 8px;
            font-size: 12px;
            color: #a9bac8;
            line-height: 1.5;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 15px;
            border: none;
            border-radius: 9px;
            background: #168c70;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: .2s;
        }

        button:hover {
            background: #20aa87;
            transform: translateY(-2px);
        }

        .alerta {
            padding: 13px;
            margin-bottom: 15px;
            border-radius: 9px;
            font-size: 13px;
            line-height: 1.5;
        }

        .error {
            background: rgba(220,53,69,.15);
            border: 1px solid #dc3545;
            color: #ffb5bd;
        }

        .bloqueado {
            text-align: center;
            padding: 20px;
            background: rgba(220,53,69,.12);
            border: 1px solid rgba(220,53,69,.5);
            border-radius: 12px;
            line-height: 1.6;
        }

        .pie {
            text-align: center;
            color: #8495a5;
            font-size: 11px;
            margin-top: 25px;
        }

        @media (max-width: 480px) {
            .contenedor {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>

<div class="contenedor">

    <div class="logo">R</div>

    <h1>Radio Stereo Academy NS</h1>

    <p class="descripcion">
        Instalación de la cuenta administradora
    </p>

    <?php if ($administradorExiste): ?>

        <div class="bloqueado">
            <strong>Instalación completada</strong>
            <p>
                Ya existe una cuenta administradora.
                No es posible crear otra desde este instalador.
            </p>
            <p>
                Continúa con el inicio de sesión del panel administrativo.
            </p>
        </div>

    <?php else: ?>

        <?php foreach ($errores as $error): ?>
            <div class="alerta error">
                <?= e($error) ?>
            </div>
        <?php endforeach; ?>

        <form method="POST" action="">

            <?= campoCSRF() ?>

            <label for="usuario">
                Nombre de usuario
            </label>

            <input
                type="text"
                id="usuario"
                name="usuario"
                minlength="4"
                maxlength="50"
                autocomplete="username"
                placeholder="Ejemplo: administrador"
                value="<?= e($_POST['usuario'] ?? '') ?>"
                required
            >

            <label for="password">
                Contraseña
            </label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="12"
                autocomplete="new-password"
                placeholder="Crea una contraseña segura"
                required
            >

            <p class="ayuda">
                Utiliza al menos 12 caracteres.
                Combina letras, números y símbolos.
            </p>

            <label for="confirmar_password">
                Confirmar contraseña
            </label>

            <input
                type="password"
                id="confirmar_password"
                name="confirmar_password"
                minlength="12"
                autocomplete="new-password"
                placeholder="Escribe nuevamente tu contraseña"
                required
            >

            <button type="submit">
                Crear cuenta administradora
            </button>

        </form>

    <?php endif; ?>

    <div class="pie">
        Sistema administrativo · Radio Stereo Academy NS
    </div>

</div>

</body>
</html>
