-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-11-2026 a las 17:14:30
-- Versión del servidor: 10.1.38-MariaDB
-- Versión de PHP: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tc_control`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administrador`
--

CREATE TABLE `administrador` (
  `id_admin` int(11) NOT NULL,
  `Usuario` varchar(150) NOT NULL,
  `Contrasenna` varchar(255) NOT NULL,
  `PreguntaSecreta` varchar(255) DEFAULT NULL,
  `RespuestaSecreta` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_rol` int(11) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `administrador`
--

INSERT INTO `administrador` (`id_admin`, `Usuario`, `Contrasenna`, `PreguntaSecreta`, `RespuestaSecreta`, `fecha_creacion`, `id_rol`) VALUES
(1, 'admin', '$2y$10$eOw7o.pZxKmKODOkwLqUF.YavgNDhhMH97w40qr0QyJADEImRSjii', NULL, NULL, '2026-05-09 00:00:00', 1),
(2, 'jailymaife@gmail.com', '12345678', NULL, NULL, '2026-05-09 00:00:00', 1),
(3, 'carlosdrb@gmail.com', '$2y$10$hG4nPiJJze8LwYnhHlUx3OrfJtOjw.FPI4nDOOb6Jc/SIuzqojbbC', NULL, NULL, '2026-05-09 00:00:00', 1),
(4, 'mara', '$2y$10$rfVGN4Me03zg0riR8Cm31.JRa2SYiDlzfC.AOmvSmNk4AT0G3aXNa', '¿Cuál es el nombre de tu mascota?', '$2y$10$8xrCsZchji0gtHBCqcZymO1vgk7B/Dv/mmZJTfLYU.eJxaHHVtrYy', '2026-10-13 14:40:33', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `banco`
--

CREATE TABLE `banco` (
  `id_banco` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `banco`
--

INSERT INTO `banco` (`id_banco`, `nombre`) VALUES
(1, 'Mercantil'),
(2, 'paypal'),
(3, 'Binance'),
(4, 'Efectivo/Caja');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL,
  `Nombre` varchar(150) NOT NULL,
  `apellido` varchar(150) NOT NULL,
  `telefono` varchar(25) NOT NULL,
  `fecha_inscripcion` date NOT NULL,
  `estatus` varchar(40) NOT NULL DEFAULT 'Activo',
  `Solvencia` varchar(255) NOT NULL DEFAULT 'Solvente',
  `Plan` varchar(255) NOT NULL DEFAULT 'Mensual',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id_cliente`, `Nombre`, `apellido`, `telefono`, `fecha_inscripcion`, `estatus`, `Solvencia`, `Plan`, `fecha_registro`) VALUES
