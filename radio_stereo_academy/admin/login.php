<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/funciones.php';

iniciarSesionSegura();

// Si ya inició sesión, enviarlo al panel.
if (administradorAutenticado()) {
    redireccionar('index.php');
}

$errores = [];
$mensaje = obtenerMensaje();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    exigirTokenCSRF();

    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || !is_string($password) || $password === '') {

        $errores[] = 'Escribe tu usuario y contraseña.';

    } else {

        try {

            $consulta = $conexion->prepare(
                'SELECT id, usuario, password_hash
                 FROM administrador
                 WHERE usuario = :usuario
                 LIMIT 1'
            );

            $consulta->execute([
                ':usuario' => $usuario
            ]);

            $administrador = $consulta->fetch();

            if (
                $administrador &&
                password_verify($password, $administrador['password_hash'])
            ) {

                // Renovar el identificador de sesión.
                session_regenerate_id(true);

                $_SESSION['administrador_id'] = (int) $administrador['id'];
                $_SESSION['administrador_usuario'] = $administrador['usuario'];

                // Actualizar el hash si PHP recomienda uno más seguro.
                if (password_needs_rehash(
                    $administrador['password_hash'],
                    PASSWORD_DEFAULT
                )) {

                    $nuevoHash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $actualizar = $conexion->prepare(
                        'UPDATE administrador
                         SET password_hash = :hash
                         WHERE id = 1'
                    );

                    $actualizar->execute([
                        ':hash' => $nuevoHash
                    ]);
                }

                redireccionar('index.php');

            } else {

                $errores[] = 'Usuario o contraseña incorrectos.';
            }

        } catch (PDOException $error) {

            error_log(
                '[Radio Stereo] Error de inicio de sesión: ' .
                $error->getMessage()
            );

            $errores[] = 'No fue posible iniciar sesión. Inténtalo nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | Radio Stereo Academy NS</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            color: white;
            background:
                radial-gradient(circle at top, #24564d, transparent 45%),
                #101b27;
        }

        .login-contenedor {
            width: 100%;
            max-width: 430px;
            padding: 35px;
            background: #1b2b3a;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(0,0,0,.35);
        }

        .logo {
            width: 75px;
            height: 75px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #123f38;
            border-radius: 50%;
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
            font-size: 14px;
            line-height: 1.5;
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
            border: 1px solid #405365;
            border-radius: 9px;
            background: #101b27;
            color: white;
            outline: none;
            font-size: 15px;
        }

        input:focus {
            border-color: #45d6b0;
            box-shadow: 0 0 0 3px rgba(69,214,176,.12);
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
            color: #ffb5bd;
            background: rgba(220,53,69,.15);
            border: 1px solid #dc3545;
        }

        .exito {
            color: #9ff0d5;
            background: rgba(69,214,176,.12);
            border: 1px solid #45d6b0;
        }

        .pie {
            text-align: center;
            color: #8495a5;
            font-size: 11px;
            margin-top: 25px;
        }

        @media(max-width: 480px) {
            .login-contenedor {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>

<div class="login-contenedor">

    <div class="logo">R</div>

    <h1>Panel administrativo</h1>

    <p class="descripcion">
        Radio Stereo Academy NS
        <br>
        Ingresa con tu cuenta administradora.
    </p>

    <?php if ($mensaje): ?>
        <div class="alerta exito">
            <?= e($mensaje['texto']) ?>
        </div>
    <?php endif; ?>

    <?php foreach ($errores as $error): ?>
        <div class="alerta error">
            <?= e($error) ?>
        </div>
    <?php endforeach; ?>

    <form method="POST" action="">

        <?= campoCSRF() ?>

        <label for="usuario">Usuario</label>

        <input
            type="text"
            id="usuario"
            name="usuario"
            maxlength="50"
            autocomplete="username"
            placeholder="Nombre de usuario"
            value="<?= e($_POST['usuario'] ?? '') ?>"
            required
            autofocus
        >

        <label for="password">Contraseña</label>

        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            placeholder="Tu contraseña"
            required
        >

        <button type="submit">
            Iniciar sesión
        </button>

    </form>

    <div class="pie">
        Radio Stereo Academy NS · Administración
    </div>

</div>

</body>
</html>
