-- 05_MIGRACION_VISTAS_REPORTES.sql
-- Crea (o reemplaza) las vistas que usa la pantalla de Reportes del administrador.
-- Es seguro ejecutarlo varias veces y en bases existentes: solo usa CREATE OR REPLACE VIEW
-- y no modifica tablas ni datos. Ejecútalo en la base de datos del sistema (phpMyAdmin > SQL).

-- Historial unificado de cargas vigentes.
CREATE OR REPLACE VIEW `v_cargas_vigentes` AS
SELECT
  'cargador' AS `origen`,
  oc.`id`, oc.`rut`, oc.`nombres`, oc.`apellido_paterno`, oc.`apellido_materno`,
  oc.`turno`, oc.`ubicacion`, oc.`patente`,
  oc.`codigo` AS `codigo_maquinaria`,
  NULL AS `equipo`,
  oc.`horometro`, NULL AS `kilometro`,
  oc.`litros`, oc.`usuario_id`, oc.`fecha_registro`
FROM `operador_cargador` oc
WHERE oc.`eliminado_at` IS NULL
UNION ALL
SELECT
  'operador' AS `origen`,
  o.`id`, o.`rut`, o.`nombres`, o.`apellido_paterno`, o.`apellido_materno`,
  o.`turno`, o.`ubicacion`, o.`patente`,
  o.`codigo_maquinaria`,
  o.`equipo`,
  o.`horometro`, o.`kilometro`,
  o.`litros`, o.`usuario_id`, o.`fecha_registro`
FROM `operador` o
WHERE o.`eliminado_at` IS NULL;

-- ---------------------------------------------

-- Totales mensuales por tipo de registro.
CREATE OR REPLACE VIEW `v_litros_por_mes` AS
SELECT
  DATE_FORMAT(`fecha_registro`,'%Y-%m') AS `mes`,
  `origen`,
  COUNT(*) AS `cantidad_registros`,
  SUM(`litros`) AS `litros_total`,
  ROUND(AVG(`litros`),2) AS `litros_promedio`
FROM `v_cargas_vigentes`
GROUP BY DATE_FORMAT(`fecha_registro`,'%Y-%m'), `origen`;

-- ---------------------------------------------

-- Consumo agrupado por patente y maquinaria.
CREATE OR REPLACE VIEW `v_litros_por_maquinaria` AS
SELECT
  `patente`,
  `codigo_maquinaria`,
  COUNT(*) AS `cantidad_registros`,
  SUM(`litros`) AS `litros_total`,
  MIN(`fecha_registro`) AS `primer_registro`,
  MAX(`fecha_registro`) AS `ultimo_registro`,
  MAX(`horometro`) AS `horometro_maximo`
FROM `v_cargas_vigentes`
GROUP BY `patente`,`codigo_maquinaria`;

-- ---------------------------------------------

-- Actividad y litros acumulados por usuario.
CREATE OR REPLACE VIEW `v_resumen_por_usuario` AS
SELECT
  u.`id` AS `usuario_id`,
  u.`username`,
  u.`rol`,
  u.`estado`,
  COUNT(c.`id`) AS `cantidad_registros`,
  COALESCE(SUM(c.`litros`),0) AS `litros_total`,
  MAX(c.`fecha_registro`) AS `ultima_actividad`
FROM `usuarios` u
LEFT JOIN `v_cargas_vigentes` c ON c.`usuario_id` = u.`id`
GROUP BY u.`id`,u.`username`,u.`rol`,u.`estado`;

-- ---------------------------------------------
