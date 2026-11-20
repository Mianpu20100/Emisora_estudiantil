<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/funciones.php';

iniciarSesionSegura();
exigirAdministrador();

$nombreAdministrador = $_SESSION['administrador_usuario'] ?? 'Administrador';

$nombreEmisora = obtenerConfiguracion(
    $conexion,
    'nombre_emisora',
    'Radio Stereo Academy NS'
);

$estadisticas = [
    'programas' => 0,
    'podcasts' => 0,
    'noticias' => 0,
    'galeria' => 0,
    'mensajes' => 0
];

$ultimosMensajes = [];
$errorPanel = false;

try {

    $consultas = [
        'programas' => "SELECT COUNT(*) FROM programas WHERE estado = 'activo'",
        'podcasts' => "SELECT COUNT(*) FROM podcasts WHERE estado = 'activo'",
        'noticias' => "SELECT COUNT(*) FROM noticias WHERE estado = 'publicada'",
        'galeria' => "SELECT COUNT(*) FROM galeria WHERE estado = 'activo'",
        'mensajes' => "SELECT COUNT(*) FROM mensajes_contacto WHERE estado = 'nuevo'"
    ];

    foreach ($consultas as $clave => $sql) {
        $estadisticas[$clave] = (int) $conexion->query($sql)->fetchColumn();
    }

    $consultaMensajes = $conexion->query(
        "SELECT id, nombre, correo, mensaje, estado, creado_en
         FROM mensajes_contacto
         ORDER BY creado_en DESC
         LIMIT 5"
    );

    $ultimosMensajes = $consultaMensajes->fetchAll();

} catch (PDOException $error) {

    error_log(
        '[Radio Stereo] Error al cargar el panel: '
        . $error->getMessage()
    );

    $errorPanel = true;
}

$mensajeFlash = obtenerMensaje();

