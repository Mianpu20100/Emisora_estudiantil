<?php
declare(strict_types=1);

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/funciones.php';

iniciarSesionSegura();

/*
|--------------------------------------------------------------------------
| PROCESAR FORMULARIO DE CONTACTO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    exigirTokenCSRF();

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    $longitud = static function (string $texto): int {
        return function_exists('mb_strlen')
            ? mb_strlen($texto, 'UTF-8')
            : strlen($texto);
    };

    if ($nombre === '' || $correo === '' || $mensaje === '') {

        establecerMensaje(
            'error',
            'Debes completar todos los campos del formulario.'
        );

    } elseif ($longitud($nombre) > 100) {

        establecerMensaje(
            'error',
            'El nombre no puede superar los 100 caracteres.'
        );

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        establecerMensaje(
            'error',
            'Escribe una dirección de correo electrónico válida.'
        );

    } elseif ($longitud($correo) > 150) {

        establecerMensaje(
            'error',
            'El correo electrónico es demasiado largo.'
        );

    } elseif ($longitud($mensaje) > 5000) {

        establecerMensaje(
            'error',
            'El mensaje no puede superar los 5000 caracteres.'
        );

    } else {

        try {

            $consulta = $conexion->prepare("
                INSERT INTO mensajes_contacto
                    (nombre, correo, mensaje)
                VALUES
                    (:nombre, :correo, :mensaje)
            ");

            $consulta->execute([
                ':nombre' => $nombre,
                ':correo' => $correo,
                ':mensaje' => $mensaje
            ]);

            establecerMensaje(
                'exito',
                '¡Mensaje enviado correctamente! Gracias por comunicarte con Radio Stereo Academy NS.'
            );

            redireccionar('Contacto.php');

        } catch (PDOException $e) {

            error_log(
                'Error al guardar mensaje de contacto: ' .
                $e->getMessage()
            );

            establecerMensaje(
                'error',
                'No fue posible enviar tu mensaje. Intenta nuevamente más tarde.'
            );

        }

    }

}

/*
|--------------------------------------------------------------------------
| MENSAJES DE CONFIRMACIÓN Y ERROR
|--------------------------------------------------------------------------
*/

$mensajeFlash = obtenerMensaje();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contacto | Radio Stereo Academy NS</title>

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

                <li><a href="Nosotros.php">Nosotros</a></li>

                <li>
                    <a href="Contacto.php" class="active">Contacto</a>
                </li>

            </ul>

        </nav>

    </header>


    <!-- CONTENIDO PRINCIPAL -->
    <main class="page-shell">

        <section class="section mostrar">


            <div class="section-header">

                <h2>Contacto</h2>

                <span class="badge">Haz parte</span>

            </div>


            <!-- MENSAJE DE CONFIRMACIÓN -->
            <?php if ($mensajeFlash !== null): ?>

                <div class="contacto-alerta <?= e($mensajeFlash['tipo']) ?>">

                    <?php if ($mensajeFlash['tipo'] === 'exito'): ?>

                        <i class="fa-solid fa-circle-check"></i>

                    <?php else: ?>

                        <i class="fa-solid fa-circle-exclamation"></i>

                    <?php endif; ?>

                    <span>
                        <?= e($mensajeFlash['texto']) ?>
                    </span>

                </div>

            <?php endif; ?>


            <div class="contact-grid">


                <!-- INFORMACIÓN DE CONTACTO -->
                <div class="contact-card">

                    <h3>¿Quieres participar?</h3>

                    <p>
                        Somos una emisora hecha por la comunidad.
                        Si tienes una idea, propuesta, historia o música
                        para compartir, aquí puedes escribirnos.
                    </p>


                    <ul class="contact-list">

                        <li>
                            <i class="fa-solid fa-location-dot"></i>
                            Supatá, Cundinamarca
                        </li>

                        <li>
                            <i class="fa-solid fa-envelope"></i>
                            iedsalud.supata@gmail.com
                        </li>

                        <li>
                            <i class="fa-brands fa-whatsapp"></i>
                            WhatsApp
                        </li>

                        <li>
                            <i class="fa-solid fa-headphones"></i>
                            Transmisión en vivo
                        </li>

                    </ul>

                </div>


                <!-- FORMULARIO -->
                <div class="contact-card">

                    <h3>Escríbenos</h3>

                    <form
                        class="contact-form"
                        action="Contacto.php"
                        method="POST"
                    >

                        <?= campoCSRF() ?>


                        <input
                            type="text"
                            name="nombre"
                            placeholder="Nombre completo"
                            maxlength="100"
                            autocomplete="name"
                            required
                        >


                        <input
                            type="email"
                            name="correo"
                            placeholder="Correo electrónico"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >


                        <textarea
                            name="mensaje"
                            placeholder="Escribe tu mensaje..."
                            maxlength="5000"
                            rows="6"
                            required
                        ></textarea>


                        <button type="submit" class="btn">

                            <i class="fa-solid fa-paper-plane"></i>

                            Enviar mensaje

                        </button>

                    </form>

                </div>


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
                    Conéctate con Radio Stereo Academy NS en nuestras redes sociales.
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
