-- ============================================================================
-- EMEX | DIAGNOSTICO DE BASE DE DATOS
-- Ejecutar seleccionando primero la base base_dato_formulario.
-- Todas las consultas son de lectura.
-- ============================================================================

USE `base_dato_formulario`;

-- 01. Tablas y motor de almacenamiento.
SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;

-- 02. Columnas de las tablas principales.
SELECT TABLE_NAME, ORDINAL_POSITION, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE,
       COLUMN_DEFAULT, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('usuarios','operador_cargador','operador',
                     'recuperacion_password','auditoria','mejoras_base_datos')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- 03. Indices.
SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE,
       GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columnas
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE
ORDER BY TABLE_NAME, INDEX_NAME;

-- 04. Relaciones y reglas de integridad.
SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, CONSTRAINT_TYPE, CONSTRAINT_NAME;

-- 05. Cantidad de usuarios por rol y estado.
SELECT rol, estado, COUNT(*) AS total
FROM usuarios
GROUP BY rol, estado
ORDER BY rol, estado;

-- 06. Calidad basica de registros de combustible.
SELECT 'operador_cargador' AS tabla, COUNT(*) AS litros_no_validos
FROM operador_cargador WHERE litros <= 0
UNION ALL
SELECT 'operador', COUNT(*) FROM operador WHERE litros <= 0;

-- 07. Referencias de usuario inexistentes (deberia devolver cero).
SELECT 'operador_cargador.usuario_id' AS referencia, COUNT(*) AS huerfanos
FROM operador_cargador oc LEFT JOIN usuarios u ON u.id = oc.usuario_id
WHERE oc.usuario_id IS NOT NULL AND u.id IS NULL
UNION ALL
SELECT 'operador.usuario_id', COUNT(*)
FROM operador o LEFT JOIN usuarios u ON u.id = o.usuario_id
WHERE o.usuario_id IS NOT NULL AND u.id IS NULL;
