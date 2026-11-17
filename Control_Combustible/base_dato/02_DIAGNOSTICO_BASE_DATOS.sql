-- Sitio Web Creado por Jerez.
-- Direccion: Miraflorez #1280
-- Quintero - Chile
-- jaime.jerez1978@gmail.com
-- https://www.
-- Creado, Programado y Diseñado por Jerez.
-- JJA

-- ---------------------------------------------
-- INICIO 02_DIAGNOSTICO_BASE_DATOS.sql
-- ---------------------------------------------

USE `base_dato_formulario`;

-- ---------------------------------------------

-- TABLAS Y MOTOR DE ALMACENAMIENTO
-- Consulta las tablas existentes, su motor, cotejamiento y cantidad aproximada de filas.
SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;

-- ---------------------------------------------

-- COLUMNAS DE LAS TABLAS PRINCIPALES
-- Consulta la definicion y el orden de las columnas de las tablas principales.
SELECT TABLE_NAME, ORDINAL_POSITION, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE,
       COLUMN_DEFAULT, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('usuarios','operador_cargador','operador',
                     'recuperacion_password','auditoria','mejoras_base_datos')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- ---------------------------------------------

-- INDICES
-- Consulta los indices definidos y las columnas que componen cada indice.
SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE,
       GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columnas
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE
ORDER BY TABLE_NAME, INDEX_NAME;

-- ---------------------------------------------

-- RELACIONES Y REGLAS DE INTEGRIDAD
-- Consulta las restricciones de integridad registradas en la base de datos.
SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, CONSTRAINT_TYPE, CONSTRAINT_NAME;

-- ---------------------------------------------

-- CANTIDAD DE USUARIOS POR ROL Y ESTADO
-- Agrupa las cuentas segun su rol y estado actual.
SELECT rol, estado, COUNT(*) AS total
FROM usuarios
GROUP BY rol, estado
ORDER BY rol, estado;

-- ---------------------------------------------

-- CALIDAD BASICA DE REGISTROS DE COMBUSTIBLE
-- Cuenta los registros que presentan cantidades de litros no validas.
SELECT 'operador_cargador' AS tabla, COUNT(*) AS litros_no_validos
FROM operador_cargador WHERE litros <= 0
UNION ALL
SELECT 'operador', COUNT(*) FROM operador WHERE litros <= 0;

-- ---------------------------------------------

-- REFERENCIAS DE USUARIO INEXISTENTES
-- Comprueba referencias a usuarios que no tengan una cuenta asociada; el resultado esperado es cero.
SELECT 'operador_cargador.usuario_id' AS referencia, COUNT(*) AS huerfanos
FROM operador_cargador oc LEFT JOIN usuarios u ON u.id = oc.usuario_id
WHERE oc.usuario_id IS NOT NULL AND u.id IS NULL
UNION ALL
SELECT 'operador.usuario_id', COUNT(*)
FROM operador o LEFT JOIN usuarios u ON u.id = o.usuario_id
WHERE o.usuario_id IS NOT NULL AND u.id IS NULL;

-- ---------------------------------------------

-- ---------------------------------------------

-- FIN 02_DIAGNOSTICO_BASE_DATOS.sql
-- ---------------------------------------------

-- Sitio Web Creado por Jerez.
-- Direccion: Miraflorez #1280
-- Quintero - Chile
-- jaime.jerez1978@gmail.com
-- https://www.
-- Creado, Programado y Diseñado por Jerez.
-- JJA
