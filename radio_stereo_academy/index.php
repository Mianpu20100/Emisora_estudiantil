<?php
declare(strict_types=1);

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/funciones.php';

iniciarSesionSegura();

/* =========================================================
   CONFIGURACIÓN GENERAL
========================================================= */

$nombreEmisora = obtenerConfiguracion(
    $conexion,
    'nombre_emisora',
    'Radio Stereo Academy NS'
);

$eslogan = obtenerConfiguracion(
    $conexion,
    'eslogan',
    'La voz que conecta a Supatá'
);

$correo = obtenerConfiguracion(
    $conexion,
    'correo',
    'iedsalud.supata@gmail.com'
);

$ubicacion = obtenerConfiguracion(
    $conexion,
    'ubicacion',
    'Supatá, Cundinamarca'
);

$streamingActivo = obtenerConfiguracion(
    $conexion,
    'streaming_activo',
    '0'
);

$streamingUrl = obtenerConfiguracion(
    $conexion,
    'streaming_url',
    ''
);

$puedeTransmitir = (
    $streamingActivo === '1'
    && filter_var($streamingUrl, FILTER_VALIDATE_URL) !== false
);

/* =========================================================
   VARIABLES DE LA PÁGINA
========================================================= */

$programas = [];
$podcasts = [];
$noticias = [];
$totalProgramas = 0;

