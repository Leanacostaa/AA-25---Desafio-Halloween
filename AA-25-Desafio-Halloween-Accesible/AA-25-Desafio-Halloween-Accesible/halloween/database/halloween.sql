-- =============================================================
--  Desafío Halloween - Script de base de datos (MySQL / MariaDB)
--  Importar en phpMyAdmin o ejecutar:  mysql -u root < halloween.sql
-- =============================================================

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS halloween;
CREATE DATABASE halloween CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE halloween;

-- Usuarios: la contraseña se guarda SIEMPRE como hash BCRYPT (60 caracteres)
CREATE TABLE usuarios (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(60)  NOT NULL,
    email      VARCHAR(120) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    creado_en  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE disfraces (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    votos       INT UNSIGNED NOT NULL DEFAULT 0,
    foto        VARCHAR(255) NULL,
    eliminado   TINYINT(1)   NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Un usuario = un voto (la restricción UNIQUE lo garantiza también a nivel BD)
CREATE TABLE votos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL UNIQUE,
    disfraz_id  INT UNSIGNED NOT NULL,
    fecha       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_voto_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_voto_disfraz FOREIGN KEY (disfraz_id) REFERENCES disfraces(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO disfraces (nombre, descripcion, votos, foto) VALUES
('Vampiro Clásico',   'Capa negra con forro rojo, colmillos y mucha elegancia nocturna.', 7, 'img/vampiro.svg'),
('Bruja del Bosque',  'Sombrero puntiagudo, escoba artesanal y un caldero humeante.',      5, 'img/bruja.svg'),
('Calabaza Gigante',  'Disfraz acolchado de calabaza con sonrisa iluminada.',              9, 'img/calabaza.svg'),
('Fantasma Errante',  'Sábana blanca con cadenas que suenan al caminar.',                  3, 'img/fantasma.svg'),
('Esqueleto Bailarín','Mono negro con huesos fosforescentes que brillan en la oscuridad.', 4, 'img/esqueleto.svg');
