<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/funciones.php';

exigirAdministrador();

$mensaje = '';
$tipoMensaje = 'exito';

$estadosPermitidos = ['todos', 'nuevo', 'leido', 'archivado'];

$filtro = $_GET['estado'] ?? 'todos';

if (!in_array($filtro, $estadosPermitidos, true)) {
    $filtro = 'todos';
}

$mensajeSeleccionado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        exigirTokenCSRF();

        $accion = $_POST['accion'] ?? '';
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new RuntimeException('El mensaje seleccionado no es válido.');
        }

        if ($accion === 'estado') {
            $nuevoEstado = $_POST['nuevo_estado'] ?? '';

            if (!in_array($nuevoEstado, ['nuevo', 'leido', 'archivado'], true)) {
                throw new RuntimeException('El estado seleccionado no es válido.');
            }

            $consulta = $conexion->prepare(
                "UPDATE mensajes_contacto
                 SET estado = :estado
                 WHERE id = :id"
            );

            $consulta->execute([
                ':estado' => $nuevoEstado,
                ':id' => $id
            ]);

            if ($consulta->rowCount() === 0) {
                $verificar = $conexion->prepare(
                    "SELECT id FROM mensajes_contacto WHERE id = :id"
                );
                $verificar->execute([':id' => $id]);

                if (!$verificar->fetch()) {
                    throw new RuntimeException('El mensaje ya no existe.');
                }
            }

            $_SESSION['mensaje_contactos'] = 'El estado del mensaje se actualizó correctamente.';

            header('Location: mensajes.php?estado=' . urlencode($filtro));
            exit;
        }

        throw new RuntimeException('La acción solicitada no es válida.');

    } catch (Throwable $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'error';
    }
}

if (isset($_SESSION['mensaje_contactos'])) {
    $mensaje = $_SESSION['mensaje_contactos'];
    unset($_SESSION['mensaje_contactos']);
}

$estadisticas = $conexion->query(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN estado = 'nuevo' THEN 1 ELSE 0 END) AS nuevos,
        SUM(CASE WHEN estado = 'leido' THEN 1 ELSE 0 END) AS leidos,
        SUM(CASE WHEN estado = 'archivado' THEN 1 ELSE 0 END) AS archivados
     FROM mensajes_contacto"
)->fetch();

if (isset($_GET['ver'])) {
    $idVer = (int)$_GET['ver'];

    if ($idVer > 0) {
        $consulta = $conexion->prepare(
            "SELECT * FROM mensajes_contacto WHERE id = :id"
        );

        $consulta->execute([':id' => $idVer]);
        $mensajeSeleccionado = $consulta->fetch();

        if (!$mensajeSeleccionado) {
            $mensaje = 'El mensaje solicitado no existe.';
            $tipoMensaje = 'error';
        }
    }
}

$sql = "SELECT id, nombre, correo, mensaje, estado, creado_en
        FROM mensajes_contacto";

if ($filtro !== 'todos') {
    $sql .= " WHERE estado = :estado";
}

$sql .= " ORDER BY
            CASE estado
                WHEN 'nuevo' THEN 1
                WHEN 'leido' THEN 2
                WHEN 'archivado' THEN 3
                ELSE 4
            END,
            creado_en DESC,
            id DESC";

$consulta = $conexion->prepare($sql);

if ($filtro !== 'todos') {
    $consulta->execute([':estado' => $filtro]);
} else {
    $consulta->execute();
}

$mensajes = $consulta->fetchAll();

