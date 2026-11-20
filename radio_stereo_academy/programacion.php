<?php
declare(strict_types=1);

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/funciones.php';

/*
|--------------------------------------------------------------------------
| Consultar programas activos
|--------------------------------------------------------------------------
*/

try {
    $consulta = $conexion->query("
        SELECT
            id,
            categoria,
            titulo,
            descripcion,
            dias,
            hora_inicio,
            hora_fin,
            imagen
        FROM programas
        WHERE estado = 'activo'
        ORDER BY orden ASC, hora_inicio ASC, id ASC
    ");

    $programas = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Error al consultar programación: ' . $e->getMessage());
    $programas = [];
    $errorProgramacion = true;
}

/*
|--------------------------------------------------------------------------
| Formatear horarios
|--------------------------------------------------------------------------
*/

function formatoHoraPrograma(?string $hora): string
{
    if (!$hora) {
        return '';
    }

    return date('H:i', strtotime($hora));
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Programación | Radio Stereo Academy NS</title>

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

                <li>
                    <a href="Programacion.php" class="active">
                        Programación
                    </a>
                </li>

                <li><a href="Podcasts.php">Podcasts</a></li>

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

                <h2>Programación</h2>

                <span class="badge">
                    Agenda semanal
                </span>

            </div>

            <div class="programa-list">

                <?php if (isset($errorProgramacion)): ?>

                    <div class="programa-item">
                        <div>
                            <strong>Información</strong>

                            <h3>Programación no disponible</h3>

                            <p>
                                En este momento no podemos cargar los programas.
                                Inténtalo nuevamente más tarde.
                            </p>
                        </div>
                    </div>

                <?php elseif (count($programas) > 0): ?>

                    <?php foreach ($programas as $programa): ?>

                        <article class="programa-item">

                            <div>

                                <strong>
                                    <?= e($programa['categoria'] ?: 'Programa radial') ?>
                                </strong>

                                <h3>
                                    <?= e($programa['titulo']) ?>
                                </h3>

                                <p>
                                    <?= nl2br(e($programa['descripcion'])) ?>
                                </p>

                            </div>

                            <div class="tiempo">

                                <?= e($programa['dias'] ?: 'Horario por definir') ?>

                                <br>

                                <?php
                                $inicio = formatoHoraPrograma($programa['hora_inicio']);
                                $fin = formatoHoraPrograma($programa['hora_fin']);
                                ?>

                                <?php if ($inicio !== '' && $fin !== ''): ?>

                                    <?= e($inicio) ?> - <?= e($fin) ?>

                                <?php else: ?>

                                    Horario por definir

                                <?php endif; ?>

                            </div>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="programa-item">

                        <div>

                            <strong>Radio Stereo Academy NS</strong>

                            <h3>Próximamente tendremos programación</h3>

                            <p>
                                Estamos preparando nuevos espacios radiales
                                para nuestra comunidad. Muy pronto podrás
                                consultar aquí los programas y sus horarios.
                            </p>

                        </div>

                    </div>

                <?php endif; ?>

            </div>

            <!-- BOTONES INFERIORES -->

            <div class="back-actions">

                <a href="index.php" class="back-btn">
                    Volver al home
                </a>

                <a href="Podcasts.php" class="link-btn primary">
                    Ir a podcasts
                </a>

            </div>

        </section>

    </main>

    <!-- FOOTER -->

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
                    La voz que conecta a Supatá.
                    Un espacio para informar, entretener
                    y dar voz a nuestra comunidad.
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
                    Conéctate con Radio Stereo Academy NS
                    en nuestras redes sociales.
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
