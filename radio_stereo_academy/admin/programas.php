<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/funciones.php';

iniciarSesionSegura();
exigirAdministrador();

$errores = [];
$exito = '';

$directorioSubidas = __DIR__ . '/../uploads/programas/';
$rutaPublica = 'uploads/programas/';

if (!is_dir($directorioSubidas)) {
    if (!mkdir($directorioSubidas, 0755, true) && !is_dir($directorioSubidas)) {
        exit('No fue posible crear la carpeta de imágenes de programación.');
    }
}

/*
|--------------------------------------------------------------------------
| SUBIR Y VALIDAR IMÁGENES
|--------------------------------------------------------------------------
*/

function subirImagenPrograma(
    array $archivo,
    string $directorio
): ?string {
    if (
        !isset($archivo['error']) ||
        $archivo['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo recibir la imagen. Inténtalo nuevamente.');
    }

    if ($archivo['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('La imagen no puede superar los 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);

    $tiposPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($tiposPermitidos[$mime])) {
        throw new RuntimeException('Formato no permitido. Utiliza JPG, PNG o WEBP.');
    }

    $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $tiposPermitidos[$mime];
    $destino = $directorio . $nombreArchivo;

    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        throw new RuntimeException('No fue posible guardar la imagen en el servidor.');
    }

    return 'uploads/programas/' . $nombreArchivo;
}

/*
|--------------------------------------------------------------------------
| PROCESAR FORMULARIOS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    exigirTokenCSRF();

    $accion = $_POST['accion'] ?? '';

    try {

        if ($accion === 'crear' || $accion === 'actualizar') {

            $id = (int) ($_POST['id'] ?? 0);
            $categoria = trim($_POST['categoria'] ?? '');
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $dias = trim($_POST['dias'] ?? '');
            $horaInicio = trim($_POST['hora_inicio'] ?? '');
            $horaFin = trim($_POST['hora_fin'] ?? '');
            $orden = filter_var(
                $_POST['orden'] ?? 0,
                FILTER_VALIDATE_INT
            );

            if ($categoria === '' || mb_strlen($categoria) > 100) {
                throw new RuntimeException('Escribe una categoría de máximo 100 caracteres.');
            }

            if ($titulo === '' || mb_strlen($titulo) > 180) {
                throw new RuntimeException('Escribe un título de máximo 180 caracteres.');
            }

            if ($dias === '' || mb_strlen($dias) > 180) {
                throw new RuntimeException('Indica los días de emisión.');
            }

            if (
                !preg_match('/^\d{2}:\d{2}$/', $horaInicio) ||
                !preg_match('/^\d{2}:\d{2}$/', $horaFin)
            ) {
                throw new RuntimeException('Selecciona horarios válidos.');
            }

            if ($orden === false) {
                throw new RuntimeException('El orden debe ser un número entero.');
            }

            $imagenNueva = subirImagenPrograma(
                $_FILES['imagen'] ?? [],
                $directorioSubidas
            );

            if ($accion === 'crear') {

                $consulta = $conexion->prepare(
                    "INSERT INTO programas
                    (categoria, titulo, descripcion, dias,
                     hora_inicio, hora_fin, imagen, orden, estado)
                    VALUES
                    (:categoria, :titulo, :descripcion, :dias,
                     :hora_inicio, :hora_fin, :imagen, :orden, 'activo')"
                );

                $consulta->execute([
                    ':categoria' => $categoria,
                    ':titulo' => $titulo,
                    ':descripcion' => $descripcion,
                    ':dias' => $dias,
                    ':hora_inicio' => $horaInicio,
                    ':hora_fin' => $horaFin,
                    ':imagen' => $imagenNueva,
                    ':orden' => $orden
                ]);

                establecerMensaje('exito', 'El programa fue creado correctamente.');
                redireccionar('programas.php');
            }

            if ($accion === 'actualizar') {

                if ($id < 1) {
                    throw new RuntimeException('El programa seleccionado no es válido.');
                }

                $consulta = $conexion->prepare(
                    'SELECT id, imagen FROM programas WHERE id = :id LIMIT 1'
                );
                $consulta->execute([':id' => $id]);
                $programaActual = $consulta->fetch();

                if (!$programaActual) {
                    throw new RuntimeException('El programa que intentas editar no existe.');
                }

                $imagenFinal = $imagenNueva ?? $programaActual['imagen'];

                $consulta = $conexion->prepare(
                    'UPDATE programas
                     SET categoria = :categoria,
                         titulo = :titulo,
                         descripcion = :descripcion,
                         dias = :dias,
                         hora_inicio = :hora_inicio,
                         hora_fin = :hora_fin,
                         imagen = :imagen,
                         orden = :orden
                     WHERE id = :id'
                );

                $consulta->execute([
                    ':categoria' => $categoria,
                    ':titulo' => $titulo,
                    ':descripcion' => $descripcion,
                    ':dias' => $dias,
                    ':hora_inicio' => $horaInicio,
                    ':hora_fin' => $horaFin,
                    ':imagen' => $imagenFinal,
                    ':orden' => $orden,
                    ':id' => $id
                ]);

                establecerMensaje('exito', 'El programa fue actualizado correctamente.');
                redireccionar('programas.php');
            }
        }

        if ($accion === 'cambiar_estado') {

            $id = (int) ($_POST['id'] ?? 0);

            if ($id < 1) {
                throw new RuntimeException('El programa seleccionado no es válido.');
            }

            $consulta = $conexion->prepare(
                "UPDATE programas
                 SET estado = CASE
                    WHEN estado = 'activo' THEN 'inactivo'
                    ELSE 'activo'
                 END
                 WHERE id = :id"
            );

            $consulta->execute([':id' => $id]);

            if ($consulta->rowCount() > 0) {
                establecerMensaje('exito', 'El estado del programa fue actualizado.');
            } else {
                establecerMensaje('info', 'No se realizaron cambios en el programa.');
            }

            redireccionar('programas.php');
        }

        throw new RuntimeException('La operación solicitada no es válida.');

    } catch (RuntimeException $error) {

        $errores[] = $error->getMessage();

    } catch (PDOException $error) {

        error_log('[Radio Stereo] Error en programación: ' . $error->getMessage());
        $errores[] = 'Ocurrió un error al guardar los datos. Revisa la información e inténtalo nuevamente.';
    }
}

/*
|--------------------------------------------------------------------------
| CARGAR PROGRAMA PARA EDITAR
|--------------------------------------------------------------------------
*/

