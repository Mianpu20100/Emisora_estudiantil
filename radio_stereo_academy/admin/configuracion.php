<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/funciones.php';

exigirAdministrador();

$mensaje = '';
$tipoMensaje = 'exito';

$campos = [
    'nombre_emisora' => [
        'titulo' => 'Nombre de la emisora',
        'tipo' => 'text',
        'max' => 150
    ],
    'eslogan' => [
        'titulo' => 'Eslogan',
        'tipo' => 'text',
        'max' => 255
    ],
    'correo' => [
        'titulo' => 'Correo electrónico',
        'tipo' => 'email',
        'max' => 150
    ],
    'ubicacion' => [
        'titulo' => 'Ubicación',
        'tipo' => 'text',
        'max' => 150
    ],
    'whatsapp' => [
        'titulo' => 'Número de WhatsApp',
        'tipo' => 'text',
        'max' => 30
    ],
    'streaming_url' => [
        'titulo' => 'Dirección del streaming',
        'tipo' => 'url',
        'max' => 500
    ]
];

$valoresPredeterminados = [
    'nombre_emisora' => 'Radio Stereo Academy NS',
    'eslogan' => 'La voz que conecta a Supatá',
    'correo' => 'iedsalud.supata@gmail.com',
    'ubicacion' => 'Supatá, Cundinamarca',
    'whatsapp' => '573228805130',
    'streaming_url' => '',
    'streaming_activo' => '0'
];

function obtenerDatosConfiguracion(PDO $conexion, array $predeterminados): array
{
    $datos = $predeterminados;

    $consulta = $conexion->query(
        "SELECT clave, valor FROM configuracion"
    );

    foreach ($consulta->fetchAll() as $fila) {
        if (array_key_exists($fila['clave'], $datos)) {
            $datos[$fila['clave']] = (string)$fila['valor'];
        }
    }

    return $datos;
}

$valores = obtenerDatosConfiguracion($conexion, $valoresPredeterminados);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        exigirTokenCSRF();

        $nuevosValores = [];

        foreach ($campos as $clave => $config) {
            $valor = trim((string)($_POST[$clave] ?? ''));

            if (mb_strlen($valor, 'UTF-8') > $config['max']) {
                throw new RuntimeException(
                    'El campo "' . $config['titulo'] . '" supera la longitud permitida.'
                );
            }

            if ($clave === 'nombre_emisora' && $valor === '') {
                throw new RuntimeException('El nombre de la emisora es obligatorio.');
            }

            if ($clave === 'correo' && $valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('El correo electrónico no es válido.');
            }

            if ($clave === 'streaming_url' && $valor !== '') {
                if (
                    !filter_var($valor, FILTER_VALIDATE_URL) ||
                    !in_array(strtolower((string)parse_url($valor, PHP_URL_SCHEME)), ['http', 'https'], true)
                ) {
                    throw new RuntimeException('La dirección del streaming debe ser una URL HTTP o HTTPS válida.');
                }
            }

            if ($clave === 'whatsapp' && $valor !== '' && !preg_match('/^[0-9+\s()-]+$/', $valor)) {
                throw new RuntimeException('El número de WhatsApp contiene caracteres no permitidos.');
            }

            $nuevosValores[$clave] = $valor;
        }

        $streamingActivo = isset($_POST['streaming_activo']) ? '1' : '0';

        if ($streamingActivo === '1' && $nuevosValores['streaming_url'] === '') {
            throw new RuntimeException(
                'Para activar el streaming primero debes registrar su dirección.'
            );
        }

        $nuevosValores['streaming_activo'] = $streamingActivo;

        $conexion->beginTransaction();

        $guardar = $conexion->prepare(
            "INSERT INTO configuracion (clave, valor)
             VALUES (:clave, :valor)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        );

        foreach ($nuevosValores as $clave => $valor) {
            $guardar->execute([
                ':clave' => $clave,
                ':valor' => $valor
            ]);
        }

        $conexion->commit();

        $_SESSION['mensaje_configuracion'] = 'La configuración se guardó correctamente.';

        header('Location: configuracion.php');
        exit;

    } catch (Throwable $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        $mensaje = $e->getMessage();
        $tipoMensaje = 'error';

        $valores = array_merge($valores, $_POST);

        $valores['streaming_activo'] =
            isset($_POST['streaming_activo']) ? '1' : '0';
    }
}

