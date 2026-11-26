-- Sitio Web Creado por Jerez.
-- ---------------------------------------------
-- INICIO 09_MIGRACION_MODO_OFFLINE.sql
-- Agrega la columna uuid_cliente a operador_cargador y operador.
-- La aplicación (PWA) genera un identificador único por cada carga hecha en el equipo; si el celular
-- reenvía una carga cuya respuesta se perdió por falta de señal, el servidor la reconoce y no la duplica.
-- Se puede ejecutar más de una vez sin problema. Compatible con MySQL y MariaDB.
-- Ejecutar en la base base_dato_formulario después de realizar un respaldo.
-- ---------------------------------------------

USE `base_dato_formulario`;

-- operador_cargador ---------------------------------------------------------
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'operador_cargador' AND COLUMN_NAME = 'uuid_cliente');
SET @sql := IF(@existe = 0,
  'ALTER TABLE `operador_cargador` ADD COLUMN `uuid_cliente` CHAR(36) NULL DEFAULT NULL AFTER `usuario_id`, ADD UNIQUE KEY `uq_cargador_uuid_cliente` (`uuid_cliente`)',
  'SELECT ''operador_cargador.uuid_cliente ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- operador ------------------------------------------------------------------
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'operador' AND COLUMN_NAME = 'uuid_cliente');
SET @sql := IF(@existe = 0,
  'ALTER TABLE `operador` ADD COLUMN `uuid_cliente` CHAR(36) NULL DEFAULT NULL AFTER `usuario_id`, ADD UNIQUE KEY `uq_operador_uuid_cliente` (`uuid_cliente`)',
  'SELECT ''operador.uuid_cliente ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Comprobación: deben aparecer las dos tablas.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'uuid_cliente';

-- ---------------------------------------------
-- FIN 09_MIGRACION_MODO_OFFLINE.sql
-- ---------------------------------------------
