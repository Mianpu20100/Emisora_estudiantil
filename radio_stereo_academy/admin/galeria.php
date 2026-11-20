<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/funciones.php';

exigirAdministrador();

$directorioImagenes = __DIR__ . '/../uploads/galeria/';

if (!is_dir($directorioImagenes)) {
    mkdir($directorioImagenes, 0755, true);
}

$mensaje = '';
$tipoMensaje = 'exito';
$imagenEditar = null;

function subirImagenGaleria(array $archivo, string $directorio): ?string
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

    $formatosPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($formatosPermitidos[$mime])) {
        throw new RuntimeException('Formato no permitido. Utiliza JPG, PNG o WEBP.');
    }

    $nombre = bin2hex(random_bytes(16)) . '.' . $formatosPermitidos[$mime];
    $destino = $directorio . $nombre;

    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        throw new RuntimeException('No fue posible guardar la imagen.');
    }

    return $nombre;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        exigirTokenCSRF();

        $accion = $_POST['accion'] ?? '';

        if ($accion === 'guardar') {
            $id = (int)($_POST['id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            $orden = filter_var(
                $_POST['orden'] ?? 0,
                FILTER_VALIDATE_INT
            );

            if ($titulo === '') {
                throw new RuntimeException('Escribe el título de la fotografía.');
            }

            if ($orden === false || $orden < 0) {
                throw new RuntimeException('El orden debe ser un número igual o mayor que cero.');
            }

            $actual = null;

            if ($id > 0) {
                $consultaActual = $conexion->prepare(
                    "SELECT id, imagen FROM galeria WHERE id = :id"
                );
                $consultaActual->execute([':id' => $id]);
                $actual = $consultaActual->fetch();

                if (!$actual) {
                    throw new RuntimeException('La fotografía que intentas editar no existe.');
                }
            }

            $imagenNueva = subirImagenGaleria(
                $_FILES['imagen'] ?? [],
                $directorioImagenes
            );

            $imagen = $imagenNueva ?? ($actual['imagen'] ?? null);

            if (!$imagen) {
                throw new RuntimeException('Debes seleccionar una imagen para esta fotografía.');
            }

            if ($id > 0) {
                $sql = "UPDATE galeria SET
                            titulo = :titulo,
                            descripcion = :descripcion,
                            imagen = :imagen,
                            categoria = :categoria,
                            orden = :orden
                        WHERE id = :id";

                $consulta = $conexion->prepare($sql);
                $consulta->execute([
                    ':titulo' => $titulo,
                    ':descripcion' => $descripcion,
                    ':imagen' => $imagen,
                    ':categoria' => $categoria,
                    ':orden' => $orden,
                    ':id' => $id
                ]);

                if ($imagenNueva && !empty($actual['imagen'])) {
                    $rutaAnterior = $directorioImagenes . basename($actual['imagen']);

                    if (is_file($rutaAnterior)) {
                        unlink($rutaAnterior);
                    }
                }

                $_SESSION['mensaje_galeria'] = 'Fotografía actualizada correctamente.';
            } else {
                $sql = "INSERT INTO galeria
                        (titulo, descripcion, imagen, categoria, orden, estado)
                        VALUES
                        (:titulo, :descripcion, :imagen, :categoria, :orden, 'activo')";

                $consulta = $conexion->prepare($sql);
                $consulta->execute([
                    ':titulo' => $titulo,
                    ':descripcion' => $descripcion,
                    ':imagen' => $imagen,
                    ':categoria' => $categoria,
                    ':orden' => $orden
                ]);

                $_SESSION['mensaje_galeria'] = 'Fotografía agregada correctamente.';
            }

            header('Location: galeria.php');
            exit;
        }

        if ($accion === 'estado') {
            $id = (int)($_POST['id'] ?? 0);
            $nuevoEstado = $_POST['nuevo_estado'] ?? '';

            if (
                $id <= 0 ||
                !in_array($nuevoEstado, ['activo', 'inactivo'], true)
            ) {
                throw new RuntimeException('No se pudo cambiar el estado de la fotografía.');
            }

            $consulta = $conexion->prepare(
                "UPDATE galeria SET estado = :estado WHERE id = :id"
            );

            $consulta->execute([
                ':estado' => $nuevoEstado,
                ':id' => $id
            ]);

            $_SESSION['mensaje_galeria'] = 'Estado de la fotografía actualizado.';

            header('Location: galeria.php');
            exit;
        }

        throw new RuntimeException('Acción no reconocida.');

    } catch (Throwable $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'error';
    }
}

if (isset($_SESSION['mensaje_galeria'])) {
    $mensaje = $_SESSION['mensaje_galeria'];
    unset($_SESSION['mensaje_galeria']);
}

if (isset($_GET['editar'])) {
    $idEditar = (int)$_GET['editar'];

    $consulta = $conexion->prepare(
        "SELECT * FROM galeria WHERE id = :id"
    );
    $consulta->execute([':id' => $idEditar]);
    $imagenEditar = $consulta->fetch();

    if (!$imagenEditar) {
        $mensaje = 'La fotografía solicitada no existe.';
        $tipoMensaje = 'error';
    }
}

$consulta = $conexion->query(
    "SELECT * FROM galeria ORDER BY orden ASC, id DESC"
);
$imagenes = $consulta->fetchAll();

$totalImagenes = count($imagenes);
$totalActivas = 0;
$totalInactivas = 0;

foreach ($imagenes as $imagen) {
    if ($imagen['estado'] === 'activo') {
        $totalActivas++;
    } else {
        $totalInactivas++;
    }
}