if (isset($_SESSION['mensaje_configuracion'])) {
    $mensaje = $_SESSION['mensaje_configuracion'];
    unset($_SESSION['mensaje_configuracion']);
    $valores = obtenerDatosConfiguracion($conexion, $valoresPredeterminados);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración | Radio Stereo Academy NS</title>

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
            max-width: 1050px;
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

        .panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 22px;
            box-shadow: 0 3px 12px #00000008;
        }

        .panel h2 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .descripcion {
            color: #758087;
            margin-top: 0;
            margin-bottom: 22px;
            line-height: 1.5;
        }

        .formulario {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .campo.completo {
            grid-column: 1 / -1;
        }

        label {
            font-weight: bold;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #d9e0e2;
            border-radius: 7px;
            font: inherit;
        }

        input:focus {
            outline: none;
            border-color: #123f38;
            box-shadow: 0 0 0 3px #123f3815;
        }

        .ayuda {
            color: #758087;
            font-size: 12px;
            line-height: 1.5;
        }

        .interruptor {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 17px;
            border-radius: 9px;
            background: #f4f7f6;
        }

        .interruptor input {
            width: 19px;
            height: 19px;
            margin-top: 2px;
            accent-color: #123f38;
        }

        .interruptor label {
            line-height: 1.5;
            cursor: pointer;
        }

        .boton {
            border: 0;
            padding: 13px 21px;
            border-radius: 7px;
            background: #123f38;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .boton:hover {
            background: #19574d;
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

        .nota {
            padding: 15px;
            border-left: 4px solid #123f38;
            background: #f3f6f5;
            border-radius: 5px;
            color: #56635f;
            line-height: 1.6;
            margin-top: 20px;
        }

        @media (max-width: 700px) {
            body {
                padding: 14px;
            }

            .formulario {
                grid-template-columns: 1fr;
            }

            .campo.completo {
                grid-column: auto;
            }

            .panel {
                padding: 18px;
            }
        }
    </style>
</head>

<body>
<div class="contenedor">

    <header class="encabezado">
        <div>
            <h1>Configuración general</h1>
            <p class="subtitulo">Administra la información principal de la emisora.</p>
        </div>

        <a href="index.php" class="volver">← Volver al panel</a>
    </header>

    <?php if ($mensaje !== ''): ?>
        <div class="mensaje <?= $tipoMensaje === 'error' ? 'error' : '' ?>">
            <?= e($mensaje) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <?= campoCSRF() ?>

        <section class="panel">
            <h2>Información de la emisora</h2>

            <p class="descripcion">
                Estos datos identifican a Radio Stereo Academy NS en las páginas públicas.
            </p>

            <div class="formulario">

                <?php foreach ($campos as $clave => $config): ?>
                    <?php if ($clave === 'streaming_url') continue; ?>

                    <div class="campo">
                        <label for="<?= e($clave) ?>">
                            <?= e($config['titulo']) ?>
                        </label>

                        <input
                            type="<?= e($config['tipo']) ?>"
                            id="<?= e($clave) ?>"
                            name="<?= e($clave) ?>"
                            maxlength="<?= (int)$config['max'] ?>"
                            value="<?= e((string)($valores[$clave] ?? '')) ?>">
                    </div>

                <?php endforeach; ?>

            </div>
        </section>

        <section class="panel">
            <h2>Transmisión en vivo</h2>

            <p class="descripcion">
                Configura la dirección de transmisión cuando la emisora tenga un servicio de streaming disponible.
            </p>

            <div class="formulario">

                <div class="campo completo">
                    <label for="streaming_url">Dirección del streaming</label>

                    <input
                        type="url"
                        id="streaming_url"
                        name="streaming_url"
                        maxlength="500"
                        placeholder="https://ejemplo.com/stream"
                        value="<?= e((string)$valores['streaming_url']) ?>">

                    <span class="ayuda">
                        Introduce la URL real del servicio de transmisión. No coloques una dirección inventada.
                    </span>
                </div>

                <div class="campo completo">
                    <div class="interruptor">
                        <input
                            type="checkbox"
                            id="streaming_activo"
                            name="streaming_activo"
                            value="1"
                            <?= ($valores['streaming_activo'] ?? '0') === '1' ? 'checked' : '' ?>>

                        <label for="streaming_activo">
                            Activar transmisión en vivo

                            <span class="ayuda">
                                Al activarlo, la página podrá mostrar el reproductor cuando conectemos la integración del streaming.
                            </span>
                        </label>
                    </div>
                </div>

            </div>

            <div class="nota">
                <strong>Importante:</strong> activar esta opción no crea una transmisión por sí sola.
                Necesitarás una URL real de audio compatible con el reproductor que integremos posteriormente.
            </div>
        </section>

        <button type="submit" class="boton">
            Guardar toda la configuración
        </button>

    </form>

</div>
</body>
</html>
