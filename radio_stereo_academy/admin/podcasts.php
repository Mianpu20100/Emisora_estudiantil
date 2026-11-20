<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/funciones.php';

iniciarSesionSegura();
exigirAdministrador();

$errores = [];

$directorioAudio = __DIR__ . '/../uploads/podcasts/';
$directorioPortadas = __DIR__ . '/../uploads/podcasts/portadas/';

foreach ([$directorioAudio, $directorioPortadas] as $directorio) {
    if (!is_dir($directorio)) {
        if (!mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            exit('No fue posible preparar las carpetas de podcasts.');
        }
    }
}

/*
|--------------------------------------------------------------------------
| SUBIR PORTADA
|--------------------------------------------------------------------------
*/

function subirPortadaPodcast(array $archivo, string $directorio): ?string
{
    if (!isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No fue posible recibir la portada.');
    }

    if ($archivo['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('La portada no puede superar los 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);

    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($permitidos[$mime])) {
        throw new RuntimeException('La portada debe ser JPG, PNG o WEBP.');
    }

    $nombre = bin2hex(random_bytes(16)) . '.' . $permitidos[$mime];

    if (!move_uploaded_file($archivo['tmp_name'], $directorio . $nombre)) {
        throw new RuntimeException('No fue posible guardar la portada.');
    }

    return 'uploads/podcasts/portadas/' . $nombre;
}

/*
|--------------------------------------------------------------------------
| SUBIR AUDIO
|--------------------------------------------------------------------------
*/

function subirAudioPodcast(array $archivo, string $directorio): ?string
{
    if (!isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        if ($archivo['error'] === UPLOAD_ERR_INI_SIZE ||
            $archivo['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('El audio supera el límite de subida permitido por PHP.');
        }

        throw new RuntimeException('No fue posible recibir el archivo de audio.');
    }

    if ($archivo['size'] > 50 * 1024 * 1024) {
        throw new RuntimeException('El audio no puede superar los 50 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);

    $permitidos = [
        'audio/mpeg' => 'mp3',
        'audio/mp3' => 'mp3',
        'audio/ogg' => 'ogg',
        'application/ogg' => 'ogg',
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'audio/mp4' => 'm4a',
        'audio/x-m4a' => 'm4a',
        'video/mp4' => 'm4a',
        'audio/aac' => 'aac',
        'audio/flac' => 'flac',
        'audio/x-flac' => 'flac',
        'audio/webm' => 'webm'
    ];

    if (!isset($permitidos[$mime])) {
        throw new RuntimeException('Formato de audio no permitido. Utiliza MP3, OGG, WAV, M4A, AAC o FLAC.');
    }

    $nombre = bin2hex(random_bytes(16)) . '.' . $permitidos[$mime];

    if (!move_uploaded_file($archivo['tmp_name'], $directorio . $nombre)) {
        throw new RuntimeException('No fue posible guardar el audio.');
    }

    return 'uploads/podcasts/' . $nombre;
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
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $duracion = trim($_POST['duracion'] ?? '');
            $fecha = trim($_POST['fecha_publicacion'] ?? '');

            if ($titulo === '' || mb_strlen($titulo) > 180) {
                throw new RuntimeException('Escribe un título de máximo 180 caracteres.');
            }

            if (mb_strlen($duracion) > 30) {
                throw new RuntimeException('La duración no puede superar los 30 caracteres.');
            }

            if ($fecha !== '') {
                $fechaValida = DateTime::createFromFormat('!Y-m-d', $fecha);

                if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
                    throw new RuntimeException('La fecha de publicación no es válida.');
                }
            } else {
                $fecha = null;
            }

            // Recuperar los archivos actuales cuando se está editando.
            $podcastActual = null;

            if ($accion === 'actualizar') {
                if ($id < 1) {
                    throw new RuntimeException('El episodio seleccionado no es válido.');
                }

                $consulta = $conexion->prepare(
                    'SELECT * FROM podcasts WHERE id = :id LIMIT 1'
                );
                $consulta->execute([':id' => $id]);
                $podcastActual = $consulta->fetch();

                if (!$podcastActual) {
                    throw new RuntimeException('El episodio que intentas editar no existe.');
                }
            }

            $portadaNueva = subirPortadaPodcast(
                $_FILES['portada'] ?? [],
                $directorioPortadas
            );

            $audioNuevo = subirAudioPodcast(
                $_FILES['audio'] ?? [],
                $directorioAudio
            );

            if ($accion === 'crear' && $audioNuevo === null) {
                throw new RuntimeException('Debes seleccionar un archivo de audio para crear el episodio.');
            }

            if ($accion === 'crear') {

                $consulta = $conexion->prepare(
                    "INSERT INTO podcasts
                    (titulo, descripcion, portada, archivo_audio,
                     duracion, fecha_publicacion, estado)
                    VALUES
                    (:titulo, :descripcion, :portada, :audio,
                     :duracion, :fecha, 'activo')"
                );

                $consulta->execute([
                    ':titulo' => $titulo,
                    ':descripcion' => $descripcion,
                    ':portada' => $portadaNueva,
                    ':audio' => $audioNuevo,
                    ':duracion' => $duracion ?: null,
                    ':fecha' => $fecha
                ]);

                establecerMensaje('exito', 'El podcast fue registrado correctamente.');
                redireccionar('podcasts.php');
            }

            if ($accion === 'actualizar') {

                $portadaFinal = $portadaNueva ?? $podcastActual['portada'];
                $audioFinal = $audioNuevo ?? $podcastActual['archivo_audio'];

                $consulta = $conexion->prepare(
                    'UPDATE podcasts
                     SET titulo = :titulo,
                         descripcion = :descripcion,
                         portada = :portada,
                         archivo_audio = :audio,
                         duracion = :duracion,
                         fecha_publicacion = :fecha
                     WHERE id = :id'
                );

                $consulta->execute([
                    ':titulo' => $titulo,
                    ':descripcion' => $descripcion,
                    ':portada' => $portadaFinal,
                    ':audio' => $audioFinal,
                    ':duracion' => $duracion ?: null,
                    ':fecha' => $fecha,
                    ':id' => $id
                ]);

                establecerMensaje('exito', 'El podcast fue actualizado correctamente.');
                redireccionar('podcasts.php');
            }
        }

        if ($accion === 'cambiar_estado') {

            $id = (int) ($_POST['id'] ?? 0);

            if ($id < 1) {
                throw new RuntimeException('El episodio seleccionado no es válido.');
            }

            $consulta = $conexion->prepare(
                "UPDATE podcasts
                 SET estado = CASE
                    WHEN estado = 'activo' THEN 'inactivo'
                    ELSE 'activo'
                 END
                 WHERE id = :id"
            );

            $consulta->execute([':id' => $id]);

            if ($consulta->rowCount() > 0) {
                establecerMensaje('exito', 'El estado del podcast fue actualizado.');
            } else {
                establecerMensaje('info', 'No se realizaron cambios.');
            }

            redireccionar('podcasts.php');
        }

        throw new RuntimeException('La operación solicitada no es válida.');

    } catch (RuntimeException $error) {

        $errores[] = $error->getMessage();

    } catch (PDOException $error) {

        error_log('[Radio Stereo] Error en podcasts: ' . $error->getMessage());
        $errores[] = 'Ocurrió un error al guardar el podcast. Inténtalo nuevamente.';
    }
}

