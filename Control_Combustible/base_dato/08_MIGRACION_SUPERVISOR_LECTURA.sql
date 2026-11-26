-- Ejecutar una sola vez en la base de datos base_dato_formulario.
-- Agrega el rol supervisor sin alterar las cuentas existentes.
USE `base_dato_formulario`;

ALTER TABLE `usuarios`
  MODIFY COLUMN `rol` ENUM('admin','supervisor','operador') NOT NULL DEFAULT 'operador';

INSERT IGNORE INTO `mejoras_base_datos` (`codigo`,`descripcion`)
VALUES ('EMEX_SUPERVISOR_LECTURA_2026','Rol supervisor y vista general de registros en solo lectura');
