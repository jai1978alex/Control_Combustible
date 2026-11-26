-- Sitio Web Creado por Jerez.
-- Direccion: Miraflorez #1280
-- Quintero - Chile
-- jaime.jerez1978@gmail.com
-- https://www.
-- Creado, Programado y Diseñado por Jerez.
-- JJA

-- ---------------------------------------------
-- INICIO 06_MIGRACION_NOMBRE_USUARIOS.sql
-- ---------------------------------------------

USE `base_dato_formulario`;

-- MIGRACION: NOMBRE REGISTRADO DE CADA CUENTA
-- Agrega nombres y apellidos a las cuentas. Cada RUT queda asociado a un nombre, que el sistema
-- exige respetar en los formularios y que el panel de administrador muestra en la lista de usuarios.
-- Ejecutar una sola vez en una base existente y despues de generar un respaldo.
ALTER TABLE `usuarios`
  ADD COLUMN `nombres` VARCHAR(100) DEFAULT NULL COMMENT 'Nombres registrados del titular del RUT' AFTER `rut`,
  ADD COLUMN `apellido_paterno` VARCHAR(100) DEFAULT NULL COMMENT 'Apellido paterno registrado del titular' AFTER `nombres`,
  ADD COLUMN `apellido_materno` VARCHAR(100) DEFAULT NULL COMMENT 'Apellido materno registrado del titular' AFTER `apellido_paterno`;

-- Para las cuentas antiguas, el administrador puede completar el nombre desde el panel de administrador
-- (columna "Nombre" de la tabla Usuarios), o con una sentencia como esta:
-- UPDATE usuarios SET nombres='Juan', apellido_paterno='Perez', apellido_materno='Soto' WHERE username='usuario_operador';

-- ---------------------------------------------
-- FIN 06_MIGRACION_NOMBRE_USUARIOS.sql
-- ---------------------------------------------