function etiquetaEstadoMensaje(string $estado): string
{
    return match ($estado) {
        'nuevo' => 'Nuevo',
        'leido' => 'Leído',
        'archivado' => 'Archivado',
        default => 'Desconocido'
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mensajes de contacto | Radio Stereo Academy NS</title>

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
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 22px;
        }

        .estadistica {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000008;
        }

        .estadistica span {
            color: #68757c;
            font-size: 14px;
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
        }

        .mensaje-alerta {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #e5f5ec;
            color: #17643b;
        }

        .mensaje-alerta.error {
            background: #fde9e9;
            color: #a32222;
        }

        .filtros {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .filtro {
            display: inline-block;
            padding: 9px 14px;
            border-radius: 7px;
            background: #eef1f1;
            color: #46534f;
            text-decoration: none;
            font-size: 14px;
        }

        .filtro.activo {
            background: #123f38;
            color: white;
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
            padding: 14px;
            border-bottom: 1px solid #edf0f0;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f5f7f7;
            color: #123f38;
        }

        tr.nuevo {
            background: #f4faf7;
        }

        .nombre {
            font-weight: bold;
            color: #263238;
        }

        .correo {
            color: #64737a;
            font-size: 13px;
            margin-top: 5px;
        }

        .resumen-mensaje {
            max-width: 300px;
            color: #68757c;
            line-height: 1.5;
        }

        .estado {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .estado.nuevo {
            background: #e1f4e8;
            color: #17643b;
        }

        .estado.leido {
            background: #e5efff;
            color: #2059a0;
        }

        .estado.archivado {
            background: #eeeeee;
            color: #555;
        }

        .acciones {
            display: flex;
            align-items: center;
            gap: 9px;
            flex-wrap: wrap;
        }

        .ver {
            color: #1458a0;
            font-weight: bold;
            text-decoration: none;
        }

        .boton {
            border: 0;
            padding: 8px 11px;
            border-radius: 6px;
            background: #e9eeee;
            color: #263238;
            cursor: pointer;
            font-size: 13px;
        }

        .detalle {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .detalle-dato {
            padding-bottom: 14px;
            border-bottom: 1px solid #edf0f0;
        }

        .detalle-dato strong {
            display: block;
            color: #123f38;
            margin-bottom: 7px;
        }

        .contenido-mensaje {
            background: #f5f7f7;
            padding: 18px;
            border-radius: 9px;
            line-height: 1.7;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .botones-detalle {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .boton-principal {
            background: #123f38;
            color: white;
        }

        .sin-mensajes {
            text-align: center;
            padding: 35px 15px;
            color: #758087;
        }

        @media (max-width: 800px) {
            .estadisticas {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 550px) {
            body {
                padding: 14px;
            }

            .estadisticas {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .estadistica {
                padding: 14px;
            }

            .estadistica strong {
                font-size: 23px;
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
            <h1>Mensajes de contacto</h1>
            <p class="subtitulo">
                Consulta y administra los mensajes enviados por los visitantes.
            </p>
        </div>

        <a href="index.php" class="volver">← Volver al panel</a>
    </header>

    <?php if ($mensaje !== ''): ?>
        <div class="mensaje-alerta <?= $tipoMensaje === 'error' ? 'error' : '' ?>">
            <?= e($mensaje) ?>
        </div>
    <?php endif; ?>

    <section class="estadisticas">

        <div class="estadistica">
            <span>Total de mensajes</span>
            <strong><?= (int)($estadisticas['total'] ?? 0) ?></strong>
        </div>

        <div class="estadistica">
            <span>Mensajes nuevos</span>
            <strong><?= (int)($estadisticas['nuevos'] ?? 0) ?></strong>
        </div>

        <div class="estadistica">
            <span>Mensajes leídos</span>
            <strong><?= (int)($estadisticas['leidos'] ?? 0) ?></strong>
        </div>

        <div class="estadistica">
            <span>Archivados</span>
            <strong><?= (int)($estadisticas['archivados'] ?? 0) ?></strong>
        </div>

    </section>

    <?php if ($mensajeSeleccionado): ?>

        <section class="panel">
            <h2>Detalle del mensaje</h2>

            <div class="detalle">

                <div class="detalle-dato">
                    <strong>Nombre del remitente</strong>
                    <?= e($mensajeSeleccionado['nombre']) ?>
                </div>

                <div class="detalle-dato">
                    <strong>Correo electrónico</strong>
                    <a href="mailto:<?= e($mensajeSeleccionado['correo']) ?>">
                        <?= e($mensajeSeleccionado['correo']) ?>
                    </a>
                </div>

                <div class="detalle-dato">
                    <strong>Fecha de recepción</strong>
                    <?= e(date('d/m/Y H:i', strtotime($mensajeSeleccionado['creado_en']))) ?>
                </div>

                <div class="detalle-dato">
                    <strong>Estado</strong>
                    <span class="estado <?= e($mensajeSeleccionado['estado']) ?>">
                        <?= e(etiquetaEstadoMensaje($mensajeSeleccionado['estado'])) ?>
                    </span>
                </div>

                <div>
                    <strong>Contenido del mensaje</strong>

                    <div class="contenido-mensaje"><?= e($mensajeSeleccionado['mensaje']) ?></div>
                </div>

                <div class="botones-detalle">

                    <a
                        href="mailto:<?= e($mensajeSeleccionado['correo']) ?>"
                        class="filtro activo">
                        Responder por correo
                    </a>

                    <?php if ($mensajeSeleccionado['estado'] !== 'leido'): ?>
                        <form method="POST">
                            <?= campoCSRF() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id" value="<?= (int)$mensajeSeleccionado['id'] ?>">
                            <input type="hidden" name="nuevo_estado" value="leido">
                            <button type="submit" class="boton boton-principal">
                                Marcar como leído
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($mensajeSeleccionado['estado'] !== 'archivado'): ?>
                        <form method="POST">
                            <?= campoCSRF() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id" value="<?= (int)$mensajeSeleccionado['id'] ?>">
                            <input type="hidden" name="nuevo_estado" value="archivado">
                            <button type="submit" class="boton">
                                Archivar mensaje
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($mensajeSeleccionado['estado'] !== 'nuevo'): ?>
                        <form method="POST">
                            <?= campoCSRF() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id" value="<?= (int)$mensajeSeleccionado['id'] ?>">
                            <input type="hidden" name="nuevo_estado" value="nuevo">
                            <button type="submit" class="boton">
                                Marcar como nuevo
                            </button>
                        </form>
                    <?php endif; ?>

                    <a href="mensajes.php?estado=<?= e($filtro) ?>" class="filtro">
                        Volver a los mensajes
                    </a>

                </div>
            </div>
        </section>

    <?php endif; ?>

    <section class="panel">
        <h2>Bandeja de mensajes</h2>

        <nav class="filtros">
            <a
                href="mensajes.php?estado=todos"
                class="filtro <?= $filtro === 'todos' ? 'activo' : '' ?>">
                Todos
            </a>

            <a
                href="mensajes.php?estado=nuevo"
                class="filtro <?= $filtro === 'nuevo' ? 'activo' : '' ?>">
                Nuevos
            </a>

            <a
                href="mensajes.php?estado=leido"
                class="filtro <?= $filtro === 'leido' ? 'activo' : '' ?>">
                Leídos
            </a>

            <a
                href="mensajes.php?estado=archivado"
                class="filtro <?= $filtro === 'archivado' ? 'activo' : '' ?>">
                Archivados
            </a>
        </nav>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Remitente</th>
                        <th>Mensaje</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!$mensajes): ?>

                    <tr>
                        <td colspan="5">
                            <div class="sin-mensajes">
                                No hay mensajes en esta categoría.
                            </div>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($mensajes as $item): ?>

                        <tr class="<?= $item['estado'] === 'nuevo' ? 'nuevo' : '' ?>">

                            <td>
                                <div class="nombre"><?= e($item['nombre']) ?></div>
                                <div class="correo"><?= e($item['correo']) ?></div>
                            </td>

                            <td>
                                <div class="resumen-mensaje">
                                    <?= e(mb_strimwidth($item['mensaje'], 0, 100, '...', 'UTF-8')) ?>
                                </div>
                            </td>

                            <td>
                                <?= e(date('d/m/Y H:i', strtotime($item['creado_en']))) ?>
                            </td>

                            <td>
                                <span class="estado <?= e($item['estado']) ?>">
                                    <?= e(etiquetaEstadoMensaje($item['estado'])) ?>
                                </span>
                            </td>

                            <td>
                                <div class="acciones">

                                    <a
                                        class="ver"
                                        href="mensajes.php?estado=<?= e($filtro) ?>&ver=<?= (int)$item['id'] ?>">
                                        Leer
                                    </a>

                                    <?php if ($item['estado'] === 'nuevo'): ?>

                                        <form method="POST">
                                            <?= campoCSRF() ?>
                                            <input type="hidden" name="accion" value="estado">
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                            <input type="hidden" name="nuevo_estado" value="leido">

                                            <button type="submit" class="boton">
                                                Marcar leído
                                            </button>
                                        </form>

                                    <?php endif; ?>

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