(1, 'Ismael', 'Gil', '04245717884', '2026-09-01', 'activo', 'Solvente', '2', '2026-10-13 02:53:55'),
(2, 'Adrian', 'Suarez', '04553454451', '2026-10-13', 'activo', 'Solvente', '2', '2026-10-13 14:45:37'),
(3, 'Alianna', 'Gutierrez', '098765432', '2026-11-07', 'activo', 'Solvente', '3', '2026-11-07 20:07:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `nombre_empresa` varchar(255) NOT NULL DEFAULT 'TC-Control',
  `tarifa_base` decimal(10,2) NOT NULL DEFAULT '30.00',
  `moneda` varchar(50) NOT NULL DEFAULT 'USD',
  `vencimiento_dias` int(11) NOT NULL DEFAULT '7',
  `ingresos_periodo` varchar(50) NOT NULL DEFAULT 'semanal',
  `ultima_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `nombre_empresa`, `tarifa_base`, `moneda`, `vencimiento_dias`, `ingresos_periodo`, `ultima_actualizacion`) VALUES
(1, 'Training Club, CA', '30.00', 'USD', 7, 'semanal', '2026-11-08 04:05:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuenta`
--

CREATE TABLE `cuenta` (
  `id_cuenta` int(11) NOT NULL,
  `id_banco` int(11) NOT NULL,
  `num_cuenta` varchar(100) NOT NULL,
  `tipo_moneda` varchar(50) NOT NULL,
  `saldo` decimal(10,2) DEFAULT '0.00',
  `saldo_inicial` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo_inicial_usd` decimal(12,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `cuenta`
--

INSERT INTO `cuenta` (`id_cuenta`, `id_banco`, `num_cuenta`, `tipo_moneda`, `saldo`, `saldo_inicial`, `saldo_inicial_usd`) VALUES
(1, 1, '0605', 'BS', '10.00', '0.00', '0.00'),
(3, 2, '65765765857', 'USD', '0.00', '0.00', '0.00'),
(4, 3, '8696969879', 'USD', '0.00', '0.00', '0.00'),
(5, 4, 'Efectivo (USD)', 'USD', '0.00', '0.00', '0.00'),
(6, 4, 'Efectivo (BS)', 'BS', '0.00', '0.00', '0.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entrenador`
--

CREATE TABLE `entrenador` (
  `cedula` varchar(30) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `apellido` varchar(150) NOT NULL,
  `telefono` varchar(25) NOT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'activo',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `entrenador`
--

INSERT INTO `entrenador` (`cedula`, `nombre`, `apellido`, `telefono`, `estatus`, `fecha_registro`) VALUES
('28220253', 'Carlos Daniel', 'Rodriguez', '04265517487', 'activo', '2026-11-07 19:44:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodo_pago`
--

CREATE TABLE `metodo_pago` (
  `id_metodo` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `metodo_pago`
--

INSERT INTO `metodo_pago` (`id_metodo`, `nombre`) VALUES
(1, 'efectivo'),
(2, 'transferencia'),
(3, 'pago movil'),
(4, 'Zelle'),
(5, 'Binance'),
(6, 'paypal');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_cuenta` int(11) NOT NULL,
  `Monto` decimal(10,2) NOT NULL,
  `Fecha` date NOT NULL,
  `id_metodo` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `moneda` varchar(3) DEFAULT NULL,
  `tasa` decimal(18,4) DEFAULT NULL,
  `monto_usd` decimal(12,2) DEFAULT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_cliente`, `id_cuenta`, `Monto`, `Fecha`, `id_metodo`, `fecha_registro`, `moneda`, `tasa`, `monto_usd`, `anulado`) VALUES
(1, 3, 3, '300.00', '2026-11-08', 3, '2026-11-08 15:23:19', 'USD', NULL, '300.00', 0),
(2, 1, 1, '22500.00', '2026-11-08', 3, '2026-11-08 15:24:18', 'BS', '900.0000', '25.00', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago_nomina`
--

CREATE TABLE `pago_nomina` (
  `id_nomina` int(11) NOT NULL,
  `id_entrenador` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL,
  `id_cuenta` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `concepto` varchar(255) DEFAULT 'Pago de Nómina / Honorarios',
  `id_metodo` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `moneda` varchar(3) DEFAULT NULL,
  `tasa` decimal(18,4) DEFAULT NULL,
  `monto_usd` decimal(12,2) DEFAULT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `pago_nomina`
--

INSERT INTO `pago_nomina` (`id_nomina`, `id_entrenador`, `id_admin`, `id_cuenta`, `monto`, `fecha_pago`, `concepto`, `id_metodo`, `fecha_registro`, `moneda`, `tasa`, `monto_usd`, `anulado`) VALUES
(1, 28220253, 1, 3, '100.00', '2026-11-08', 'Pago de Nómina / Honorarios', 3, '2026-11-08 15:35:03', 'USD', NULL, '100.00', 0),
(2, 28220253, 1, 1, '5000.00', '2026-11-08', 'Pago de Nómina / Honorarios', 3, '2026-11-08 15:35:28', 'BS', '900.0000', '5.56', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago_servicio`
--

CREATE TABLE `pago_servicio` (
  `id_pago_servicio` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` varchar(80) NOT NULL,
  `id_cuenta` int(11) DEFAULT NULL,
  `id_metodo` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `moneda` varchar(3) DEFAULT NULL,
  `tasa` decimal(18,4) DEFAULT NULL,
  `monto_usd` decimal(12,2) DEFAULT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `pago_servicio`
--

INSERT INTO `pago_servicio` (`id_pago_servicio`, `monto`, `concepto`, `id_cuenta`, `id_metodo`, `fecha`, `id_admin`, `moneda`, `tasa`, `monto_usd`, `anulado`) VALUES
(1, '5000.00', 'electricidad', 1, 3, '2026-11-08', 1, 'BS', '900.0000', '5.56', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes`
--

CREATE TABLE `planes` (
  `id_plan` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `dias` int(11) NOT NULL DEFAULT '30',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `planes`
--

INSERT INTO `planes` (`id_plan`, `nombre`, `precio`, `dias`, `created_at`) VALUES
(1, 'Semanal', '10.00', 7, '2026-09-22 20:29:41'),
(2, 'Mensual', '25.00', 30, '2026-09-22 20:29:41'),
(3, 'Anual', '300.00', 365, '2026-09-22 20:29:41'),
(4, 'Promocion pareja o grupo', '20.00', 30, '2026-09-22 20:33:47'),
(5, 'premium', '120.00', 30, '2026-10-13 14:44:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_usuario`
--

CREATE TABLE `rol_usuario` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `rol_usuario`
--

INSERT INTO `rol_usuario` (`id_rol`, `nombre`, `descripcion`) VALUES
(1, 'Administrador', 'Control total del sistema'),
(2, 'Operador', 'Gestion de clientes y pagos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasa_cambio`
--

CREATE TABLE `tasa_cambio` (
  `id_tasa` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `tasa` decimal(18,4) NOT NULL,
  `id_admin` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `tasa_cambio`
--

INSERT INTO `tasa_cambio` (`id_tasa`, `fecha`, `tasa`, `id_admin`) VALUES
(1, '2026-11-07', '896.1200', 1),
(2, '2026-11-08', '900.0000', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administrador`
--
ALTER TABLE `administrador`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `usuario_unico` (`Usuario`),
  ADD KEY `fk_rol_usuario` (`id_rol`);

--
-- Indices de la tabla `banco`
--
ALTER TABLE `banco`
  ADD PRIMARY KEY (`id_banco`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id_cliente`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cuenta`
--
ALTER TABLE `cuenta`
  ADD PRIMARY KEY (`id_cuenta`),
  ADD KEY `fk_cuenta_banco` (`id_banco`);

--
-- Indices de la tabla `entrenador`
--
ALTER TABLE `entrenador`
  ADD PRIMARY KEY (`cedula`);

--
-- Indices de la tabla `metodo_pago`
--
ALTER TABLE `metodo_pago`
  ADD PRIMARY KEY (`id_metodo`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `idx_id_cliente` (`id_cliente`),
  ADD KEY `fk_pago_cuenta` (`id_cuenta`),
  ADD KEY `fk_metodo_pago_pagos` (`id_metodo`);

--
-- Indices de la tabla `pago_nomina`
--
ALTER TABLE `pago_nomina`
  ADD PRIMARY KEY (`id_nomina`),
  ADD KEY `idx_nomina_entrenador` (`id_entrenador`),
  ADD KEY `idx_nomina_admin` (`id_admin`),
  ADD KEY `fk_metodo_pago` (`id_metodo`),
  ADD KEY `fk_nomina_cuenta` (`id_cuenta`);

--
-- Indices de la tabla `pago_servicio`
--
ALTER TABLE `pago_servicio`
  ADD PRIMARY KEY (`id_pago_servicio`),
  ADD KEY `fk_id_admin` (`id_admin`),
  ADD KEY `fk_metodo_pago_servicio` (`id_metodo`),
  ADD KEY `fk_servicio_cuenta` (`id_cuenta`);

--
-- Indices de la tabla `planes`
--
ALTER TABLE `planes`
  ADD PRIMARY KEY (`id_plan`);

--
-- Indices de la tabla `rol_usuario`
--
ALTER TABLE `rol_usuario`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `tasa_cambio`
--
ALTER TABLE `tasa_cambio`
  ADD PRIMARY KEY (`id_tasa`),
  ADD UNIQUE KEY `uq_tasa_fecha` (`fecha`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administrador`
--
ALTER TABLE `administrador`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `banco`
--
ALTER TABLE `banco`
  MODIFY `id_banco` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cuenta`
--
ALTER TABLE `cuenta`
  MODIFY `id_cuenta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `metodo_pago`
--
ALTER TABLE `metodo_pago`
  MODIFY `id_metodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pago_nomina`
--
ALTER TABLE `pago_nomina`
  MODIFY `id_nomina` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pago_servicio`
--
ALTER TABLE `pago_servicio`
  MODIFY `id_pago_servicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tasa_cambio`
--
ALTER TABLE `tasa_cambio`
  MODIFY `id_tasa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `pago_servicio`
--
ALTER TABLE `pago_servicio`
  ADD CONSTRAINT `fk_id_admin` FOREIGN KEY (`id_admin`) REFERENCES `administrador` (`id_admin`),
  ADD CONSTRAINT `fk_metodo_pago_servicio` FOREIGN KEY (`id_metodo`) REFERENCES `metodo_pago` (`id_metodo`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_servicio_cuenta` FOREIGN KEY (`id_cuenta`) REFERENCES `cuenta` (`id_cuenta`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
