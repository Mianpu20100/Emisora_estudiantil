
-- RADIO STEREO ACADEMY NS
-- Base de datos inicial

CREATE DATABASE IF NOT EXISTS radio_stereo_academy
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE radio_stereo_academy;

-- 1. ÚNICA CUENTA ADMINISTRADORA
CREATE TABLE IF NOT EXISTS administrador (
    id TINYINT UNSIGNED NOT NULL DEFAULT 1,
    usuario VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_administrador_usuario (usuario),
    CONSTRAINT chk_unico_administrador CHECK (id = 1)
) ENGINE=InnoDB;

-- 2. PROGRAMACIÓN
CREATE TABLE IF NOT EXISTS programas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(100) NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT,
    dias VARCHAR(180) NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    imagen VARCHAR(255) DEFAULT NULL,
    orden INT NOT NULL DEFAULT 0,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. PODCASTS
CREATE TABLE IF NOT EXISTS podcasts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT,
    portada VARCHAR(255) DEFAULT NULL,
    archivo_audio VARCHAR(255) DEFAULT NULL,
    duracion VARCHAR(30) DEFAULT NULL,
    fecha_publicacion DATE DEFAULT NULL,
    orden INT NOT NULL DEFAULT 0,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. NOTICIAS
CREATE TABLE IF NOT EXISTS noticias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    categoria VARCHAR(100) DEFAULT 'Institucional',
    resumen TEXT,
    contenido LONGTEXT,
    imagen VARCHAR(255) DEFAULT NULL,
    estado ENUM('borrador','publicada') NOT NULL DEFAULT 'borrador',
    fecha_publicacion DATETIME DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_noticia_slug (slug),
    INDEX idx_noticias_estado_fecha (estado, fecha_publicacion)
) ENGINE=InnoDB;

-- 5. GALERÍA
CREATE TABLE IF NOT EXISTS galeria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255) NOT NULL,
    categoria VARCHAR(100) DEFAULT NULL,
    orden INT NOT NULL DEFAULT 0,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. INFORMACIÓN DE LAS PÁGINAS
CREATE TABLE IF NOT EXISTS contenido_pagina (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL,
    titulo VARCHAR(200) DEFAULT NULL,
    contenido LONGTEXT,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_contenido_clave (clave)
) ENGINE=InnoDB;

-- 7. CONFIGURACIÓN GENERAL
CREATE TABLE IF NOT EXISTS configuracion (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL,
    valor TEXT,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_configuracion_clave (clave)
) ENGINE=InnoDB;

-- 8. MENSAJES DE CONTACTO (OPCIONAL)
CREATE TABLE IF NOT EXISTS mensajes_contacto (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    correo VARCHAR(180) NOT NULL,
    mensaje TEXT NOT NULL,
    estado ENUM('nuevo','leido','archivado') NOT NULL DEFAULT 'nuevo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- CONFIGURACIÓN INICIAL
INSERT IGNORE INTO configuracion (clave, valor) VALUES
('nombre_emisora', 'Radio Stereo Academy NS'),
('eslogan', 'La voz que conecta a Supatá'),
('correo', 'iedsalud.supata@gmail.com'),
('ubicacion', 'Supatá, Cundinamarca'),
('whatsapp', '573228805130'),
('streaming_activo', '0'),
('streaming_url', ''),
('zona_horaria', 'America/Bogota');

-- CONTENIDO INSTITUCIONAL INICIAL
INSERT IGNORE INTO contenido_pagina (clave, titulo, contenido) VALUES
('historia', 'Nuestra historia',
 'Radio Stereo Academy NS nace como una propuesta educativa y creativa para conectar a la comunidad de la IED Nuestra Señora de la Salud con la radio, la comunicación, la expresión y la creatividad.'),
('mision', 'Misión',
 'Fortalecer la comunicación institucional mediante una plataforma web que permita difundir contenidos educativos, culturales e informativos y promover la participación de la comunidad educativa.'),
('vision', 'Visión',
 'Ser una plataforma digital reconocida dentro de la institución por innovar la comunicación escolar e impulsar la creatividad, el aprendizaje y la participación de la comunidad educativa.');