$datosFormulario = $imagenEditar ?: [
    'id' => '',
    'titulo' => '',
    'descripcion' => '',
    'categoria' => '',
    'orden' => 0,
    'imagen' => '',
    'estado' => 'activo'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar galería | Radio Stereo Academy NS</title>

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
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        h1, h2 {
            color: #123f38;
        }

        h1 {
            margin: 0 0 7px;
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

        .estadistica, .panel {
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
            padding: 24px;
            margin-bottom: 25px;
        }

        .panel h2 {
            margin-top: 0;
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

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d9e0e2;
            border-radius: 7px;
            font: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
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
            min-width: 800px;
            border-collapse: collapse;
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
            width: 90px;
            height: 65px;
            object-fit: cover;
            border-radius: 7px;
        }

        .estado {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .activo {
            background: #e1f4e8;
            color: #17643b;
        }

        .inactivo {
            background: #fde9e9;
            color: #a32222;
        }

        .acciones {
            display: flex;
            align-items: center;
            gap: 9px;
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

            .estadisticas, .formulario {
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
            <h1>Administrar galería</h1>
            <p class="subtitulo">Organiza las fotografías de la emisora.</p>
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
            Total de fotografías
            <strong><?= $totalImagenes ?></strong>
        </div>

        <div class="estadistica">
            Activas
            <strong><?= $totalActivas ?></strong>
        </div>

        <div class="estadistica">
            Inactivas
            <strong><?= $totalInactivas ?></strong>
        </div>
    </section>

    <section class="panel">
        <h2><?= $imagenEditar ? 'Editar fotografía' : 'Agregar fotografía' ?></h2>

        <form method="POST" enctype="multipart/form-data">
            <?= campoCSRF() ?>

            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" value="<?= e((string)$datosFormulario['id']) ?>">

            <div class="formulario">

                <div class="campo completo">
                    <label for="titulo">Título</label>
                    <input
                        type="text"
                        id="titulo"
                        name="titulo"
                        maxlength="255"
                        required
                        value="<?= e((string)$datosFormulario['titulo']) ?>"
                        placeholder="Ej. Actividad cultural de la institución">
                </div>

                <div class="campo">
                    <label for="categoria">Categoría</label>
                    <input
                        type="text"
                        id="categoria"
                        name="categoria"
                        maxlength="100"
                        value="<?= e((string)$datosFormulario['categoria']) ?>"
                        placeholder="Ej. Eventos, actividades, estudiantes">
                </div>

                <div class="campo">
                    <label for="orden">Orden de aparición</label>
                    <input
                        type="number"
                        id="orden"
                        name="orden"
                        min="0"
                        required
                        value="<?= e((string)$datosFormulario['orden']) ?>">
                    <span class="ayuda">Los números menores aparecen primero.</span>
                </div>

                <div class="campo completo">
                    <label for="descripcion">Descripción</label>
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        placeholder="Describe brevemente la fotografía"><?= e((string)($datosFormulario['descripcion'] ?? '')) ?></textarea>
                </div>

                <div class="campo completo">
                    <label for="imagen">Seleccionar fotografía</label>
                    <input
                        type="file"
                        id="imagen"
                        name="imagen"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        <?= $imagenEditar ? '' : 'required' ?>>

                    <span class="ayuda">JPG, PNG o WEBP. Máximo 5 MB.</span>

                    <?php if (!empty($datosFormulario['imagen'])): ?>
                        <p>Imagen actual:</p>
                        <img
                            src="../uploads/galeria/<?= e(basename((string)$datosFormulario['imagen'])) ?>"
                            class="miniatura"
                            alt="Imagen actual de la galería">
                    <?php endif; ?>
                </div>

                <div class="campo completo">
                    <div class="acciones">
                        <button type="submit" class="boton">
                            <?= $imagenEditar ? 'Guardar cambios' : 'Agregar fotografía' ?>
                        </button>

                        <?php if ($imagenEditar): ?>
                            <a href="galeria.php" class="boton-secundario">Cancelar edición</a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Fotografías registradas</h2>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Fotografía</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Orden</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (!$imagenes): ?>
                    <tr>
                        <td colspan="6">Todavía no hay fotografías registradas.</td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($imagenes as $imagen): ?>
                        <tr>
                            <td>
                                <img
                                    src="../uploads/galeria/<?= e(basename((string)$imagen['imagen'])) ?>"
                                    class="miniatura"
                                    alt="<?= e($imagen['titulo']) ?>">
                            </td>

                            <td><?= e($imagen['titulo']) ?></td>
                            <td><?= e($imagen['categoria'] ?: 'General') ?></td>
                            <td><?= (int)$imagen['orden'] ?></td>

                            <td>
                                <span class="estado <?= e($imagen['estado']) ?>">
                                    <?= e(ucfirst($imagen['estado'])) ?>
                                </span>
                            </td>

                            <td>
                                <div class="acciones">
                                    <a
                                        class="editar"
                                        href="galeria.php?editar=<?= (int)$imagen['id'] ?>">
                                        Editar
                                    </a>

                                    <form method="POST">
                                        <?= campoCSRF() ?>

                                        <input type="hidden" name="accion" value="estado">
                                        <input type="hidden" name="id" value="<?= (int)$imagen['id'] ?>">

                                        <?php if ($imagen['estado'] === 'activo'): ?>
                                            <input type="hidden" name="nuevo_estado" value="inactivo">
                                            <button type="submit" class="boton-estado">
                                                Desactivar
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="nuevo_estado" value="activo">
                                            <button type="submit" class="boton-estado">
                                                Activar
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
