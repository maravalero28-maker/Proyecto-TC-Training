-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-10-2026 a las 00:46:39
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
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
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administrador`
--

INSERT INTO `administrador` (`id_admin`, `Usuario`, `Contrasenna`, `PreguntaSecreta`, `RespuestaSecreta`, `fecha_creacion`) VALUES
(1, 'admin', '$2y$10$eOw7o.pZxKmKODOkwLqUF.YavgNDhhMH97w40qr0QyJADEImRSjii', NULL, NULL, '2026-05-09 00:00:00'),
(2, 'jailymaife@gmail.com', '12345678', NULL, NULL, '2026-05-09 00:00:00'),
(3, 'carlosdrb@gmail.com', '$2y$10$hG4nPiJJze8LwYnhHlUx3OrfJtOjw.FPI4nDOOb6Jc/SIuzqojbbC', NULL, NULL, '2026-05-09 00:00:00'),
(4, 'mara', '$2y$10$rfVGN4Me03zg0riR8Cm31.JRa2SYiDlzfC.AOmvSmNk4AT0G3aXNa', '¿Cuál es el nombre de tu mascota?', '$2y$10$8xrCsZchji0gtHBCqcZymO1vgk7B/Dv/mmZJTfLYU.eJxaHHVtrYy', '2026-10-13 14:40:33');

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
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id_cliente`, `Nombre`, `apellido`, `telefono`, `fecha_inscripcion`, `estatus`, `Solvencia`, `Plan`, `fecha_registro`) VALUES
(1, 'Ismael', 'Gil', '04245717884', '2026-09-01', 'activo', 'Solvente', '1', '2026-10-13 02:53:55'),
(2, 'Adrian', 'Suarez', '04553454451', '2026-10-13', 'activo', 'Solvente', '5', '2026-10-13 14:45:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `nombre_empresa` varchar(255) NOT NULL DEFAULT 'TC-Control',
  `tarifa_base` decimal(10,2) NOT NULL DEFAULT 30.00,
  `moneda` varchar(50) NOT NULL DEFAULT 'USD',
  `vencimiento_dias` int(11) NOT NULL DEFAULT 7,
  `ingresos_periodo` varchar(50) NOT NULL DEFAULT 'semanal',
  `ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `nombre_empresa`, `tarifa_base`, `moneda`, `vencimiento_dias`, `ingresos_periodo`, `ultima_actualizacion`) VALUES
(1, 'Training Club, CA', 30.00, 'USD', 35, 'semanal', '2026-10-13 14:51:17');

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
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `Monto` decimal(10,2) NOT NULL,
  `Fecha` date NOT NULL,
  `MetodoPago` varchar(50) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_cliente`, `Monto`, `Fecha`, `MetodoPago`, `fecha_registro`) VALUES
(1, 1, 500.00, '2026-10-13', 'Transferencia', '2026-10-13 02:54:37'),
(2, 2, 120.00, '2026-10-13', 'Efectivo', '2026-10-13 14:47:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago_nomina`
--

CREATE TABLE `pago_nomina` (
  `id_nomina` int(11) NOT NULL,
  `id_entrenador` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `concepto` varchar(255) DEFAULT 'Pago de Nómina / Honorarios',
  `metodo_pago` varchar(50) DEFAULT 'Efectivo',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pago_nomina`
--

INSERT INTO `pago_nomina` (`id_nomina`, `id_entrenador`, `id_admin`, `monto`, `fecha_pago`, `concepto`, `metodo_pago`, `fecha_registro`) VALUES
(1, 28220253, 1, 25.00, '2026-10-13', 'Pago de Nómina / Honorarios', 'Pago Móvil', '2026-10-12 22:29:11'),
(2, 28220253, 4, 120.00, '2026-10-13', 'Pago de Nómina / Honorarios', 'Efectivo', '2026-10-13 14:57:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago_servicio`
--

CREATE TABLE `pago_servicio` (
  `id_pago_servicio` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` varchar(80) NOT NULL,
  `metodo_pago` varchar(80) NOT NULL,
  `fecha` date NOT NULL DEFAULT curdate(),
  `id_admin` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `pago_servicio`
--

INSERT INTO `pago_servicio` (`id_pago_servicio`, `monto`, `concepto`, `metodo_pago`, `fecha`, `id_admin`) VALUES
(1, 50.00, 'Contador', 'Pago Móvil', '2026-10-13', 1),
(2, 100.00, 'Contador', 'Efectivo', '2026-10-13', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes`
--

CREATE TABLE `planes` (
  `id_plan` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dias` int(11) NOT NULL DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `planes`
--

INSERT INTO `planes` (`id_plan`, `nombre`, `precio`, `dias`, `created_at`) VALUES
(1, 'Semanal', 10.00, 7, '2026-09-22 20:29:41'),
(2, 'Mensual', 25.00, 30, '2026-09-22 20:29:41'),
(3, 'Anual', 300.00, 365, '2026-09-22 20:29:41'),
(4, 'Promocion pareja o grupo', 20.00, 30, '2026-09-22 20:33:47'),
(5, 'premium', 120.00, 30, '2026-10-13 14:44:07');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administrador`
--
ALTER TABLE `administrador`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `usuario_unico` (`Usuario`);

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
-- Indices de la tabla `entrenador`
--
ALTER TABLE `entrenador`
  ADD PRIMARY KEY (`cedula`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `idx_id_cliente` (`id_cliente`);

--
-- Indices de la tabla `pago_nomina`
--
ALTER TABLE `pago_nomina`
  ADD PRIMARY KEY (`id_nomina`),
  ADD KEY `idx_nomina_entrenador` (`id_entrenador`),
  ADD KEY `idx_nomina_admin` (`id_admin`);

--
-- Indices de la tabla `pago_servicio`
--
ALTER TABLE `pago_servicio`
  ADD PRIMARY KEY (`id_pago_servicio`),
  ADD KEY `fk_id_admin` (`id_admin`);

--
-- Indices de la tabla `planes`
--
ALTER TABLE `planes`
  ADD PRIMARY KEY (`id_plan`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administrador`
--
ALTER TABLE `administrador`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pago_nomina`
--
ALTER TABLE `pago_nomina`
  MODIFY `id_nomina` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pago_servicio`
--
ALTER TABLE `pago_servicio`
  MODIFY `id_pago_servicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `id_plan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pago_servicio`
--
ALTER TABLE `pago_servicio`
  ADD CONSTRAINT `fk_id_admin` FOREIGN KEY (`id_admin`) REFERENCES `administrador` (`id_admin`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
