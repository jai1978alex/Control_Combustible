-- ============================================================================
-- EMEX | CONTROL DE COMBUSTIBLE
-- BASE DE DATOS PROFESIONAL - INSTALACION LIMPIA
-- ============================================================================
-- Base de datos: base_dato_formulario
-- Motor: MySQL / MariaDB - InnoDB
-- Codificacion: utf8mb4
-- Zona horaria de la aplicacion: America/Santiago (se configura en PHP)
--
-- CONTENIDO
--  01. Creacion y configuracion de la base de datos
--  02. Usuarios, roles y control de acceso
--  03. Registro de operador cargador
--  04. Registro de operador
--  05. Recuperacion segura de contrasenas
--  06. Auditoria del sistema
--  07. Control de versiones de la base de datos
--  08. Vistas para reportes y estadisticas
--  09. Indices, integridad y rendimiento
--
-- IMPORTANTE
-- * Este archivo esta preparado para una INSTALACION NUEVA.
-- * Antes de reemplazar una base de datos existente, exporte un respaldo.
-- * No contiene usuarios ni contrasenas predeterminadas.
-- * El primer administrador se crea con backend/crear_primer_admin.php.
-- * No almacene contrasenas en texto plano.
-- ============================================================================
-- 01. CREACION Y CONFIGURACION DE LA BASE DE DATOS
-- ============================================================================
CREATE DATABASE IF NOT EXISTS `base_dato_formulario`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `base_dato_formulario`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 02. USUARIOS, ROLES Y CONTROL DE ACCESO
-- ============================================================================
-- Tabla central de autenticacion. El campo password almacena hashes creados
-- por password_hash() de PHP. Los roles existentes se conservan por
-- compatibilidad con el codigo actual: admin y operador.
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `rut` VARCHAR(12) DEFAULT NULL COMMENT 'RUT maestro asociado a la cuenta para validar identidad mediante QR',
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('admin','operador') NOT NULL DEFAULT 'operador',
  `estado` ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  `debe_cambiar_password` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_username` (`username`),
  UNIQUE KEY `uq_usuarios_email` (`email`),
  UNIQUE KEY `uq_usuarios_rut` (`rut`),
  KEY `idx_usuarios_estado_rol` (`estado`,`rol`),
  KEY `idx_usuarios_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cuentas de acceso, roles y estado de usuarios';