$programaEditar = null;
$idEditar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);

if ($idEditar) {
    $consulta = $conexion->prepare(
        'SELECT * FROM programas WHERE id = :id LIMIT 1'
    );
    $consulta->execute([':id' => $idEditar]);
    $programaEditar = $consulta->fetch();

    if (!$programaEditar) {
        $errores[] = 'El programa que buscas editar no existe.';
    }
}

/*
|--------------------------------------------------------------------------
| LISTAR PROGRAMAS
|--------------------------------------------------------------------------
*/

$consulta = $conexion->query(
    'SELECT * FROM programas ORDER BY orden ASC, id DESC'
);

$programas = $consulta->fetchAll();

$mensaje = obtenerMensaje();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Programación radial | Radio Stereo Academy NS</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f2f5f7;
            color: #263640;
        }

        .pagina {
            max-width: 1250px;
            margin: auto;
            padding: 30px 22px;
        }

        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        h1 {
            color: #123f38;
            font-size: 27px;
        }

        .subtitulo {
            margin-top: 7px;
            color: #75838d;
            font-size: 14px;
        }

        .volver {
            display: inline-block;
            padding: 11px 16px;
            background: #123f38;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
        }

        .volver:hover {
            background: #197a65;
        }

        .contenido-grid {
            display: grid;
            grid-template-columns: minmax(280px, 370px) minmax(0, 1fr);
            gap: 22px;
            align-items: start;
        }

        .panel {
            background: white;
            padding: 23px;
            border: 1px solid #e5eaed;
            border-radius: 13px;
            box-shadow: 0 3px 12px rgba(20,40,50,.035);
        }

        .panel h2 {
            font-size: 18px;
            color: #123f38;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin: 15px 0 7px;
            font-size: 13px;
            font-weight: bold;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d7e0e5;
            border-radius: 8px;
            background: #fbfcfd;
            color: #263640;
            font: inherit;
            font-size: 13px;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #168c70;
            box-shadow: 0 0 0 3px rgba(22,140,112,.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .fila {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .ayuda {
            color: #87949c;
            font-size: 11px;
            margin-top: 6px;
            line-height: 1.5;
        }

        .imagen-actual {
            display: block;
            width: 100%;
            max-height: 160px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
        }

        .boton {
            display: inline-block;
            width: 100%;
            margin-top: 20px;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #168c70;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .boton:hover {
            background: #20aa87;
        }

        .cancelar {
            display: block;
            text-align: center;
            margin-top: 13px;
            color: #657581;
            font-size: 13px;
            text-decoration: none;
        }

        .alerta {
            padding: 13px 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.5;
        }

        .exito {
            background: #e0f5eb;
            color: #176342;
        }

        .error {
            background: #fff0f0;
            color: #a32c39;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th {
            padding: 13px 10px;
            text-align: left;
            background: #f4f7f7;
            color: #53656d;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        td {
            padding: 13px 10px;
            border-bottom: 1px solid #edf0f2;
            font-size: 12px;
            vertical-align: middle;
        }

        .miniatura {
            width: 62px;
            height: 48px;
            object-fit: cover;
            border-radius: 7px;
            background: #e8eeee;
        }

        .sin-imagen {
            display: flex;
            width: 62px;
            height: 48px;
            align-items: center;
            justify-content: center;
            background: #e8eeee;
            border-radius: 7px;
            color: #84949b;
            font-size: 10px;
        }

        .estado {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
        }

        .estado.activo {
            background: #e0f5eb;
            color: #176342;
        }

        .estado.inactivo {
            background: #fff0f0;
            color: #a32c39;
        }

        .acciones {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .accion {
            padding: 7px 9px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 11px;
            cursor: pointer;
        }

        .editar {
            background: #e7f0ff;
            color: #2454a5;
        }

        .cambiar {
            background: #f0f1f2;
            color: #47545c;
        }

        .form-estado {
            margin: 0;
        }

        .vacio {
            padding: 30px 15px;
            text-align: center;
            color: #839099;
            font-size: 13px;
        }

        @media(max-width: 950px) {
            .contenido-grid {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 500px) {
            .pagina {
                padding: 20px 12px;
            }

            .panel {
                padding: 17px;
            }

            .fila {
                grid-template-columns: 1fr;
                gap: 0;
            }

            h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

<main class="pagina">

    <header class="encabezado">
        <div>
            <h1>Programación radial</h1>
            <p class="subtitulo">
                Crea, organiza y administra los programas de la emisora.
            </p>
        </div>

        <a href="index.php" class="volver">
            ← Volver al panel
        </a>
    </header>

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

    <div class="contenido-grid">

        <section class="panel">

            <h2>
                <?= $programaEditar ? 'Editar programa' : 'Registrar programa' ?>
            </h2>

            <form method="POST" enctype="multipart/form-data">

                <?= campoCSRF() ?>

                <input
                    type="hidden"
                    name="accion"
                    value="<?= $programaEditar ? 'actualizar' : 'crear' ?>"
                >

                <?php if ($programaEditar): ?>
                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $programaEditar['id'] ?>"
                    >
                <?php endif; ?>

                <label for="categoria">Categoría</label>
                <input
                    type="text"
                    id="categoria"
                    name="categoria"
                    maxlength="100"
                    placeholder="Ej. Musical, educativo, informativo"
                    value="<?= e($programaEditar['categoria'] ?? '') ?>"
                    required
                >

                <label for="titulo">Nombre del programa</label>
                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    maxlength="180"
                    placeholder="Nombre del programa"
                    value="<?= e($programaEditar['titulo'] ?? '') ?>"
                    required
                >

                <label for="descripcion">Descripción</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    placeholder="Describe el contenido del programa..."
                ><?= e($programaEditar['descripcion'] ?? '') ?></textarea>

                <label for="dias">Días de emisión</label>
                <input
                    type="text"
                    id="dias"
                    name="dias"
                    maxlength="180"
                    placeholder="Ej. Lunes a viernes"
                    value="<?= e($programaEditar['dias'] ?? '') ?>"
                    required
                >

                <div class="fila">

                    <div>
                        <label for="hora_inicio">Hora de inicio</label>
                        <input
                            type="time"
                            id="hora_inicio"
                            name="hora_inicio"
                            value="<?= e(substr($programaEditar['hora_inicio'] ?? '08:00:00', 0, 5)) ?>"
                            required
                        >
                    </div>

                    <div>
                        <label for="hora_fin">Hora de finalización</label>
                        <input
                            type="time"
                            id="hora_fin"
                            name="hora_fin"
                            value="<?= e(substr($programaEditar['hora_fin'] ?? '09:00:00', 0, 5)) ?>"
                            required
                        >
                    </div>

                </div>

                <label for="orden">Orden de aparición</label>
                <input
                    type="number"
                    id="orden"
                    name="orden"
                    min="0"
                    step="1"
                    value="<?= e((string) ($programaEditar['orden'] ?? 0)) ?>"
                >

                <label for="imagen">Imagen del programa</label>
                <input
                    type="file"
                    id="imagen"
                    name="imagen"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >

                <p class="ayuda">
                    Formatos permitidos: JPG, PNG y WEBP. Tamaño máximo: 5 MB.
                </p>

                <?php if (!empty($programaEditar['imagen'])): ?>
                    <p class="ayuda">Imagen actualmente guardada:</p>
                    <img
                        class="imagen-actual"
                        src="../<?= e($programaEditar['imagen']) ?>"
                        alt="Imagen actual del programa"
                    >
                <?php endif; ?>

                <button type="submit" class="boton">
                    <?= $programaEditar ? 'Guardar cambios' : 'Registrar programa' ?>
                </button>

                <?php if ($programaEditar): ?>
                    <a href="programas.php" class="cancelar">
                        Cancelar edición
                    </a>
                <?php endif; ?>

            </form>

        </section>

        <section class="panel">

            <h2>Programas registrados (<?= count($programas) ?>)</h2>

            <?php if (empty($programas)): ?>

                <div class="vacio">
                    Todavía no hay programas registrados.
                    Utiliza el formulario para crear el primero.
                </div>

            <?php else: ?>

                <div class="tabla-contenedor">

                    <table>
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Programa</th>
                                <th>Horario</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($programas as $programa): ?>

                                <tr>

                                    <td>
                                        <?php if (!empty($programa['imagen'])): ?>
                                            <img
                                                class="miniatura"
                                                src="../<?= e($programa['imagen']) ?>"
                                                alt="<?= e($programa['titulo']) ?>"
                                            >
                                        <?php else: ?>
                                            <div class="sin-imagen">Sin imagen</div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <strong><?= e($programa['titulo']) ?></strong>
                                        <br>
                                        <span style="color:#839099">
                                            <?= e($programa['categoria']) ?>
                                        </span>
                                        <br>
                                        <span style="color:#839099">
                                            <?= e($programa['dias']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e(horaFormateada($programa['hora_inicio'])) ?>
                                        <br>
                                        a
                                        <?= e(horaFormateada($programa['hora_fin'])) ?>
                                    </td>

                                    <td>
                                        <span class="estado <?= e($programa['estado']) ?>">
                                            <?= e(ucfirst($programa['estado'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="acciones">

                                            <a
                                                class="accion editar"
                                                href="programas.php?editar=<?= (int) $programa['id'] ?>"
                                            >
                                                Editar
                                            </a>

                                            <form
                                                method="POST"
                                                class="form-estado"
                                                onsubmit="return confirm('¿Deseas cambiar el estado de este programa?');"
                                            >

                                                <?= campoCSRF() ?>

                                                <input
                                                    type="hidden"
                                                    name="accion"
                                                    value="cambiar_estado"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $programa['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="accion cambiar"
                                                >
                                                    <?= $programa['estado'] === 'activo'
                                                        ? 'Desactivar'
                                                        : 'Activar' ?>
                                                </button>

                                            </form>

                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

            <?php endif; ?>

        </section>

    </div>

</main>

</body>
</html>
