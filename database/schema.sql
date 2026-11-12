CREATE DATABASE IF NOT EXISTS practicas_profesionales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE practicas_profesionales;

CREATE TABLE IF NOT EXISTS usuarios (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nombre_completo VARCHAR(120) NOT NULL,
 correo VARCHAR(190) NOT NULL UNIQUE,
 contrasena_hash VARCHAR(255) NOT NULL,
 rol ENUM('administrador','estudiante') NOT NULL DEFAULT 'estudiante',
 carnet_estudiante VARCHAR(30) NULL UNIQUE,
 fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS empresas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(150) NOT NULL UNIQUE,
 sector VARCHAR(100) NOT NULL,
 sitio_web VARCHAR(255) NULL,
 fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS oportunidades (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 empresa_id BIGINT UNSIGNED NOT NULL,
 titulo VARCHAR(160) NOT NULL,
 descripcion TEXT NOT NULL,
 requisitos TEXT NOT NULL,
 ubicacion VARCHAR(120) NOT NULL,
 modalidad ENUM('Presencial','Híbrida','Remota') NOT NULL,
 vacantes INT UNSIGNED NOT NULL DEFAULT 1,
 fecha_limite DATE NOT NULL,
 estado ENUM('activa','cerrada') NOT NULL DEFAULT 'activa',
 creado_por BIGINT UNSIGNED NOT NULL,
 fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_oportunidad_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
 CONSTRAINT fk_oportunidad_autor FOREIGN KEY (creado_por) REFERENCES usuarios(id),
 INDEX idx_oportunidad_estado_fecha (estado,fecha_limite)
) ENGINE=InnoDB;

