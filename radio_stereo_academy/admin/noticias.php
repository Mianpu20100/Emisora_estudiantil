<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/funciones.php';

exigirAdministrador();

$directorioImagenes = __DIR__ . '/../uploads/noticias/';

if (!is_dir($directorioImagenes)) {
    mkdir($directorioImagenes, 0755, true);
}

$mensaje = '';
$tipoMensaje = 'exito';
$noticiaEditar = null;

function limpiarTextoNoticia(string $texto): string
{
    return trim($texto);
}

function generarSlugUnico(PDO $conexion, string $titulo, int $idExcluir = 0): string
{
    $base = generarSlug($titulo);

    if ($base === '') {
        $base = 'noticia-' . date('YmdHis');
    }

    $slug = $base;
    $contador = 2;

    while (true) {
        $sql = "SELECT id FROM noticias WHERE slug = :slug";
        $parametros = [':slug' => $slug];

        if ($idExcluir > 0) {
            $sql .= " AND id != :id";
            $parametros[':id'] = $idExcluir;
        }

        $consulta = $conexion->prepare($sql);
        $consulta->execute($parametros);

        if (!$consulta->fetch()) {
            return $slug;
        }

        $slug = $base . '-' . $contador;
        $contador++;
    }
}

function subirImagenNoticia(array $archivo, string $directorio): ?string
{
    if (
        !isset($archivo['error']) ||
        $archivo['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen.');
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

    $nombre = bin2hex(random_bytes(16)) . '.' . $tiposPermitidos[$mime];
    $destino = $directorio . $nombre;

    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        throw new RuntimeException('No fue posible guardar la imagen.');
    }

    return $nombre;
}