$modulos = [
    [
        'titulo' => 'Programación radial',
        'descripcion' => 'Crea, modifica y organiza los programas, horarios y categorías de la emisora.',
        'icono' => '◷',
        'ruta' => 'programas.php',
        'cantidad' => $estadisticas['programas'],
        'unidad' => 'programas activos'
    ],
    [
        'titulo' => 'Podcasts y audios',
        'descripcion' => 'Administra episodios, archivos de audio, portadas y fechas de publicación.',
        'icono' => '♫',
        'ruta' => 'podcasts.php',
        'cantidad' => $estadisticas['podcasts'],
        'unidad' => 'podcasts activos'
    ],
    [
        'titulo' => 'Noticias',
        'descripcion' => 'Publica noticias, modifica contenidos y administra los borradores.',
        'icono' => '▤',
        'ruta' => 'noticias.php',
        'cantidad' => $estadisticas['noticias'],
        'unidad' => 'noticias publicadas'
    ],
    [
        'titulo' => 'Galería multimedia',
        'descripcion' => 'Sube fotografías, organiza las imágenes y controla su visibilidad.',
        'icono' => '▧',
        'ruta' => 'galeria.php',
        'cantidad' => $estadisticas['galeria'],
        'unidad' => 'imágenes activas'
    ],
    [
        'titulo' => 'Mensajes de contacto',
        'descripcion' => 'Consulta los mensajes enviados por los visitantes y administra sus estados.',
        'icono' => '✉',
        'ruta' => 'mensajes.php',
        'cantidad' => $estadisticas['mensajes'],
        'unidad' => 'mensajes nuevos'
    ],
    [
        'titulo' => 'Contenido institucional',
        'descripcion' => 'Edita la historia, misión y visión de Radio Stereo Academy NS.',
        'icono' => '▥',
        'ruta' => 'contenido.php',
        'cantidad' => null,
        'unidad' => null
    ],
    [
        'titulo' => 'Configuración general',
        'descripcion' => 'Actualiza el nombre, eslogan, correo, ubicación y transmisión de la emisora.',
        'icono' => '⚙',
        'ruta' => 'configuracion.php',
        'cantidad' => null,
        'unidad' => null
    ]
];

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel administrativo | <?= e($nombreEmisora) ?></title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f2f5f7;
            color: #24313d;
        }

        a {
            -webkit-tap-highlight-color: transparent;
        }

        .estructura {
            min-height: 100vh;
            display: flex;
        }

        /* =====================================
           BARRA LATERAL
        ===================================== */

        .sidebar {
            width: 255px;
            min-height: 100vh;
            background: #123f38;
            color: white;
            padding: 25px 17px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .marca {
            padding: 5px 10px 25px;
            border-bottom: 1px solid rgba(255,255,255,.15);
            margin-bottom: 25px;
        }

        .marca h2 {
            font-size: 19px;
            line-height: 1.4;
        }

        .marca p {
            margin-top: 5px;
            font-size: 12px;
            color: #b9d5ce;
        }

        .titulo-menu {
            margin: 10px 10px;
            color: #a9c9c1;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 12px;
            border-radius: 9px;
            color: #e4f0ec;
            text-decoration: none;
            font-size: 14px;
            transition: .2s;
        }

        .menu a:hover,
        .menu a.activo {
            background: rgba(255,255,255,.14);
            color: white;
        }

        .menu a:focus-visible,
        .acceso:focus-visible,
        .cerrar-sesion:focus-visible {
            outline: 2px solid #00e5ff;
            outline-offset: 3px;
        }

        .icono {
            width: 22px;
            text-align: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .sidebar-pie {
            margin-top: auto;
            padding-top: 25px;
            border-top: 1px solid rgba(255,255,255,.15);
        }

        .usuario {
            padding: 10px;
            font-size: 13px;
            color: #d5e6e1;
            overflow-wrap: anywhere;
        }

        .cerrar-sesion {
            display: block;
            margin-top: 10px;
            padding: 12px;
            border-radius: 8px;
            background: rgba(255,255,255,.12);
            color: white;
            text-align: center;
            text-decoration: none;
            font-size: 13px;
            transition: .2s;
        }

        .cerrar-sesion:hover {
            background: #a83245;
        }

        .ver-sitio {
            display: block;
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            color: #d5e6e1;
            text-decoration: none;
            font-size: 13px;
            text-align: center;
            border: 1px solid rgba(255,255,255,.2);
            transition: .2s;
        }

        .ver-sitio:hover {
            background: rgba(255,255,255,.12);
            color: white;
        }

        /* =====================================
           CONTENIDO PRINCIPAL
        ===================================== */

        .contenido {
            flex: 1;
            min-width: 0;
            padding: 30px;
        }

        .encabezado {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .encabezado h1 {
            font-size: 27px;
            color: #183d36;
        }

        .encabezado p {
            margin-top: 7px;
            color: #71808d;
            font-size: 14px;
        }

        .fecha {
            color: #61717e;
            font-size: 13px;
            background: white;
            padding: 12px 16px;
            border-radius: 9px;
            border: 1px solid #e4e9ed;
        }

        /* =====================================
           BIENVENIDA
        ===================================== */

        .bienvenida {
            padding: 25px;
            margin-bottom: 27px;
            border-radius: 14px;
            background: linear-gradient(120deg, #123f38, #197a65);
            color: white;
        }

        .bienvenida h2 {
            font-size: 21px;
            margin-bottom: 9px;
        }

        .bienvenida p {
            color: #d7eee7;
            font-size: 14px;
            line-height: 1.6;
        }

        .titulo-seccion {
            font-size: 18px;
            margin-bottom: 17px;
            color: #243b37;
        }

        /* =====================================
           ESTADÍSTICAS
        ===================================== */

        .estadisticas {
            display: grid;
            grid-template-columns: repeat(5, minmax(130px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .tarjeta {
            padding: 20px;
            background: white;
            border: 1px solid #e7ecef;
            border-radius: 13px;
            box-shadow: 0 3px 12px rgba(20,40,50,.035);
            transition: .2s;
        }

        .tarjeta:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 18px rgba(20,40,50,.07);
        }

        .tarjeta .simbolo {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e8f5f0;
            border-radius: 10px;
            font-size: 19px;
            margin-bottom: 14px;
        }

        .tarjeta h3 {
            font-size: 26px;
            color: #123f38;
        }

        .tarjeta p {
            margin-top: 6px;
            font-size: 12px;
            color: #71808d;
        }

        /* =====================================
           MÓDULOS DE ADMINISTRACIÓN
        ===================================== */

        .accesos {
            display: grid;
            grid-template-columns: repeat(3, minmax(170px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .acceso {
            display: block;
            padding: 21px;
            background: white;
            border: 1px solid #e7ecef;
            border-radius: 12px;
            text-decoration: none;
            color: #24313d;
            transition: .25s ease;
            position: relative;
        }

        .acceso:hover {
            transform: translateY(-4px);
            border-color: #197a65;
            box-shadow: 0 8px 22px rgba(18,63,56,.09);
        }

        .acceso .simbolo {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e8f5f0;
            color: #123f38;
            border-radius: 11px;
            font-size: 23px;
            margin-bottom: 14px;
        }

        .acceso h3 {
            font-size: 15px;
            margin-bottom: 8px;
            color: #183d36;
        }

        .acceso p {
            color: #71808d;
            font-size: 12px;
            line-height: 1.6;
            min-height: 58px;
        }

        .acceso-inferior {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid #edf0f2;
        }

        .cantidad-modulo {
            color: #168c70;
            font-size: 11px;
            font-weight: bold;
        }

        .abrir-modulo {
            color: #168c70;
            font-size: 12px;
            font-weight: bold;
        }

        .acceso:hover .abrir-modulo {
            color: #123f38;
        }

        /* =====================================
           MENSAJES RECIENTES
        ===================================== */

        .panel-mensajes {
            background: white;
            border: 1px solid #e7ecef;
            border-radius: 13px;
            overflow: hidden;
            margin-bottom: 25px;
        }

        .panel-mensajes-encabezado {
            padding: 20px;
            border-bottom: 1px solid #edf0f2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .panel-mensajes-encabezado h2 {
            font-size: 17px;
        }

        .ver-mensajes {
            color: #168c70;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
        }

        .ver-mensajes:hover {
            text-decoration: underline;
        }

        .mensaje-item {
            padding: 17px 20px;
            border-bottom: 1px solid #edf0f2;
        }

        .mensaje-item:last-child {
            border-bottom: none;
        }

        .mensaje-item strong {
            font-size: 14px;
        }

        .mensaje-item .correo {
            color: #168c70;
            font-size: 12px;
            margin-left: 8px;
            overflow-wrap: anywhere;
        }

        .mensaje-item p {
            margin-top: 8px;
            color: #657581;
            font-size: 13px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .mensaje-item small {
            display: block;
            margin-top: 8px;
            color: #9aa6ae;
            font-size: 11px;
        }

        .vacio {
            padding: 25px;
            text-align: center;
            color: #89959d;
            font-size: 13px;
        }

        /* =====================================
           ALERTAS
        ===================================== */

        .alerta {
            padding: 14px 17px;
            margin-bottom: 20px;
            border-radius: 9px;
            background: #e0f5eb;
            color: #176342;
            font-size: 13px;
        }

        .alerta.error {
            background: #fde8e8;
            color: #a83245;
        }

        /* =====================================
           RESPONSIVE
        ===================================== */

        @media(max-width: 1100px) {

            .estadisticas {
                grid-template-columns: repeat(3, 1fr);
            }

            .accesos {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media(max-width: 750px) {

            .estructura {
                display: block;
            }

            .sidebar {
                width: 100%;
                min-height: auto;
                padding: 17px;
            }

            .marca {
                padding-bottom: 15px;
                margin-bottom: 12px;
            }

            .menu {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sidebar-pie {
                margin-top: 15px;
                padding-top: 12px;
            }

            .contenido {
                padding: 20px 15px;
            }

        }

        @media(max-width: 480px) {

            .estadisticas {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .accesos {
                grid-template-columns: 1fr;
            }

            .tarjeta {
                padding: 15px;
            }

            .encabezado h1 {
                font-size: 22px;
            }

            .mensaje-item .correo {
                display: block;
                margin: 6px 0 0;
            }

        }

    </style>

</head>

<body>

<div class="estructura">

    <!-- =====================================
         BARRA LATERAL
    ====================================== -->

    <aside class="sidebar">

        <div class="marca">

            <h2>RADIO STEREO</h2>

            <p>ACADEMY NS · ADMINISTRACIÓN</p>

        </div>


        <p class="titulo-menu">Menú principal</p>


        <nav class="menu">

            <a href="index.php" class="activo">
                <span class="icono">⌂</span>
                Panel principal
            </a>

            <a href="programas.php">
                <span class="icono">◷</span>
                Programación
            </a>

            <a href="podcasts.php">
                <span class="icono">♫</span>
                Podcasts
            </a>

            <a href="noticias.php">
                <span class="icono">▤</span>
                Noticias
            </a>

            <a href="galeria.php">
                <span class="icono">▧</span>
                Galería
            </a>

            <a href="contenido.php">
                <span class="icono">▥</span>
                Contenido institucional
            </a>

            <a href="mensajes.php">
                <span class="icono">✉</span>
                Mensajes de contacto
            </a>

            <a href="configuracion.php">
                <span class="icono">⚙</span>
                Configuración
            </a>

        </nav>


        <!-- PIE DEL MENÚ -->

        <div class="sidebar-pie">

            <a href="../index.php" class="ver-sitio" target="_blank" rel="noopener noreferrer">
                ↗ Ver sitio público
            </a>

            <div class="usuario">

                Sesión iniciada como:

                <strong><?= e($nombreAdministrador) ?></strong>

            </div>

            <a href="logout.php" class="cerrar-sesion">
                ↪ Cerrar sesión
            </a>

        </div>

    </aside>


    <!-- =====================================
         CONTENIDO PRINCIPAL
    ====================================== -->

    <main class="contenido">


        <!-- ENCABEZADO -->

        <header class="encabezado">

            <div>

                <h1>Panel principal</h1>

                <p>
                    Administración de <?= e($nombreEmisora) ?>
                </p>

            </div>

            <div class="fecha">
                <?= e(date('d/m/Y')) ?>
            </div>

        </header>


        <!-- MENSAJES DEL SISTEMA -->

        <?php if (!empty($mensajeFlash)): ?>

            <div class="alerta">
                <?= e($mensajeFlash['texto']) ?>
            </div>

        <?php endif; ?>


        <?php if ($errorPanel): ?>

            <div class="alerta error">
                No fue posible cargar toda la información del panel.
                Revisa los registros de errores de PHP.
            </div>

        <?php endif; ?>


        <!-- BIENVENIDA -->

        <section class="bienvenida">

            <h2>
                ¡Bienvenido al panel administrativo!
            </h2>

            <p>
                Desde este espacio puedes administrar la programación,
                podcasts, noticias, galería, mensajes y contenido
                institucional de <?= e($nombreEmisora) ?>.
            </p>

        </section>


        <!-- ESTADÍSTICAS -->

        <h2 class="titulo-seccion">
            Resumen general
        </h2>


        <section class="estadisticas">

            <article class="tarjeta">

                <div class="simbolo">◷</div>

                <h3><?= $estadisticas['programas'] ?></h3>

                <p>Programas activos</p>

            </article>


            <article class="tarjeta">

                <div class="simbolo">♫</div>

                <h3><?= $estadisticas['podcasts'] ?></h3>

                <p>Podcasts activos</p>

            </article>


            <article class="tarjeta">

                <div class="simbolo">▤</div>

                <h3><?= $estadisticas['noticias'] ?></h3>

                <p>Noticias publicadas</p>

            </article>


            <article class="tarjeta">

                <div class="simbolo">▧</div>

                <h3><?= $estadisticas['galeria'] ?></h3>

                <p>Imágenes de galería</p>

            </article>


            <article class="tarjeta">

                <div class="simbolo">✉</div>

                <h3><?= $estadisticas['mensajes'] ?></h3>

                <p>Mensajes nuevos</p>

            </article>

        </section>


        <!-- MÓDULOS -->

        <h2 class="titulo-seccion">
            Módulos de administración
        </h2>


        <section class="accesos">

            <?php foreach ($modulos as $modulo): ?>

                <a
                    href="<?= e($modulo['ruta']) ?>"
                    class="acceso"
                >

                    <div class="simbolo">
                        <?= e($modulo['icono']) ?>
                    </div>


                    <h3>
                        <?= e($modulo['titulo']) ?>
                    </h3>


                    <p>
                        <?= e($modulo['descripcion']) ?>
                    </p>


                    <div class="acceso-inferior">

                        <?php if ($modulo['cantidad'] !== null): ?>

                            <span class="cantidad-modulo">

                                <?= (int) $modulo['cantidad'] ?>

                                <?= e($modulo['unidad']) ?>

                            </span>

                        <?php else: ?>

                            <span class="cantidad-modulo">
                                Administrar contenido
                            </span>

                        <?php endif; ?>


                        <span class="abrir-modulo">
                            Abrir →
                        </span>

                    </div>

                </a>

            <?php endforeach; ?>

        </section>


        <!-- ÚLTIMOS MENSAJES -->

        <section class="panel-mensajes">

            <div class="panel-mensajes-encabezado">

                <h2>
                    Últimos mensajes recibidos
                </h2>

                <a href="mensajes.php" class="ver-mensajes">
                    Ver todos los mensajes →
                </a>

            </div>


            <?php if (empty($ultimosMensajes)): ?>

                <div class="vacio">
                    Todavía no hay mensajes de contacto.
                </div>

            <?php else: ?>

                <?php foreach ($ultimosMensajes as $mensajeItem): ?>

                    <article class="mensaje-item">

                        <strong>
                            <?= e($mensajeItem['nombre']) ?>
                        </strong>

                        <span class="correo">
                            <?= e($mensajeItem['correo']) ?>
                        </span>

                        <p>
                            <?= e($mensajeItem['mensaje']) ?>
                        </p>

                        <small>

                            <?= e(fechaFormateada(
                                $mensajeItem['creado_en'],
                                'd/m/Y h:i A'
                            )) ?>

                            · Estado: <?= e($mensajeItem['estado']) ?>

                        </small>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>


    </main>

</div>

</body>
</html>
