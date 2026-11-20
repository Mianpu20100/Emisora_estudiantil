<?php
declare(strict_types=1);

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/funciones.php';

$noticias = [];
$errorNoticias = false;

try {
    $consulta = $conexion->prepare("
        SELECT
            id,
            titulo,
            slug,
            categoria,
            resumen,
            contenido,
            imagen,
            fecha_publicacion
        FROM noticias
        WHERE estado = 'publicada'
          AND fecha_publicacion <= NOW()
        ORDER BY fecha_publicacion DESC, id DESC
    ");

    $consulta->execute();

    $noticias = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Error al consultar noticias: ' . $e->getMessage());
    $errorNoticias = true;
}

/**
 * Formatea la fecha de publicación.
 */
function formatoFechaNoticia(?string $fecha): string
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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Noticias | Radio Stereo Academy NS</title>

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

                <li><a href="Podcasts.php">Podcasts</a></li>

                <li>
                    <a href="Noticias.php" class="active">Noticias</a>
                </li>

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

                <h2>Noticias</h2>

                <span class="badge">Lo más reciente</span>

            </div>


            <!-- NOTICIAS DINÁMICAS -->
            <div class="news-grid">


                <?php if ($errorNoticias): ?>

                    <div class="mensaje-noticias">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <h3>No fue posible cargar las noticias</h3>

                        <p>
                            Intenta nuevamente más tarde.
                        </p>

                    </div>


                <?php elseif (empty($noticias)): ?>

                    <div class="mensaje-noticias">

                        <i class="fa-regular fa-newspaper"></i>

                        <h3>Próximamente tendremos nuevas noticias</h3>

                        <p>
                            Estamos preparando información para compartir
                            con nuestra comunidad.
                        </p>

                    </div>


                <?php else: ?>


                    <?php foreach ($noticias as $noticia): ?>

                        <article class="news-card">


                            <!-- IMAGEN -->
                            <?php if (!empty($noticia['imagen'])): ?>

                                <div class="news-image">

                                    <img
                                        src="<?= e($noticia['imagen']) ?>"
                                        alt="<?= e($noticia['titulo']) ?>"
                                        loading="lazy"
                                    >

                                </div>

                            <?php endif; ?>


                            <!-- CATEGORÍA -->
                            <?php if (!empty($noticia['categoria'])): ?>

                                <span class="tag">
                                    <?= e($noticia['categoria']) ?>
                                </span>

                            <?php endif; ?>


                            <!-- TÍTULO -->
                            <h3>
                                <?= e($noticia['titulo']) ?>
                            </h3>


                            <!-- RESUMEN -->
                            <?php if (!empty($noticia['resumen'])): ?>

                                <p>
                                    <?= nl2br(e($noticia['resumen'])) ?>
                                </p>

                            <?php endif; ?>


                            <!-- FECHA -->
                            <?php if (!empty($noticia['fecha_publicacion'])): ?>

                                <div class="news-date">

                                    <i class="fa-regular fa-calendar"></i>

                                    <span>
                                        <?= e(
                                            formatoFechaNoticia(
                                                $noticia['fecha_publicacion']
                                            )
                                        ) ?>
                                    </span>

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

                <a href="Galería.php" class="link-btn primary">
                    Ver galería
                </a>

            </div>


        </section>

    </main>


    <!-- PIE DE PÁGINA -->
    <footer class="footer">

        <div class="footer-contenido">


            <div class="footer-columna">

                <img
                    src="assets/IMG/LOGO FINAL.jpeg"
                    class="footer-logo"
                    alt="Radio Stereo Academy NS"
                >

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

                <p>
                    <i class="fa-solid fa-location-dot"></i>
                    Supatá, Cundinamarca
                </p>

                <p>
                    <i class="fa-solid fa-envelope"></i>
                    iedsalud.supata@gmail.com
                </p>

                <p>
                    <i class="fa-brands fa-whatsapp"></i>
                    WhatsApp
                </p>

                <p>
                    <i class="fa-solid fa-headphones"></i>
                    Transmisión en vivo
                </p>

            </div>


            <div class="footer-columna">

                <h3>Síguenos</h3>

                <p>
                    Conéctate con Radio Stereo Tech en nuestras redes sociales.
                </p>

                <div class="redes">

                    <a
                        href="https://www.facebook.com/share/1CDN9QXCuh/"
                        aria-label="Facebook"
                    >
                        <i class="fab fa-facebook-f"></i>
                    </a>

                    <a
                        href="https://www.instagram.com/radio_stereoacademy_ns?utm_source=qr&srtk=MWR2aG0wbnF5Zngw"
                        aria-label="Instagram"
                    >
                        <i class="fab fa-instagram"></i>
                    </a>

                    <a
                        href="https://www.tiktok.com/@nuestrasenorasalud?_r=1&_t=ZS-9ACC7owc3L2"
                        aria-label="TikTok"
                    >
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