function eliminarImagenNoticia(?string $nombre, string $directorio): void
{
    if (!$nombre) {
        return;
    }

    $ruta = $directorio . basename($nombre);

    if (is_file($ruta)) {
        unlink($ruta);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        exigirTokenCSRF();

        $accion = $_POST['accion'] ?? '';

        if ($accion === 'guardar') {
            $id = (int)($_POST['id'] ?? 0);
            $titulo = limpiarTextoNoticia($_POST['titulo'] ?? '');
            $categoria = limpiarTextoNoticia($_POST['categoria'] ?? '');
            $resumen = limpiarTextoNoticia($_POST['resumen'] ?? '');
            $contenido = limpiarTextoNoticia($_POST['contenido'] ?? '');
            $estado = $_POST['estado'] ?? 'borrador';

            if ($titulo === '' || $resumen === '' || $contenido === '') {
                throw new RuntimeException('Completa el título, el resumen y el contenido.');
            }

            if (!in_array($estado, ['borrador', 'publicada'], true)) {
                throw new RuntimeException('El estado seleccionado no es válido.');
            }

            $consultaActual = null;

            if ($id > 0) {
                $consultaActual = $conexion->prepare(
                    "SELECT id, imagen, fecha_publicacion FROM noticias WHERE id = :id"
                );
                $consultaActual->execute([':id' => $id]);
                $actual = $consultaActual->fetch();

                if (!$actual) {
                    throw new RuntimeException('La noticia que intentas editar no existe.');
                }
            }

            $imagenNueva = subirImagenNoticia(
                $_FILES['imagen'] ?? [],
                $directorioImagenes
            );

            $imagen = $imagenNueva ?? ($actual['imagen'] ?? null);
            $slug = generarSlugUnico($conexion, $titulo, $id);

            $fechaPublicacion = null;

            if ($estado === 'publicada') {
                $fechaPublicacion = $actual['fecha_publicacion'] ?? date('Y-m-d H:i:s');

                if (!$fechaPublicacion) {
                    $fechaPublicacion = date('Y-m-d H:i:s');
                }
            }

            if ($id > 0) {
                $sql = "UPDATE noticias SET
                            titulo = :titulo,
                            slug = :slug,
                            categoria = :categoria,
                            resumen = :resumen,
                            contenido = :contenido,
                            imagen = :imagen,
                            estado = :estado,
                            fecha_publicacion = :fecha_publicacion
                        WHERE id = :id";

                $consulta = $conexion->prepare($sql);

                $consulta->execute([
                    ':titulo' => $titulo,
                    ':slug' => $slug,
                    ':categoria' => $categoria,
                    ':resumen' => $resumen,
                    ':contenido' => $contenido,
                    ':imagen' => $imagen,
                    ':estado' => $estado,
                    ':fecha_publicacion' => $fechaPublicacion,
                    ':id' => $id
                ]);

                if ($imagenNueva && !empty($actual['imagen'])) {
                    eliminarImagenNoticia($actual['imagen'], $directorioImagenes);
                }

                $mensaje = 'Noticia actualizada correctamente.';
            } else {
                $sql = "INSERT INTO noticias
                        (titulo, slug, categoria, resumen, contenido, imagen, estado, fecha_publicacion)
                        VALUES
                        (:titulo, :slug, :categoria, :resumen, :contenido, :imagen, :estado, :fecha_publicacion)";

                $consulta = $conexion->prepare($sql);

                $consulta->execute([
                    ':titulo' => $titulo,
                    ':slug' => $slug,
                    ':categoria' => $categoria,
                    ':resumen' => $resumen,
                    ':contenido' => $contenido,
                    ':imagen' => $imagen,
                    ':estado' => $estado,
                    ':fecha_publicacion' => $fechaPublicacion
                ]);

                $mensaje = 'Noticia creada correctamente.';
            }

            $_SESSION['mensaje_noticias'] = $mensaje;

            header('Location: noticias.php');
            exit;
        }

        if ($accion === 'estado') {
            $id = (int)($_POST['id'] ?? 0);
            $nuevoEstado = $_POST['nuevo_estado'] ?? '';

            if ($id <= 0 || !in_array($nuevoEstado, ['borrador', 'publicada'], true)) {
                throw new RuntimeException('No se pudo cambiar el estado.');
            }

            $consulta = $conexion->prepare(
                "UPDATE noticias
                 SET estado = :estado,
                     fecha_publicacion = CASE
                         WHEN :estado_fecha = 'publicada'
                         THEN COALESCE(fecha_publicacion, NOW())
                         ELSE NULL
                     END
                 WHERE id = :id"
            );

            $consulta->execute([
                ':estado' => $nuevoEstado,
                ':estado_fecha' => $nuevoEstado,
                ':id' => $id
            ]);

            $_SESSION['mensaje_noticias'] = 'Estado de la noticia actualizado.';

            header('Location: noticias.php');
            exit;
        }

        throw new RuntimeException('Acción no reconocida.');

    } catch (Throwable $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'error';
    }
}

if (isset($_SESSION['mensaje_noticias'])) {
    $mensaje = $_SESSION['mensaje_noticias'];
    unset($_SESSION['mensaje_noticias']);
}

if (isset($_GET['editar'])) {
    $idEditar = (int)$_GET['editar'];

    $consulta = $conexion->prepare(
        "SELECT * FROM noticias WHERE id = :id"
    );
    $consulta->execute([':id' => $idEditar]);
    $noticiaEditar = $consulta->fetch();

    if (!$noticiaEditar) {
        $mensaje = 'La noticia solicitada no existe.';
        $tipoMensaje = 'error';
    }
}

$consulta = $conexion->query(
    "SELECT * FROM noticias ORDER BY creado_en DESC, id DESC"
);

$noticias = $consulta->fetchAll();

$totalNoticias = count($noticias);
$totalPublicadas = 0;
$totalBorradores = 0;

foreach ($noticias as $noticia) {
    if ($noticia['estado'] === 'publicada') {
        $totalPublicadas++;
    } else {
        $totalBorradores++;
    }
}

