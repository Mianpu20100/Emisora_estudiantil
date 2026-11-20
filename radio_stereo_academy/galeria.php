<?php
declare(strict_types=1);

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/funciones.php';

$imagenes = [];
$errorGaleria = false;

try {
    $consulta = $conexion->query("
        SELECT
            id,
            titulo,
            descripcion,
            imagen,
            categoria
        FROM galeria
        WHERE estado = 'activo'
        ORDER BY orden ASC, id DESC
    ");

    $imagenes = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Error al consultar la galería: ' . $e->getMessage());
    $errorGaleria = true;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Galería | Radio Stereo Academy NS</title>

    <link rel="stylesheet" href="assets/CSS/estilos.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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

                <li><a href="Noticias.php">Noticias</a></li>

                <li>
                    <a href="Galería.php" class="active">Galería</a>
                </li>

                <li><a href="Nosotros.php">Nosotros</a></li>

                <li><a href="Contacto.php">Contacto</a></li>

            </ul>

        </nav>

    </header>


    <!-- CONTENIDO PRINCIPAL -->
    <main class="page-shell">

        <section class="section mostrar">


            <div class="section-header">

                <h2>Galería</h2>

                <span class="badge">Momentos de la comunidad</span>

            </div>


            <!-- GALERÍA DINÁMICA -->
            <div class="gallery-grid">


                <?php if ($errorGaleria): ?>

                    <div class="mensaje-galeria">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <h3>No fue posible cargar la galería</h3>

                        <p>
                            Intenta nuevamente más tarde.
                        </p>

                    </div>


                <?php elseif (empty($imagenes)): ?>

                    <div class="mensaje-galeria">

                        <i class="fa-solid fa-camera"></i>

                        <h3>Próximamente tendremos nuevas fotografías</h3>

                        <p>
                            Estamos preparando una colección de momentos
                            especiales de nuestra comunidad.
                        </p>

                    </div>


                <?php else: ?>


                    <?php foreach ($imagenes as $imagen): ?>

                        <article class="gallery-card">


                            <!-- FOTOGRAFÍA -->
                            <div class="gallery-thumb">

                                <img
                                    src="<?= e($imagen['imagen']) ?>"
                                    alt="<?= e($imagen['titulo']) ?>"
                                    loading="lazy"
                                >

                            </div>


                            <!-- CATEGORÍA -->
                            <?php if (!empty($imagen['categoria'])): ?>

                                <span class="gallery-category">
                                    <?= e($imagen['categoria']) ?>
                                </span>

                            <?php endif; ?>


                            <!-- TÍTULO -->
                            <h3>
                                <?= e($imagen['titulo']) ?>
                            </h3>


                            <!-- DESCRIPCIÓN -->
                            <?php if (!empty($imagen['descripcion'])): ?>

                                <p>
                                    <?= nl2br(e($imagen['descripcion'])) ?>
                                </p>

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

                <a href="Nosotros.php" class="link-btn primary">
                    Conocer más
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
                        href="https://www.tiktok.com/@academy.stereo.ns?_r=1&_t=ZS-9AZqnHLU4hR"
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