/*
|--------------------------------------------------------------------------
| CARGAR EPISODIO PARA EDITAR
|--------------------------------------------------------------------------
*/

$podcastEditar = null;
$idEditar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);

if ($idEditar) {
    $consulta = $conexion->prepare(
        'SELECT * FROM podcasts WHERE id = :id LIMIT 1'
    );

    $consulta->execute([':id' => $idEditar]);
    $podcastEditar = $consulta->fetch();

    if (!$podcastEditar) {
        $errores[] = 'El podcast que buscas editar no existe.';
    }
}

/*
|--------------------------------------------------------------------------
| LISTAR PODCASTS
|--------------------------------------------------------------------------
*/

$consulta = $conexion->query(
    'SELECT * FROM podcasts ORDER BY orden ASC, id DESC'
);

$podcasts = $consulta->fetchAll();

$mensaje = obtenerMensaje();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Podcasts | Radio Stereo Academy NS</title>

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
            padding: 11px 16px;
            background: #123f38;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
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

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d7e0e5;
            border-radius: 8px;
            background: #fbfcfd;
            color: #263640;
            font: inherit;
            font-size: 13px;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #168c70;
            box-shadow: 0 0 0 3px rgba(22,140,112,.1);
        }

        .ayuda {
            color: #87949c;
            font-size: 11px;
            margin-top: 6px;
            line-height: 1.5;
        }

        .boton {
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
            min-width: 650px;
            border-collapse: collapse;
        }

        th {
            padding: 13px 10px;
            text-align: left;
            background: #f4f7f7;
            color: #53656d;
            font-size: 11px;
            text-transform: uppercase;
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
            width: 62px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            background: #e8eeee;
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

        .activo {
            background: #e0f5eb;
            color: #176342;
        }

        .inactivo {
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
            font-size: 11px;
            cursor: pointer;
            text-decoration: none;
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

        .audio {
            width: 150px;
            height: 32px;
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
            <h1>Podcasts y audios</h1>
            <p class="subtitulo">
                Administra los episodios, sus portadas y archivos de audio.
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
                <?= $podcastEditar ? 'Editar episodio' : 'Registrar episodio' ?>
            </h2>

            <form method="POST" enctype="multipart/form-data">

                <?= campoCSRF() ?>

                <input
                    type="hidden"
                    name="accion"
                    value="<?= $podcastEditar ? 'actualizar' : 'crear' ?>"
                >

                <?php if ($podcastEditar): ?>
                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $podcastEditar['id'] ?>"
                    >
                <?php endif; ?>

                <label for="titulo">Título del podcast</label>
                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    maxlength="180"
                    placeholder="Nombre del episodio"
                    value="<?= e($podcastEditar['titulo'] ?? '') ?>"
                    required
                >

                <label for="descripcion">Descripción</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    placeholder="Describe el contenido del episodio..."
                ><?= e($podcastEditar['descripcion'] ?? '') ?></textarea>

                <label for="duracion">Duración</label>
                <input
                    type="text"
                    id="duracion"
                    name="duracion"
                    maxlength="30"
                    placeholder="Ej. 15:30 minutos"
                    value="<?= e($podcastEditar['duracion'] ?? '') ?>"
                >

                <label for="fecha_publicacion">Fecha de publicación</label>
                <input
                    type="date"
                    id="fecha_publicacion"
                    name="fecha_publicacion"
                    value="<?= e($podcastEditar['fecha_publicacion'] ?? '') ?>"
                >

                <label for="portada">Portada del podcast</label>
                <input
                    type="file"
                    id="portada"
                    name="portada"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >

                <p class="ayuda">
                    JPG, PNG o WEBP. Máximo 5 MB.
                </p>

                <?php if (!empty($podcastEditar['portada'])): ?>
                    <p class="ayuda">Portada actual:</p>
                    <img
                        src="../<?= e($podcastEditar['portada']) ?>"
                        alt="Portada actual"
                        style="width:100%;max-height:160px;object-fit:cover;border-radius:8px;margin-top:10px;"
                    >
                <?php endif; ?>

                <label for="audio">Archivo de audio</label>
                <input
                    type="file"
                    id="audio"
                    name="audio"
                    accept=".mp3,.ogg,.wav,.m4a,.aac,.flac,.webm,audio/*"
                    <?= $podcastEditar ? '' : 'required' ?>
                >

                <p class="ayuda">
                    MP3, OGG, WAV, M4A, AAC o FLAC. Máximo 50 MB.
                    En una edición puedes dejar el campo vacío para conservar el audio actual.
                </p>

                <?php if (!empty($podcastEditar['archivo_audio'])): ?>
                    <p class="ayuda">Audio actual:</p>
                    <audio controls style="width:100%;margin-top:10px;"
                        src="../<?= e($podcastEditar['archivo_audio']) ?>">
                    </audio>
                <?php endif; ?>

                <button type="submit" class="boton">
                    <?= $podcastEditar ? 'Guardar cambios' : 'Registrar podcast' ?>
                </button>

                <?php if ($podcastEditar): ?>
                    <a href="podcasts.php" class="cancelar">
                        Cancelar edición
                    </a>
                <?php endif; ?>

            </form>

        </section>

        <section class="panel">

            <h2>Podcasts registrados (<?= count($podcasts) ?>)</h2>

            <?php if (empty($podcasts)): ?>

                <div class="vacio">
                    Todavía no hay podcasts registrados.
                    Utiliza el formulario para agregar el primero.
                </div>

            <?php else: ?>

                <div class="tabla-contenedor">

                    <table>
                        <thead>
                            <tr>
                                <th>Portada</th>
                                <th>Episodio</th>
                                <th>Audio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($podcasts as $podcast): ?>

                                <tr>

                                    <td>
                                        <?php if (!empty($podcast['portada'])): ?>
                                            <img
                                                class="miniatura"
                                                src="../<?= e($podcast['portada']) ?>"
                                                alt="<?= e($podcast['titulo']) ?>"
                                            >
                                        <?php else: ?>
                                            <div class="sin-imagen">Sin portada</div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <strong><?= e($podcast['titulo']) ?></strong>
                                        <br>
                                        <span style="color:#839099">
                                            <?= e($podcast['duracion'] ?? '') ?>
                                        </span>
                                        <br>
                                        <span style="color:#839099">
                                            <?= e(fechaFormateada($podcast['fecha_publicacion'] ?? null)) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (!empty($podcast['archivo_audio'])): ?>
                                            <audio
                                                class="audio"
                                                controls
                                                preload="none"
                                                src="../<?= e($podcast['archivo_audio']) ?>"
                                            ></audio>
                                        <?php else: ?>
                                            Sin audio
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="estado <?= e($podcast['estado']) ?>">
                                            <?= e(ucfirst($podcast['estado'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="acciones">

                                            <a
                                                class="accion editar"
                                                href="podcasts.php?editar=<?= (int) $podcast['id'] ?>"
                                            >
                                                Editar
                                            </a>

                                            <form
                                                method="POST"
                                                class="form-estado"
                                                onsubmit="return confirm('¿Deseas cambiar el estado de este podcast?');"
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
                                                    value="<?= (int) $podcast['id'] ?>"
                                                >

                                                <button type="submit" class="accion cambiar">
                                                    <?= $podcast['estado'] === 'activo'
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