-- ============================================================================
-- 03. REGISTRO DE OPERADOR CARGADOR
-- ============================================================================
-- Registra cargas efectuadas por operadores cargadores.
-- eliminado_at / eliminado_por implementan eliminacion logica y trazable.
CREATE TABLE IF NOT EXISTS `operador_cargador` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombres` VARCHAR(100) NOT NULL,
  `apellido_paterno` VARCHAR(100) NOT NULL,
  `apellido_materno` VARCHAR(100) NOT NULL,
  `rut` VARCHAR(12) NOT NULL,
  `turno` CHAR(1) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL COMMENT 'Codigo de maquinaria',
  `ubicacion` VARCHAR(100) NOT NULL COMMENT 'Ubicacion o coordenadas GPS recibidas por la aplicacion',
  `patente` VARCHAR(10) NOT NULL,
  `horometro` DECIMAL(10,2) NOT NULL,
  `litros` DECIMAL(10,2) NOT NULL,
  `remanente` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `observacion` TEXT DEFAULT NULL,
  `eliminado_at` DATETIME DEFAULT NULL,
  `eliminado_por` INT UNSIGNED DEFAULT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cargador_rut_fecha` (`rut`,`fecha_registro`),
  KEY `idx_cargador_vigente_fecha` (`eliminado_at`,`fecha_registro`),
  KEY `idx_cargador_vigente_litros` (`eliminado_at`,`litros`),
  KEY `idx_cargador_usuario_fecha` (`usuario_id`,`fecha_registro`),
  KEY `idx_cargador_patente_fecha` (`patente`,`fecha_registro`),
  CONSTRAINT `fk_cargador_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cargador_eliminado_por`
    FOREIGN KEY (`eliminado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_cargador_litros` CHECK (`litros` > 0),
  CONSTRAINT `chk_cargador_horometro` CHECK (`horometro` >= 0),
  CONSTRAINT `chk_cargador_turno` CHECK (`turno` IN ('A','B','C','D'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cargas de combustible registradas por operadores cargadores';

-- ============================================================================
-- 04. REGISTRO DE OPERADOR
-- ============================================================================
-- Registra consumo/carga asociado a operador, equipo y maquinaria.
-- horometro y kilometro aceptan NULL cuando el tipo de equipo no utiliza
-- alguno de esos indicadores.
CREATE TABLE IF NOT EXISTS `operador` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombres` VARCHAR(100) NOT NULL,
  `apellido_paterno` VARCHAR(100) NOT NULL,
  `apellido_materno` VARCHAR(100) NOT NULL,
  `rut` VARCHAR(12) NOT NULL,
  `turno` CHAR(1) NOT NULL,
  `ubicacion` VARCHAR(100) NOT NULL,
  `codigo_maquinaria` VARCHAR(50) NOT NULL,
  `patente` VARCHAR(10) NOT NULL,
  `equipo` VARCHAR(100) NOT NULL,
  `horometro` DECIMAL(10,2) DEFAULT NULL,
  `kilometro` DECIMAL(10,2) DEFAULT NULL,
  `litros` DECIMAL(10,2) NOT NULL,
  `consumo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `observacion` TEXT DEFAULT NULL,
  `eliminado_at` DATETIME DEFAULT NULL,
  `eliminado_por` INT UNSIGNED DEFAULT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_operador_rut_fecha` (`rut`,`fecha_registro`),
  KEY `idx_operador_vigente_fecha` (`eliminado_at`,`fecha_registro`),
  KEY `idx_operador_vigente_litros` (`eliminado_at`,`litros`),
  KEY `idx_operador_usuario_fecha` (`usuario_id`,`fecha_registro`),
  KEY `idx_operador_patente_fecha` (`patente`,`fecha_registro`),
  KEY `idx_operador_maquinaria_fecha` (`codigo_maquinaria`,`fecha_registro`),
  CONSTRAINT `fk_operador_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_operador_eliminado_por`
    FOREIGN KEY (`eliminado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_operador_litros` CHECK (`litros` > 0),
  CONSTRAINT `chk_operador_lecturas`
    CHECK ((`horometro` IS NULL OR `horometro` >= 0)
       AND (`kilometro` IS NULL OR `kilometro` >= 0)),
  CONSTRAINT `chk_operador_turno` CHECK (`turno` IN ('A','B','C','D'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cargas y consumos asociados a operadores, equipos y maquinarias';

-- ============================================================================
-- 05. RECUPERACION SEGURA DE CONTRASENAS
-- ============================================================================
-- token_hash debe contener el hash del token, nunca el token original.
-- Los tokens deben tener vencimiento y marcarse como usados al consumirse.
CREATE TABLE IF NOT EXISTS `recuperacion_password` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `usado` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_solicitud` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expira_en` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reset_token_hash` (`token_hash`),
  KEY `idx_reset_usuario` (`usuario_id`),
  KEY `idx_reset_estado_expira` (`usado`,`expira_en`),
  CONSTRAINT `fk_reset_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokens temporales para recuperacion de acceso';

-- ============================================================================
-- 06. AUDITORIA DEL SISTEMA
-- ============================================================================
-- Registro historico de acciones administrativas y operacionales.
-- Se conserva la auditoria aunque una cuenta sea eliminada.
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `accion` VARCHAR(80) NOT NULL,
  `tabla_afectada` VARCHAR(80) DEFAULT NULL,
  `registro_id` INT UNSIGNED DEFAULT NULL,
  `detalle` TEXT DEFAULT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_auditoria_fecha` (`fecha_registro`),
  KEY `idx_auditoria_accion` (`accion`),
  KEY `idx_auditoria_usuario_fecha` (`usuario_id`,`fecha_registro`),
  KEY `idx_auditoria_tabla_registro` (`tabla_afectada`,`registro_id`),
  CONSTRAINT `fk_auditoria_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Bitacora de seguridad y trazabilidad del sistema';

-- ============================================================================
-- 07. CONTROL DE VERSIONES Y MIGRACIONES
-- ============================================================================
-- Mantiene un registro de las mejoras estructurales aplicadas.
CREATE TABLE IF NOT EXISTS `mejoras_base_datos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(60) NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `aplicado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mejora_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial de versiones y mejoras del esquema de base de datos';

INSERT IGNORE INTO `mejoras_base_datos` (`codigo`,`descripcion`) VALUES
('EMEX_DB_2026_03','Esquema profesional consolidado para instalacion nueva');

-- ============================================================================
-- 08. VISTAS PARA REPORTES Y ESTADISTICAS
-- ============================================================================
-- Las vistas son de solo lectura y excluyen registros eliminados logicamente.

-- 08.1. Historial unificado de cargas vigentes.
CREATE OR REPLACE VIEW `v_cargas_vigentes` AS
SELECT
  'cargador' AS `origen`,
  oc.`id`, oc.`rut`, oc.`nombres`, oc.`apellido_paterno`, oc.`apellido_materno`,
  oc.`turno`, oc.`ubicacion`, oc.`patente`,
  oc.`codigo` AS `codigo_maquinaria`,
  NULL AS `equipo`,
  oc.`horometro`, NULL AS `kilometro`,
  oc.`litros`, oc.`usuario_id`, oc.`fecha_registro`
FROM `operador_cargador` oc
WHERE oc.`eliminado_at` IS NULL
UNION ALL
SELECT
  'operador' AS `origen`,
  o.`id`, o.`rut`, o.`nombres`, o.`apellido_paterno`, o.`apellido_materno`,
  o.`turno`, o.`ubicacion`, o.`patente`,
  o.`codigo_maquinaria`,
  o.`equipo`,
  o.`horometro`, o.`kilometro`,
  o.`litros`, o.`usuario_id`, o.`fecha_registro`
FROM `operador` o
WHERE o.`eliminado_at` IS NULL;

-- 08.2. Totales mensuales por tipo de registro.
CREATE OR REPLACE VIEW `v_litros_por_mes` AS
SELECT
  DATE_FORMAT(`fecha_registro`,'%Y-%m') AS `mes`,
  `origen`,
  COUNT(*) AS `cantidad_registros`,
  SUM(`litros`) AS `litros_total`,
  ROUND(AVG(`litros`),2) AS `litros_promedio`
FROM `v_cargas_vigentes`
GROUP BY DATE_FORMAT(`fecha_registro`,'%Y-%m'), `origen`;

-- 08.3. Consumo agrupado por patente y maquinaria.
CREATE OR REPLACE VIEW `v_litros_por_maquinaria` AS
SELECT
  `patente`,
  `codigo_maquinaria`,
  COUNT(*) AS `cantidad_registros`,
  SUM(`litros`) AS `litros_total`,
  MIN(`fecha_registro`) AS `primer_registro`,
  MAX(`fecha_registro`) AS `ultimo_registro`,
  MAX(`horometro`) AS `horometro_maximo`
FROM `v_cargas_vigentes`
GROUP BY `patente`,`codigo_maquinaria`;

-- 08.4. Actividad y litros acumulados por usuario.
CREATE OR REPLACE VIEW `v_resumen_por_usuario` AS
SELECT
  u.`id` AS `usuario_id`,
  u.`username`,
  u.`rol`,
  u.`estado`,
  COUNT(c.`id`) AS `cantidad_registros`,
  COALESCE(SUM(c.`litros`),0) AS `litros_total`,
  MAX(c.`fecha_registro`) AS `ultima_actividad`
FROM `usuarios` u
LEFT JOIN `v_cargas_vigentes` c ON c.`usuario_id` = u.`id`
GROUP BY u.`id`,u.`username`,u.`rol`,u.`estado`;

-- ============================================================================
-- 09. CONSULTAS DE VERIFICACION POST-INSTALACION
-- ============================================================================
-- Ejecutar estas consultas para comprobar que las tablas y vistas existen.
SELECT
  TABLE_NAME AS `tabla`,
  TABLE_ROWS AS `filas_aproximadas`,
  ENGINE AS `motor`,
  TABLE_COLLATION AS `codificacion`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;

SELECT
  TABLE_NAME AS `vista`,
  'Disponible' AS `estado`
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

SELECT
  CONSTRAINT_NAME AS `regla_integridad`,
  TABLE_NAME AS `tabla`
FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND CONSTRAINT_TYPE IN ('FOREIGN KEY','CHECK')
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- ============================================================================
-- FIN DEL ESQUEMA EMEX
-- ============================================================================
