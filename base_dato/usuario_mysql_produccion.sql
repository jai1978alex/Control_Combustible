-- Ejecutar SOLO con una cuenta administrativa de MySQL.
-- Cambie la contraseña antes de ejecutar.
CREATE USER 'control_combustible_app'@'localhost'
IDENTIFIED BY 'CAMBIAR_POR_UNA_CLAVE_ALEATORIA_LARGA';

GRANT SELECT, INSERT, UPDATE, DELETE
ON base_dato_formulario.*
TO 'control_combustible_app'@'localhost';

FLUSH PRIVILEGES;