try {

    /* PROGRAMAS ACTIVOS */

    $consultaProgramas = $conexion->query("
        SELECT
            id,
            categoria,
            titulo,
            descripcion,
            dias,
            hora_inicio,
            hora_fin
        FROM programas
        WHERE estado = 'activo'
        ORDER BY orden ASC, hora_inicio ASC, id DESC
        LIMIT 3
    ");

    $programas = $consultaProgramas->fetchAll();

    /* TOTAL DE PROGRAMAS ACTIVOS */

    $consultaTotal = $conexion->query("
        SELECT COUNT(*)
        FROM programas
        WHERE estado = 'activo'
    ");

    $totalProgramas = (int) $consultaTotal->fetchColumn();


    /* PODCASTS ACTIVOS */

    $consultaPodcasts = $conexion->query("
        SELECT
            id,
            titulo,
            descripcion,
            portada,
            archivo_audio,
            duracion,
            fecha_publicacion
        FROM podcasts
        WHERE estado = 'activo'
        ORDER BY fecha_publicacion DESC, orden ASC, id DESC
        LIMIT 3
    ");

    $podcasts = $consultaPodcasts->fetchAll();


    /* NOTICIAS PUBLICADAS */

    $consultaNoticias = $conexion->query("
        SELECT
            id,
            titulo,
            categoria,
            resumen,
            contenido,
            imagen,
            slug,
            fecha_publicacion
        FROM noticias
        WHERE estado = 'publicada'
        AND fecha_publicacion <= NOW()
        ORDER BY fecha_publicacion DESC, id DESC
        LIMIT 3
    ");

    $noticias = $consultaNoticias->fetchAll();

} catch (PDOException $error) {

    error_log(
        'Error al cargar la página principal: ' . $error->getMessage()
    );

}

/* =========================================================
    INFORMACIÓN PARA LA PÁGINA
========================================================= */

$podcastDestacado = !empty($podcasts)
    ? $podcasts[0]['titulo']
    : 'Próximamente';

$programaDestacado = !empty($programas)
    ? $programas[0]['titulo']
    : 'Consulta nuestra programación';

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($nombreEmisora) ?> | Inicio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/CSS/estilos.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

    <div id="particles"></div>

    <div class="ondas"></div>


    <!-- =====================================================
         ENCABEZADO
    ====================================================== -->

    <header>

        <nav>

            <div class="ESCUDO">

                <img src="assets/IMG/ESCUDO.jpeg" alt="<?= e($nombreEmisora) ?>">

                <h2>
                    RADIO STEREO<br>
                    <span>ACADEMY NS</span>
                </h2>

            </div>


            <ul>

                    <li><a href="index.php" class="active">Inicio</a></li>

                    <li><a href="Programacion.php">Programación</a></li>

                    <li><a href="Podcasts.php">Podcasts</a></li>

                    <li><a href="Noticias.php">Noticias</a></li>

                    <li><a href="Galería.php">Galería</a></li>

                    <li><a href="Nosotros.php">Nosotros</a></li>

                    <li><a href="Contacto.php">Contacto</a></li>

                    <!-- Acceso al administrador -->
                    <li class="nav-admin"> <a href="admin/login.php" aria-label="Acceso administrativo" title="Acceso administrativo"> <i class="fa-solid fa-lock"></i> </a> </li>

            </ul>

        </nav>

    </header>


    <!-- =====================================================
        BANNER PROMOCIONAL
    ====================================================== -->

    <div class="promo-banner" aria-label="Banner promocional de Radio Stereo Academy NS">

        <div class="promo-marquee">

            <span>RADIO STEREO ACADEMY NS</span>
            <span>•</span>
            <span>CONEXIÓN ESTUDIANTIL</span>
            <span>•</span>
            <span>IDEAS EN VIVO</span>
            <span>•</span>
            <span>CREANDO FUTURO</span>
            <span>•</span>

            <span>RADIO STEREO ACADEMY NS</span>
            <span>•</span>
            <span>CONEXIÓN ESTUDIANTIL</span>
            <span>•</span>
            <span>IDEAS EN VIVO</span>
            <span>•</span>
            <span>CREANDO FUTURO</span>

        </div>

    </div>


    <!-- =====================================================
        SECCIÓN PRINCIPAL
    ====================================================== -->

    <section class="inicio hero">

        <div class="hero-copy">

            <p class="eyebrow">

                <span class="live-dot"></span>

                La señal de nuestra comunidad

            </p>


            <h1>
                La voz que<br>
                <em>conecta</em> a Supatá.
            </h1>


            <p class="hero-intro">

                Un espacio creado por estudiantes para informar,
                compartir ideas y llevar la energía de la IED
                Nuestra Señora de la Salud a cada rincón.

            </p>


            <div class="hero-actions">

                <?php if ($puedeTransmitir): ?>

                    <a
                        class="btn btn-hero"
                        href="<?= e($streamingUrl) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >

                        <i class="fa-solid fa-play"></i>

                        Escuchar en vivo

                    </a>

                <?php else: ?>

                    <button
                        class="btn btn-hero"
                        type="button"
                        disabled
                        title="La transmisión todavía no está disponible"
                    >

                        <i class="fa-solid fa-headphones"></i>

                        Transmisión próximamente

                    </button>

                <?php endif; ?>


                <a class="text-link" href="#programacion">

                    Ver programación

                    <span aria-hidden="true">→</span>

                </a>

            </div>


            <div class="hero-badges" aria-label="Datos de la emisora">

                <div>

                    <strong>+<?= $totalProgramas ?></strong>

                    <span>Programas</span>

                </div>


                <div>

                    <strong><?= $puedeTransmitir ? 'ON' : 'OFF' ?></strong>

                    <span>Transmisión</span>

                </div>


                <div>

                    <strong>100%</strong>

                    <span>Estudiantil</span>

                </div>

            </div>

        </div>


        <div class="hero-art" aria-label="Identidad visual de Radio Stereo Academy NS">

            <div class="art-ring ring-one"></div>

            <div class="art-ring ring-two"></div>


            <div class="signal-badge">

                <span>FM</span>

                <small>IED · SUPATÁ</small>

            </div>


            <div class="logo-window">

                <img src="assets/IMG/LOGO FINAL.jpeg" alt="Logo Radio Stereo Academy NS">

            </div>


            <p class="art-caption">

                UNA EMISORA<br>

                <strong>HECHA EN EL COLEGIO</strong>

            </p>

        </div>

    </section>


    <!-- =====================================================
        INFORMACIÓN RÁPIDA
    ====================================================== -->

    <section class="quick-info" aria-label="Información de la emisora">

        <div>

            <span class="info-icon">◉</span>

            <p>

                <strong>Programas disponibles</strong>

                <span><?= $totalProgramas ?> programas activos</span>

            </p>

        </div>


        <div>

            <span class="info-icon">◷</span>

            <p>

                <strong>Podcast destacado</strong>

                <span><?= e($podcastDestacado) ?></span>

            </p>

        </div>


        <div>

            <span class="info-icon">⌁</span>

            <p>

                <strong>Participa</strong>

                <span>Comparte tu historia con nosotros</span>

            </p>

        </div>

    </section>


    <!-- =====================================================
        CONTENIDO PRINCIPAL
    ====================================================== -->

    <main class="page-shell">


        <!-- PRESENTACIÓN -->

        <section class="feature-band section mostrar">

            <div>

                <span class="badge">Radio escolar</span>

                <h3>
                    La comunidad escuchando, creando y compartiendo.
                </h3>

            </div>


            <p>

                Desde la IED Nuestra Señora de la Salud,
                Radio Stereo Academy NS conecta estudiantes,
                docentes y familias con historias, música e ideas
                que fortalecen nuestra comunidad.

            </p>

        </section>


        <!-- =================================================
             PROGRAMACIÓN DINÁMICA
        ================================================== -->

        <section class="section mostrar" id="programacion">

            <div class="section-header">

                <h2>Programación</h2>

                <span class="badge">Programas activos</span>

            </div>


            <div class="card-grid">

                <?php if (!empty($programas)): ?>

                    <?php foreach ($programas as $programa): ?>

                        <article class="card">

                            <span class="hora">

                                <?php

                                $horaInicio = !empty($programa['hora_inicio'])
                                    ? date('H:i', strtotime($programa['hora_inicio']))
                                    : '';

                                $horaFin = !empty($programa['hora_fin'])
                                    ? date('H:i', strtotime($programa['hora_fin']))
                                    : '';

                                if ($horaInicio && $horaFin):

                                ?>

                                    <?= e($horaInicio) ?> - <?= e($horaFin) ?>

                                <?php else: ?>

                                    Horario por confirmar

                                <?php endif; ?>

                            </span>


                            <h3><?= e($programa['titulo']) ?></h3>


                            <div class="mini-line"></div>


                            <p>

                                <?= e($programa['descripcion'] ?? '') ?>

                            </p>


                            <?php if (!empty($programa['dias'])): ?>

                                <p class="programa-dias">

                                    <strong>Días:</strong>

                                    <?= e($programa['dias']) ?>

                                </p>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <article class="card">

                        <h3>Próximamente</h3>

                        <div class="mini-line"></div>

                        <p>
                            Todavía no hay programas activos.
                            Muy pronto encontrarás aquí nuestra programación.
                        </p>

                    </article>

                <?php endif; ?>

            </div>


            <div class="back-actions">

                <a href="Programacion.php" class="link-btn primary">

                    Ver programación completa

                </a>

                <a href="index.php" class="back-btn">

                    Volver al inicio

                </a>

            </div>

        </section>


        <!-- =================================================
             PODCASTS DINÁMICOS
        ================================================== -->

        <section class="section mostrar">

            <div class="section-header">

                <h2>Podcasts</h2>

                <span class="badge">Destacados</span>

            </div>


            <div class="card-grid">

                <?php if (!empty($podcasts)): ?>

                    <?php foreach ($podcasts as $indice => $podcast): ?>

                        <article class="card">

                            <span class="hora">

                                Podcast <?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?>

                            </span>


                            <h3><?= e($podcast['titulo']) ?></h3>


                            <div class="mini-line"></div>


                            <p>

                                <?= e($podcast['descripcion'] ?? '') ?>

                            </p>


                            <?php if (!empty($podcast['fecha_publicacion'])): ?>

                                <p>

                                    <strong>Publicado:</strong>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime($podcast['fecha_publicacion'])
                                    ) ?>

                                </p>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <article class="card">

                        <h3>Próximamente</h3>

                        <div class="mini-line"></div>

                        <p>
                            Todavía no hay podcasts disponibles.
                            Pronto podrás escuchar nuestros episodios.
                        </p>

                    </article>

                <?php endif; ?>

            </div>


            <div class="back-actions">

                <a href="Podcasts.php" class="link-btn primary">

                    Escuchar podcasts

                </a>

                <a href="index.php" class="back-btn">

                    Volver al inicio

                </a>

            </div>

        </section>


        <!-- =================================================
            NOTICIAS DINÁMICAS
        ================================================== -->

        <section class="section mostrar">

            <div class="section-header">

                <h2>Noticias</h2>

                <span class="badge">Actualidad</span>

            </div>


            <div class="card-grid">

                <?php if (!empty($noticias)): ?>

                    <?php foreach ($noticias as $noticia): ?>

                        <article class="card">

                            <span class="hora">

                                <?= e($noticia['categoria'] ?? 'Institucional') ?>

                            </span>


                            <h3><?= e($noticia['titulo']) ?></h3>


                            <div class="mini-line"></div>


                            <p>

                                <?= e($noticia['resumen'] ?? '') ?>

                            </p>


                            <?php if (!empty($noticia['fecha_publicacion'])): ?>

                                <p>

                                    <strong>Fecha:</strong>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime($noticia['fecha_publicacion'])
                                    ) ?>

                                </p>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <article class="card">

                        <h3>Próximamente</h3>

                        <div class="mini-line"></div>

                        <p>
                            Todavía no hay noticias publicadas.
                            Aquí aparecerán las novedades de nuestra institución.
                        </p>

                    </article>

                <?php endif; ?>

            </div>


            <div class="back-actions">

                <a href="Noticias.php" class="link-btn primary">

                    Leer noticias

                </a>

                <a href="index.php" class="back-btn">

                    Volver al inicio

                </a>

            </div>

        </section>


    </main>


    <!-- =====================================================
        PIE DE PÁGINA
    ====================================================== -->

    <footer class="footer">

        <div class="footer-contenido">


            <!-- IDENTIDAD -->

            <div class="footer-columna">

                <img
                    src="assets/IMG/LOGO FINAL.jpeg"
                    class="footer-logo"
                    alt="<?= e($nombreEmisora) ?>"
                >

                <h3><?= e($nombreEmisora) ?></h3>

                <p>

                    <?= e($eslogan) ?>.
                    Un espacio para informar, entretener y dar voz
                    a nuestra comunidad.

                </p>

            </div>


            <!-- ENLACES -->

            <div class="footer-columna">

                <h3>Enlaces</h3>

                <ul class="footer-enlaces">

                    <li><a href="index.php">Inicio</a></li>

                    <li><a href="Programacion.php">Programación</a></li>

                    <li><a href="Podcasts.php">Podcasts</a></li>

                    <li><a href="Noticias.php">Noticias</a></li>

                    <li><a href="Galería.php">Galería</a></li>

                </ul>

            </div>


            <!-- CONTACTO -->

            <div class="footer-columna">

                <h3>Contáctanos</h3>

                <p>

                    <i class="fa-solid fa-location-dot"></i>

                    <?= e($ubicacion) ?>

                </p>


                <p>

                    <i class="fa-solid fa-envelope"></i>

                    <?= e($correo) ?>

                </p>


                <p>

                    <i class="fa-brands fa-whatsapp"></i>

                    WhatsApp

                </p>


                <p>

                    <i class="fa-solid fa-headphones"></i>

                    <?= $puedeTransmitir
                        ? 'Transmisión disponible'
                        : 'Transmisión próximamente' ?>

                </p>

            </div>


            <!-- REDES SOCIALES -->

            <div class="footer-columna">

                <h3>Síguenos</h3>

                <p>
                    Conéctate con Radio Stereo Academy NS
                    en nuestras redes sociales.
                </p>


                <div class="redes">

                    <a
                        href="https://www.facebook.com/share/1CDN9QXCuh/"
                        aria-label="Facebook"
                        target="_blank"
                        rel="noopener noreferrer"
                    >

                        <i class="fab fa-facebook-f"></i>

                    </a>


                    <a
                        href="https://www.instagram.com/radio_stereoacademy_ns?utm_source=qr&srtk=MWR2aG0wbnF5Zngw"
                        aria-label="Instagram"
                        target="_blank"
                        rel="noopener noreferrer"
                    >

                        <i class="fab fa-instagram"></i>

                    </a>


                    <a
                        href="https://www.tiktok.com/@academy.stereo.ns?_r=1&_t=ZS-9AZqnHLU4hR"
                        aria-label="TikTok"
                        target="_blank"
                        rel="noopener noreferrer"
                    >

                        <i class="fab fa-tiktok"></i>

                    </a>

                </div>

            </div>


        </div>


        <div class="footer-bottom">

            <p>© <?= date('Y') ?> Radio Stereo Tech Supatá</p>

            <p>Todos los derechos reservados</p>

        </div>

    </footer>


    <script src="assets/JS/script.js"></script>

</body>

</html>
