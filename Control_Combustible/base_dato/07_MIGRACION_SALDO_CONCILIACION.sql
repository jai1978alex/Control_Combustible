-- Sitio Web Creado por Jerez.
-- ---------------------------------------------
-- INICIO 07_MIGRACION_SALDO_CONCILIACION.sql
-- Prepara la tabla que utiliza "Saldo y conciliación".
-- Ejecutar en la base base_dato_formulario después de realizar un respaldo.
-- Esta versión es compatible con instalaciones antiguas: no depende de que
-- usuarios.id tenga exactamente el mismo tipo numérico para crear la tabla.
-- ---------------------------------------------

CREATE DATABASE IF NOT EXISTS `base_dato_formulario`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `base_dato_formulario`;

CREATE TABLE IF NOT EXISTS `estanque_movimientos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tipo` ENUM('ENTRADA','MEDICION') NOT NULL,
  `fecha` DATETIME NOT NULL,
  `litros` DECIMAL(12,2) NOT NULL,
  `referencia` VARCHAR(60) NULL,
  `observacion` VARCHAR(255) NULL,
  `usuario_id` INT NULL,
  `eliminado_at` DATETIME NULL,
  `eliminado_por` INT NULL,
  `creado_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_estanque_tipo_fecha` (`eliminado_at`,`tipo`,`fecha`),
  KEY `idx_estanque_usuario` (`usuario_id`),
  KEY `idx_estanque_eliminado_por` (`eliminado_por`),
  CONSTRAINT `chk_estanque_litros`
    CHECK ((`tipo`='ENTRADA' AND `litros` > 0) OR (`tipo`='MEDICION' AND `litros` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comprobación: debe aparecer estanque_movimientos en el resultado.
SELECT TABLE_SCHEMA, TABLE_NAME, ENGINE
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'estanque_movimientos';

-- ---------------------------------------------
-- FIN 07_MIGRACION_SALDO_CONCILIACION.sql
-- ---------------------------------------------
