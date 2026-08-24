-- =====================================================================
-- FIX: columna faltante "debe_cambiar_password" en la tabla usuarios
-- =====================================================================
-- Este es el problema que causaba el error:
--   Uncaught mysqli_sql_exception: Unknown column 'debe_cambiar_password'
--   in 'field list' ... login.php:30
--
-- El código (login.php, security.php) ya esperaba esta columna, pero
-- la base de datos existente se creó antes de que se agregara. Este
-- script la agrega si falta, sin tocar nada más. Es seguro ejecutarlo
-- más de una vez (no falla si la columna ya existe).
--
-- CÓMO EJECUTARLO (phpMyAdmin):
--   1. Abre phpMyAdmin -> selecciona la base "base_dato_formulario".
--   2. Pestaña "SQL".
--   3. Pega el contenido de este archivo y presiona "Continuar".
--
-- O por línea de comandos:
--   mysql -u root -p base_dato_formulario < FIX_debe_cambiar_password.sql
-- =====================================================================

USE base_dato_formulario;

SET @has_col = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'debe_cambiar_password'
);

SET @sql_col = IF(
    @has_col = 0,
    'ALTER TABLE usuarios ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0 AFTER estado',
    'SELECT ''La columna debe_cambiar_password ya existía, no se hizo ningún cambio.'' AS resultado'
);

PREPARE stmt FROM @sql_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Listo. La columna debe_cambiar_password existe en la tabla usuarios.' AS resultado;
