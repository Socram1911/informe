-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-09-2025 a las 22:03:55
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `informe2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id`, `nombre`) VALUES
(1, 'Administración'),
(2, 'Informática'),
(3, 'Recursos Humanos'),
(4, 'Operaciones'),
(5, 'Asistencia al contribuyente'),
(6, 'Recaudación'),
(7, 'Gerencia'),
(8, 'Tramitaciones'),
(9, 'Apoyo jurídico'),
(10, 'A.C.A.B.A'),
(11, 'Control anterior');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_seccion`
--

CREATE TABLE `historial_seccion` (
  `id` int(11) NOT NULL,
  `seccion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` enum('guardar','completar','solicitar_revision','aprobar','rechazar') NOT NULL,
  `contenido_copia` mediumtext DEFAULT NULL,
  `comentario` varchar(500) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `informes`
--

CREATE TABLE `informes` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `periodo` enum('enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre') NOT NULL,
  `estado` enum('borrador','en_progreso','completado') NOT NULL DEFAULT 'borrador',
  `creado_por` int(11) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secciones_informe`
--

CREATE TABLE `secciones_informe` (
  `id` int(11) NOT NULL,
  `informe_id` int(11) NOT NULL,
  `departamento_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `contenido` mediumtext DEFAULT NULL,
  `capitulo` enum('1','2','3','4','5') NOT NULL,
  `estado` enum('borrador','completado','en_revision','aprobado','rechazado') NOT NULL DEFAULT 'borrador',
  `asignado_a` int(11) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `correo` varchar(190) NOT NULL,
  `clave_hash` varchar(255) NOT NULL,
  `rol` enum('editor','supervisor','admin') NOT NULL DEFAULT 'editor',
  `departamento_id` int(11) DEFAULT NULL,
  `ruta_firma` varchar(255) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historial_seccion`
--
ALTER TABLE `historial_seccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_hs_seccion` (`seccion_id`),
  ADD KEY `fk_hs_usuario` (`usuario_id`);

--
-- Indices de la tabla `informes`
--
ALTER TABLE `informes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_informes_usuario` (`creado_por`);

--
-- Indices de la tabla `secciones_informe`
--
ALTER TABLE `secciones_informe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_si_informe` (`informe_id`),
  ADD KEY `fk_si_departamento` (`departamento_id`),
  ADD KEY `fk_si_asignado` (`asignado_a`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `fk_usuarios_departamento` (`departamento_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `historial_seccion`
--
ALTER TABLE `historial_seccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `informes`
--
ALTER TABLE `informes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `secciones_informe`
--
ALTER TABLE `secciones_informe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_seccion`
--
ALTER TABLE `historial_seccion`
  ADD CONSTRAINT `fk_hs_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones_informe` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `informes`
--
ALTER TABLE `informes`
  ADD CONSTRAINT `fk_informes_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `secciones_informe`
--
ALTER TABLE `secciones_informe`
  ADD CONSTRAINT `fk_si_asignado` FOREIGN KEY (`asignado_a`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_si_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_si_informe` FOREIGN KEY (`informe_id`) REFERENCES `informes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
