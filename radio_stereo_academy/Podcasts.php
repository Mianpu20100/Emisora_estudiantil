<?php
declare(strict_types=1);

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/funciones.php';

$podcasts = [];
$errorPodcasts = false;

try {
    $consulta = $conexion->query("
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
        ORDER BY orden ASC, fecha_publicacion DESC, id DESC
    ");

    $podcasts = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Error al consultar los podcasts: ' . $e->getMessage());
    $errorPodcasts = true;
}

/**
 * Formatea la fecha de publicación.
 */
function formatoFechaPodcast(?string $fecha): string
{
    if (empty($fecha)) {
        return '';
    }

    $timestamp = strtotime($fecha);

    if ($timestamp === false) {
        return '';
    }

    return date('d/m/Y', $timestamp);
}

/**
 * Formatea la duración del episodio.
 */
function formatoDuracionPodcast(?string $duracion): string
{
    if (empty($duracion)) {
        return '';
    }

    return $duracion;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Podcasts | Radio Stereo Academy NS</title>

    <link rel="stylesheet" href="assets/CSS/estilos.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <div id="particles"></div>
    <div class="ondas"></div>

    <!-- ENCABEZADO -->
    <header>
        <nav>

            <div class="ESCUDO">
                <img src="assets/IMG/ESCUDO.jpeg" alt="Radio Stereo Tech">
                <h2>Radio Stereo Academy NS</h2>
            </div>

            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="Programacion.php">Programación</a></li>
                <li><a href="Podcasts.php" class="active">Podcasts</a></li>
                <li><a href="Noticias.php">Noticias</a></li>
                <li><a href="Galería.php">Galería</a></li>
                <li><a href="Nosotros.php">Nosotros</a></li>
                <li><a href="Contacto.php">Contacto</a></li>
            </ul>

        </nav>
    </header>


    <!-- CONTENIDO PRINCIPAL -->
    <main class="page-shell">

        <section class="section mostrar">

            <div class="section-header">
                <h2>Podcasts</h2>
                <span class="badge">Escucha con nosotros</span>
            </div>


            <!-- PODCASTS DINÁMICOS -->
            <div class="podcast-grid">

                <?php if ($errorPodcasts): ?>

                    <div class="mensaje-podcasts">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <h3>No fue posible cargar los podcasts</h3>
                        <p>Intenta nuevamente más tarde.</p>
                    </div>

                <?php elseif (empty($podcasts)): ?>

                    <div class="mensaje-podcasts">
                        <i class="fa-solid fa-headphones"></i>
                        <h3>Próximamente tendremos nuevos episodios</h3>
                        <p>
                            Estamos preparando contenido para que disfrutes
                            de nuestros podcasts.
                        </p>
                    </div>

                <?php else: ?>

                    <?php foreach ($podcasts as $podcast): ?>

                        <article class="podcast-card">

                            <!-- PORTADA -->
                            <div class="podcast-cover">

                                <?php if (!empty($podcast['portada'])): ?>

                                    <img
                                        src="<?= e($podcast['portada']) ?>"
                                        alt="Portada de <?= e($podcast['titulo']) ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <i class="fa-solid fa-headphones"></i>

                                <?php endif; ?>

                            </div>


                            <!-- TÍTULO -->
                            <h3>
                                <?= e($podcast['titulo']) ?>
                            </h3>


                            <!-- DESCRIPCIÓN -->
                            <?php if (!empty($podcast['descripcion'])): ?>

                                <p>
                                    <?= nl2br(e($podcast['descripcion'])) ?>
                                </p>

                            <?php endif; ?>


                            <!-- INFORMACIÓN DEL EPISODIO -->
                            <div class="podcast-informacion">

                                <?php if (!empty($podcast['fecha_publicacion'])): ?>

                                    <span>
                                        <i class="fa-regular fa-calendar"></i>

                                        <?= e(
                                            formatoFechaPodcast(
                                                $podcast['fecha_publicacion']
                                            )
                                        ) ?>
                                    </span>

                                <?php endif; ?>


                                <?php if (!empty($podcast['duracion'])): ?>

                                    <span>
                                        <i class="fa-regular fa-clock"></i>

                                        <?= e(
                                            formatoDuracionPodcast(
                                                $podcast['duracion']
                                            )
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- REPRODUCTOR DE AUDIO -->
                            <?php if (!empty($podcast['archivo_audio'])): ?>

                                <div class="podcast-reproductor">

                                    <audio controls preload="none">
                                        <source
                                            src="<?= e($podcast['archivo_audio']) ?>"
                                        >
                                        Tu navegador no admite la reproducción
                                        de audio.
                                    </audio>

                                </div>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>


            <!-- BOTONES INFERIORES -->
            <div class="back-actions">

                <a href="index.php" class="back-btn">
                    Volver al home
                </a>

                <a href="Noticias.php" class="link-btn primary">
                    Ir a noticias
                </a>

            </div>

        </section>

    </main>


    <!-- PIE DE PÁGINA -->
    <footer class="footer">

        <div class="footer-contenido">

            <div class="footer-columna">

                <img src="assets/IMG/LOGO FINAL.jpeg" class="footer-logo" alt="Radio Stereo Academy NS" >

                <h3>Radio Stereo Academy NS</h3>

                <p>
                    La voz que conecta a Supatá. Un espacio para informar,
                    entretener y dar voz a nuestra comunidad.
                </p>

            </div>


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


            <div class="footer-columna">

                <h3>Contáctanos</h3>

                <p> <i class="fa-solid fa-location-dot"></i> Supatá, Cundinamarca </p>

                <p> <i class="fa-solid fa-envelope"></i> iedsalud.supata@gmail.com </p>

                <p> <i class="fa-brands fa-whatsapp"></i> WhatsApp </p>

                <p> <i class="fa-solid fa-headphones"></i> Transmisión en vivo </p>

            </div>


            <div class="footer-columna">

                <h3>Síguenos</h3>

                <p>
                    Conéctate con Radio Stereo Tech en nuestras redes sociales.
                </p>

                <div class="redes">

                    <a href="https://www.facebook.com/share/1CDN9QXCuh/" aria-label="Facebook" >
                        <i class="fab fa-facebook-f"></i>
                    </a>

                    <a href="https://www.instagram.com/radio_stereoacademy_ns?utm_source=qr&srtk=MWR2aG0wbnF5Zngw" aria-label="Instagram" >
                        <i class="fab fa-instagram"></i>
                    </a>

                    <a href="https://www.tiktok.com/@academy.stereo.ns?_r=1&_t=ZS-9AZqnHLU4hR" aria-label="TikTok" >
                        <i class="fab fa-tiktok"></i>
                    </a>

                </div>

            </div>

        </div>


        <div class="footer-bottom">

            <p>© 2026 Radio Stereo Tech Supatá</p>

            <p>Todos los derechos reservados</p>

        </div>

    </footer>


    <script src="assets/JS/script.js"></script>

</body>
</html>
