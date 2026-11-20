<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/funciones.php';

exigirAdministrador();

$secciones = [
    'historia' => [
        'nombre' => 'Historia de la emisora',
        'descripcion' => 'Cuenta cómo nació Radio Stereo Academy NS y cómo ha evolucionado.'
    ],
    'mision' => [
        'nombre' => 'Misión',
        'descripcion' => 'Describe el propósito y compromiso de la emisora.'
    ],
    'vision' => [
        'nombre' => 'Visión',
        'descripcion' => 'Describe lo que la emisora quiere alcanzar en el futuro.'
    ]
];

$error = '';

/*
|--------------------------------------------------------------------------
| Guardar contenido
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        exigirTokenCSRF();

        $conexion->beginTransaction();

        foreach ($secciones as $clave => $datos) {
            $titulo = trim($_POST['titulo'][$clave] ?? '');
            $contenido = trim($_POST['contenido'][$clave] ?? '');

            if ($titulo === '' || $contenido === '') {
                throw new RuntimeException(
                    'Debes completar el título y el contenido de todas las secciones.'
                );
            }

            if (mb_strlen($titulo) > 150) {
                throw new RuntimeException(
                    'El título de ' . $datos['nombre'] . ' es demasiado largo.'
                );
            }

            $consulta = $conexion->prepare("
                INSERT INTO contenido_pagina (clave, titulo, contenido)
                VALUES (:clave, :titulo, :contenido)
                ON DUPLICATE KEY UPDATE
                    titulo = VALUES(titulo),
                    contenido = VALUES(contenido)
            ");

            $consulta->execute([
                ':clave' => $clave,
                ':titulo' => $titulo,
                ':contenido' => $contenido
            ]);
        }

        $conexion->commit();

        header('Location: contenido.php?guardado=1');
        exit;

    } catch (Throwable $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        error_log('Error al guardar contenido institucional: ' . $e->getMessage());

        $error = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'No fue posible guardar el contenido. Inténtalo nuevamente.';
    }
}

/*
|--------------------------------------------------------------------------
| Obtener contenido
|--------------------------------------------------------------------------
*/

$consulta = $conexion->query("
    SELECT clave, titulo, contenido
    FROM contenido_pagina
    WHERE clave IN ('historia', 'mision', 'vision')
");

$contenidoGuardado = [];

foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $contenidoGuardado[$fila['clave']] = $fila;
}

/*
|--------------------------------------------------------------------------
| Valores predeterminados para secciones inexistentes
|--------------------------------------------------------------------------
*/

$valoresPredeterminados = [
    'historia' => [
        'titulo' => 'Nuestra historia',
        'contenido' => ''
    ],
    'mision' => [
        'titulo' => 'Nuestra misión',
        'contenido' => ''
    ],
    'vision' => [
        'titulo' => 'Nuestra visión',
        'contenido' => ''
    ]
];

foreach ($secciones as $clave => $datos) {
    if (!isset($contenidoGuardado[$clave])) {
        $contenidoGuardado[$clave] = $valoresPredeterminados[$clave];
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contenido institucional | Administración</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            background: #f3f5f7;
            color: #263238;
            font-family: Arial, sans-serif;
        }

        .contenedor {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }

        .encabezado {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .encabezado h1 {
            margin: 0 0 8px;
            color: #123f38;
            font-size: 27px;
        }

        .encabezado p {
            margin: 0;
            color: #68747b;
            font-size: 14px;
        }

        .boton-volver {
            display: inline-block;
            padding: 11px 17px;
            background: #123f38;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            font-size: 14px;
        }

        .boton-volver:hover {
            background: #19584e;
        }

        .aviso {
            padding: 14px 17px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .aviso.exito {
            background: #dff5e8;
            color: #176b3a;
        }

        .aviso.error {
            background: #ffe4e4;
            color: #a12626;
        }

        .introduccion {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 22px;
            border: 1px solid #e5e9eb;
            line-height: 1.6;
            color: #5e6a70;
        }

        .seccion {
            background: white;
            border: 1px solid #e5e9eb;
            border-radius: 10px;
            padding: 23px;
            margin-bottom: 20px;
        }

        .seccion h2 {
            color: #123f38;
            font-size: 19px;
            margin: 0 0 8px;
        }

        .seccion .descripcion {
            color: #718087;
            font-size: 13px;
            margin: 0 0 20px;
            line-height: 1.5;
        }

        .campo {
            margin-bottom: 18px;
        }

        .campo label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
            color: #34434a;
        }

        .campo input,
        .campo textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd4d8;
            border-radius: 7px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            outline: none;
            transition: border-color .2s;
        }

        .campo input:focus,
        .campo textarea:focus {
            border-color: #00a995;
        }

        .campo textarea {
            min-height: 170px;
            resize: vertical;
            line-height: 1.6;
        }

        .ayuda {
            color: #829096;
            font-size: 12px;
            margin-top: 7px;
        }

        .acciones {
            display: flex;
            justify-content: flex-end;
            margin: 25px 0;
        }

        .boton-guardar {
            border: none;
            padding: 14px 24px;
            border-radius: 7px;
            background: #123f38;
            color: white;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
        }

        .boton-guardar:hover {
            background: #19584e;
        }

        @media (max-width: 600px) {
            body {
                padding: 15px;
            }

            .encabezado {
                align-items: flex-start;
                flex-direction: column;
            }

            .seccion {
                padding: 17px;
            }

            .boton-guardar {
                width: 100%;
            }

            .acciones {
                display: block;
            }
        }
    </style>
</head>

<body>

<div class="contenedor">

    <header class="encabezado">
        <div>
            <h1>Contenido institucional</h1>
            <p>Administra la información principal de Radio Stereo Academy NS.</p>
        </div>

        <a href="index.php" class="boton-volver">
            Volver al panel
        </a>
    </header>

    <?php if (isset($_GET['guardado'])): ?>
        <div class="aviso exito">
            El contenido institucional se guardó correctamente.
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="aviso error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="introduccion">
        Desde este módulo puedes modificar la historia, misión y visión
        de la emisora. Los cambios se guardan en la base de datos y
        posteriormente se mostrarán en la página pública.
    </div>

    <form method="POST" action="contenido.php">

        <?= campoCSRF() ?>

        <?php foreach ($secciones as $clave => $datos): ?>

            <section class="seccion">

                <h2><?= e($datos['nombre']) ?></h2>

                <p class="descripcion">
                    <?= e($datos['descripcion']) ?>
                </p>

                <div class="campo">
                    <label for="titulo_<?= e($clave) ?>">
                        Título
                    </label>

                    <input
                        type="text"
                        id="titulo_<?= e($clave) ?>"
                        name="titulo[<?= e($clave) ?>]"
                        maxlength="150"
                        required
                        value="<?= e($contenidoGuardado[$clave]['titulo']) ?>"
                    >
                </div>

                <div class="campo">
                    <label for="contenido_<?= e($clave) ?>">
                        Contenido
                    </label>

                    <textarea
                        id="contenido_<?= e($clave) ?>"
                        name="contenido[<?= e($clave) ?>]"
                        required
                        placeholder="Escribe aquí el contenido..."
                    ><?= e($contenidoGuardado[$clave]['contenido']) ?></textarea>

                    <div class="ayuda">
                        Puedes utilizar párrafos y saltos de línea.
                    </div>
                </div>

            </section>

        <?php endforeach; ?>

        <div class="acciones">
            <button type="submit" class="boton-guardar">
                Guardar cambios
            </button>
        </div>

    </form>

</div>

</body>
</html>
