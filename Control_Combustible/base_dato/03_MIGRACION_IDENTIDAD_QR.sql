-- Control de Combustible EMEX
-- Vincula cada cuenta de acceso con un RUT maestro para validar QR.
-- Ejecutar una sola vez en una base existente, después de respaldarla.
USE `base_dato_formulario`;

ALTER TABLE `usuarios`
  ADD COLUMN `rut` VARCHAR(12) DEFAULT NULL
    COMMENT 'RUT maestro asociado a la cuenta para validar identidad mediante QR',
  ADD UNIQUE KEY `uq_usuarios_rut` (`rut`);