$datosFormulario = $noticiaEditar ?: [
    'id' => '',
    'titulo' => '',
    'categoria' => '',
    'resumen' => '',
    'contenido' => '',
    'imagen' => '',
    'estado' => 'borrador'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar noticias | Radio Stereo Academy NS</title>

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
            max-width: 1250px;
            margin: auto;
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
            margin: 0 0 7px;
            color: #123f38;
        }

        .subtitulo {
            color: #68757c;
            margin: 0;
        }

        .volver {
            display: inline-block;
            padding: 11px 17px;
            border-radius: 8px;
            background: #123f38;
            color: white;
            text-decoration: none;
        }

        .estadisticas {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 22px;
        }

        .estadistica {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000008;
        }

        .estadistica strong {
            display: block;
            color: #123f38;
            font-size: 27px;
            margin-top: 8px;
        }

        .panel {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px #00000008;
        }

        .panel h2 {
            margin-top: 0;
            color: #123f38;
        }

        .formulario {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 17px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .campo.completo {
            grid-column: 1 / -1;
        }

        label {
            font-weight: bold;
            font-size: 14px;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d9e0e2;
            border-radius: 7px;
            font: inherit;
            background: white;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .contenido-editor {
            min-height: 220px;
        }

        .boton {
            border: 0;
            padding: 12px 18px;
            border-radius: 7px;
            background: #123f38;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .boton-secundario {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 7px;
            background: #e9eeee;
            color: #263238;
            text-decoration: none;
            font-weight: bold;
        }

        .mensaje {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #e5f5ec;
            color: #17643b;
        }

        .mensaje.error {
            background: #fde9e9;
            color: #a32222;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th, td {
            padding: 13px;
            border-bottom: 1px solid #edf0f0;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f5f7f7;
            color: #123f38;
        }

        .miniatura {
            width: 75px;
            height: 55px;
            object-fit: cover;
            border-radius: 6px;
        }

        .estado {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .publicada {
            background: #e1f4e8;
            color: #17643b;
        }

        .borrador {
            background: #fff0d6;
            color: #855b12;
        }

        .acciones {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .editar {
            color: #1458a0;
            text-decoration: none;
            font-weight: bold;
        }

        .boton-estado {
            border: 0;
            padding: 8px 10px;
            border-radius: 6px;
            cursor: pointer;
            background: #e9eeee;
            color: #263238;
        }

        .ayuda {
            color: #758087;
            font-size: 12px;
        }

        @media (max-width: 700px) {
            body {
                padding: 14px;
            }

            .estadisticas {
                grid-template-columns: 1fr;
            }

            .formulario {
                grid-template-columns: 1fr;
            }

            .campo.completo {
                grid-column: auto;
            }

            .panel {
                padding: 17px;
            }
        }
    </style>
</head>

<body>
<div class="contenedor">

    <header class="encabezado">
        <div>
            <h1>Administrar noticias</h1>
            <p class="subtitulo">Crea, edita y publica las noticias de la emisora.</p>
        </div>

        <a href="index.php" class="volver">← Volver al panel</a>
    </header>

    <?php if ($mensaje !== ''): ?>
        <div class="mensaje <?= $tipoMensaje === 'error' ? 'error' : '' ?>">
            <?= e($mensaje) ?>
        </div>
    <?php endif; ?>

    <section class="estadisticas">
        <div class="estadistica">
            Total de noticias
            <strong><?= $totalNoticias ?></strong>
        </div>

        <div class="estadistica">
            Publicadas
            <strong><?= $totalPublicadas ?></strong>
        </div>

        <div class="estadistica">
            Borradores
            <strong><?= $totalBorradores ?></strong>
        </div>
    </section>

    <section class="panel">
        <h2><?= $noticiaEditar ? 'Editar noticia' : 'Crear noticia' ?></h2>

        <form method="POST" enctype="multipart/form-data">
            <?= campoCSRF() ?>

            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" value="<?= e((string)$datosFormulario['id']) ?>">

            <div class="formulario">

                <div class="campo completo">
                    <label for="titulo">Título de la noticia</label>
                    <input
                        type="text"
                        id="titulo"
                        name="titulo"
                        maxlength="255"
                        required
                        value="<?= e((string)$datosFormulario['titulo']) ?>"
                        placeholder="Escribe el título de la noticia">
                </div>

                <div class="campo">
                    <label for="categoria">Categoría</label>
                    <input
                        type="text"
                        id="categoria"
                        name="categoria"
                        maxlength="100"
                        value="<?= e((string)($datosFormulario['categoria'] ?? '')) ?>"
                        placeholder="Ej. Institucional, eventos, cultura">
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="borrador" <?= $datosFormulario['estado'] === 'borrador' ? 'selected' : '' ?>>
                            Guardar como borrador
                        </option>
                        <option value="publicada" <?= $datosFormulario['estado'] === 'publicada' ? 'selected' : '' ?>>
                            Publicar noticia
                        </option>
                    </select>
                </div>

                <div class="campo completo">
                    <label for="resumen">Resumen</label>
                    <textarea
                        id="resumen"
                        name="resumen"
                        maxlength="1000"
                        required
                        placeholder="Escribe una breve descripción de la noticia"><?= e((string)$datosFormulario['resumen']) ?></textarea>
                </div>

                <div class="campo completo">
                    <label for="contenido">Contenido de la noticia</label>
                    <textarea
                        id="contenido"
                        name="contenido"
                        class="contenido-editor"
                        required
                        placeholder="Escribe aquí el contenido completo de la noticia"><?= e((string)$datosFormulario['contenido']) ?></textarea>
                </div>

                <div class="campo completo">
                    <label for="imagen">Imagen principal</label>
                    <input
                        type="file"
                        id="imagen"
                        name="imagen"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                    <span class="ayuda">Formatos permitidos: JPG, PNG y WEBP. Máximo 5 MB.</span>

                    <?php if (!empty($datosFormulario['imagen'])): ?>
                        <p>Imagen actual:</p>
                        <img
                            src="../uploads/noticias/<?= e(basename((string)$datosFormulario['imagen'])) ?>"
                            class="miniatura"
                            alt="Imagen actual de la noticia">
                    <?php endif; ?>
                </div>

                <div class="campo completo">
                    <div class="acciones">
                        <button type="submit" class="boton">
                            <?= $noticiaEditar ? 'Guardar cambios' : 'Crear noticia' ?>
                        </button>

                        <?php if ($noticiaEditar): ?>
                            <a href="noticias.php" class="boton-secundario">Cancelar edición</a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Noticias registradas</h2>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (!$noticias): ?>
                    <tr>
                        <td colspan="6">Todavía no hay noticias registradas.</td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($noticias as $noticia): ?>
                        <tr>
                            <td>
                                <?php if (!empty($noticia['imagen'])): ?>
                                    <img
                                        src="../uploads/noticias/<?= e(basename((string)$noticia['imagen'])) ?>"
                                        class="miniatura"
                                        alt="Imagen de noticia">
                                <?php else: ?>
                                    Sin imagen
                                <?php endif; ?>
                            </td>

                            <td><?= e($noticia['titulo']) ?></td>
                            <td><?= e($noticia['categoria'] ?? 'General') ?></td>

                            <td>
                                <span class="estado <?= e($noticia['estado']) ?>">
                                    <?= e(ucfirst($noticia['estado'])) ?>
                                </span>
                            </td>

                            <td>
                                <?= $noticia['fecha_publicacion']
                                    ? e(date('d/m/Y H:i', strtotime($noticia['fecha_publicacion'])))
                                    : 'Sin publicar' ?>
                            </td>

                            <td>
                                <div class="acciones">
                                    <a
                                        class="editar"
                                        href="noticias.php?editar=<?= (int)$noticia['id'] ?>">
                                        Editar
                                    </a>

                                    <form method="POST">
                                        <?= campoCSRF() ?>

                                        <input type="hidden" name="accion" value="estado">
                                        <input type="hidden" name="id" value="<?= (int)$noticia['id'] ?>">

                                        <?php if ($noticia['estado'] === 'publicada'): ?>
                                            <input type="hidden" name="nuevo_estado" value="borrador">
                                            <button type="submit" class="boton-estado">
                                                Pasar a borrador
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="nuevo_estado" value="publicada">
                                            <button type="submit" class="boton-estado">
                                                Publicar
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>
</body>
</html>
