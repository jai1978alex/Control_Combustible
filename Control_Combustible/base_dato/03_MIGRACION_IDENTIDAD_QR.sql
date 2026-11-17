-- Sitio Web Creado por Jerez.
-- Direccion: Miraflorez #1280
-- Quintero - Chile
-- jaime.jerez1978@gmail.com
-- https://www.
-- Creado, Programado y Diseñado por Jerez.
-- JJA

-- ---------------------------------------------
-- INICIO 03_MIGRACION_IDENTIDAD_QR.sql
-- ---------------------------------------------

USE `base_dato_formulario`;

-- ---------------------------------------------

-- MIGRACION DE IDENTIDAD MEDIANTE RUT Y CODIGO QR
-- Agrega a las cuentas de usuario un RUT maestro unico para permitir la validacion de identidad mediante QR.
-- Esta migracion debe ejecutarse una sola vez en una base de datos existente y despues de generar un respaldo.
ALTER TABLE `usuarios`
  ADD COLUMN `rut` VARCHAR(12) DEFAULT NULL
    COMMENT 'RUT maestro asociado a la cuenta para validar identidad mediante QR',
  ADD UNIQUE KEY `uq_usuarios_rut` (`rut`);

-- ---------------------------------------------

-- ---------------------------------------------

-- FIN 03_MIGRACION_IDENTIDAD_QR.sql
-- ---------------------------------------------

-- Sitio Web Creado por Jerez.
-- Direccion: Miraflorez #1280
-- Quintero - Chile
-- jaime.jerez1978@gmail.com
-- https://www.
-- Creado, Programado y Diseñado por Jerez.
-- JJA
