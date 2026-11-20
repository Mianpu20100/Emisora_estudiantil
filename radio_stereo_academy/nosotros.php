<?php
declare(strict_types=1);

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/funciones.php';

$contenido = [];
$errorContenido = false;

/*
 * Consultar historia, misión y visión desde la base de datos.
 */
try {
    $consulta = $conexion->query("
        SELECT clave, titulo, contenido
        FROM contenido_pagina
        WHERE clave IN ('historia', 'mision', 'vision')
    ");

    $resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $fila) {
        $contenido[$fila['clave']] = $fila;
    }

} catch (PDOException $e) {
    error_log('Error al consultar el contenido institucional: ' . $e->getMessage());
    $errorContenido = true;
}

/*
 * Contenido alternativo si alguna sección todavía no tiene
 * información guardada en la base de datos.
 */
$historia = $contenido['historia'] ?? [
    'titulo' => 'Nuestra historia',
    'contenido' => 'Radio Stereo Academy NS nace como una propuesta educativa y creativa para conectar a la comunidad de la IED Nuestra Señora de la Salud con la radio, la comunicación, la expresión y la creatividad. Es un espacio donde estudiantes, docentes y familias comparten historias, ideas y proyectos que fortalecen la identidad institucional.'
];

$mision = $contenido['mision'] ?? [
    'titulo' => 'Misión',
    'contenido' => 'La misión de Radio Stereo Academy NS es fortalecer la comunicación de la Institución Educativa Nuestra Señora de la Salud mediante una plataforma web que permita la transmisión, organización y difusión de contenidos educativos, culturales e informativos, promoviendo la participación activa de los estudiantes y el uso responsable de las tecnologías digitales para beneficio de toda la comunidad educativa.'
];

$vision = $contenido['vision'] ?? [
    'titulo' => 'Visión',
    'contenido' => 'Ser una plataforma digital reconocida dentro de la Institución Educativa Nuestra Señora de la Salud por innovar la comunicación escolar, convirtiéndose en un medio interactivo que impulse la creatividad, el aprendizaje y la participación de toda la comunidad educativa.'
];

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nosotros | Radio Stereo Academy NS</title>

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

                <li><a href="Galería.php">Galería</a></li>

                <li>
                    <a href="Nosotros.php" class="active">Nosotros</a>
                </li>

                <li><a href="Contacto.php">Contacto</a></li>

            </ul>

        </nav>

    </header>


    <!-- CONTENIDO PRINCIPAL -->
    <main class="page-shell">

        <section class="section mostrar">


            <!-- ENCABEZADO DE SECCIÓN -->
            <div class="section-header">

                <h2>Nosotros</h2>

                <span class="badge">Nuestra historia</span>

            </div>


            <?php if ($errorContenido): ?>

                <div class="mensaje-contenido">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <p>
                        No fue posible consultar toda la información institucional.
                    </p>

                </div>

            <?php endif; ?>


            <!-- HISTORIA -->
            <div class="historia-institucional">

                <h3>
                    <?= e($historia['titulo']) ?>
                </h3>

                <p style="color: rgba(255,255,255,.8); line-height: 1.7; margin-bottom: 24px;">

                    <?= nl2br(e($historia['contenido'])) ?>

                </p>

            </div>


            <!-- ESTADÍSTICAS -->
            <div class="stats-grid">

                <div class="stat-box">

                    <strong>100%</strong>

                    <span>Estudiantil</span>

                </div>


                <div class="stat-box">

                    <strong>24/7</strong>

                    <span>Creatividad</span>

                </div>


                <div class="stat-box">

                    <strong>+20</strong>

                    <span>Propuestas</span>

                </div>


                <div class="stat-box">

                    <strong>1</strong>

                    <span>Comunidad</span>

                </div>

            </div>


            <!-- MISIÓN Y VISIÓN -->
            <div class="team-box">


                <div class="team-item">

                    <h4>
                        <?= e($mision['titulo']) ?>
                    </h4>

                    <p>
                        <?= nl2br(e($mision['contenido'])) ?>
                    </p>

                </div>


                <div class="team-item">

                    <h4>
                        <?= e($vision['titulo']) ?>
                    </h4>

                    <p>
                        <?= nl2br(e($vision['contenido'])) ?>
                    </p>

                </div>


            </div>


            <!-- BOTONES INFERIORES -->
            <div class="back-actions">

                <a href="index.php" class="back-btn">
                    Volver al home
                </a>

                <a href="Contacto.php" class="link-btn primary">
                    Contactar
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
                    <i class="fa-solid fa-location-dot"></i> Supatá, Cundinamarca </p>

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

    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script src="assets/JS/script.js"></script>

</body>

</html>
