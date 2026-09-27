





SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

;
;
;
;




CREATE DATABASE IF NOT EXISTS `tc_control`;
USE `tc_control`;




DROP TABLE IF EXISTS `administrador`;
CREATE TABLE `administrador` (
  `id_admin` int(11) NOT NULL AUTO_INCREMENT,
  `Usuario` varchar(150) NOT NULL,
  `Contrasenna` varchar(255) NOT NULL,
  `PreguntaSecreta` varchar(255) DEFAULT NULL,
  `RespuestaSecreta` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `usuario_unico` (`Usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




INSERT INTO `administrador` (`id_admin`, `Usuario`, `Contrasenna`, `fecha_creacion`) VALUES
(1, 'admin', '$2y$10$eOw7o.pZxKmKODOkwLqUF.YavgNDhhMH97w40qr0QyJADEImRSjii', '2026-05-09 00:00:00'),
(2, 'jailymaife@gmail.com', '12345678', '2026-05-09 00:00:00'),
(3, 'carlosdrb@gmail.com', '$2y$10$hG4nPiJJze8LwYnhHlUx3OrfJtOjw.FPI4nDOOb6Jc/SIuzqojbbC', '2026-05-09 00:00:00');




DROP TABLE IF EXISTS `cliente`;
CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(150) NOT NULL,
  `apellido` varchar(150) NOT NULL,
  `telefono` varchar(25) NOT NULL,
  `fecha_inscripcion` date NOT NULL,
  `estatus` varchar(40) NOT NULL DEFAULT 'Activo',
  `Solvencia` varchar(255) NOT NULL DEFAULT 'Solvente',
  `Plan` varchar(255) NOT NULL DEFAULT 'Mensual',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




INSERT INTO `cliente` (`id_cliente`, `Nombre`, `apellido`, `telefono`, `fecha_inscripcion`, `estatus`, `Solvencia`, `Plan`, `fecha_registro`) VALUES
(1, 'Jaily', 'Unda', '04120530221', '2026-05-06', 'Activo', 'Solvente', 'Mensual', '2026-05-09 00:00:00'),
(3, 'dsavaer', 'reabrb', '234234', '2026-05-19', 'activo', 'solvente', 'basico', '2026-05-09 00:00:00');




DROP TABLE IF EXISTS `pagos`;
CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `Monto` decimal(10,2) NOT NULL,
  `Fecha` date NOT NULL,
  `MetodoPago` varchar(50) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pago`),
  KEY `idx_id_cliente` (`id_cliente`),
  CONSTRAINT `fk_pagos_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_empresa` varchar(255) NOT NULL DEFAULT 'TC-Control',
  `tarifa_base` decimal(10,2) NOT NULL DEFAULT 30.00,
  `moneda` varchar(50) NOT NULL DEFAULT 'USD',
  `vencimiento_dias` int(11) NOT NULL DEFAULT 7,
  `ingresos_periodo` varchar(50) NOT NULL DEFAULT 'semanal',
  `ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




INSERT INTO `configuracion` (`id`, `nombre_empresa`, `tarifa_base`, `moneda`, `vencimiento_dias`, `ingresos_periodo`, `ultima_actualizacion`) VALUES
(1, 'TC-Control', 30.00, 'USD', 7, 'semanal', '2026-05-09 00:00:00');


CREATE TABLE IF NOT EXISTS `planes` (
  `id_plan` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(120) NOT NULL,
  `precio` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `dias` INT(11) NOT NULL DEFAULT 30,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `planes` (`id_plan`, `nombre`, `precio`, `dias`) VALUES
(1, 'Básico', 10.00, 7),
(2, 'Mensual', 30.00, 30),
(3, 'Anual', 300.00, 365);




COMMIT;

;
;
;
