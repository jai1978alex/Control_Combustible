# Mejora de base de datos V2 — referencia no incluida

**Importante:** esta documentación menciona `base_dato/MEJORA_BASE_DATOS_V2.sql`, pero ese archivo no está incluido en el ZIP recibido. No ejecutes ni busques esa migración en esta entrega. Las instrucciones de aplicación que aparecen más abajo no son utilizables hasta contar con el SQL real, revisarlo y probarlo en una copia de la base.

# Mejora de la base de datos EMEX – Etapa 2 (V2)

Archivo a ejecutar en instalaciones existentes: `base_dato/MEJORA_BASE_DATOS_V2.sql`

Es **repetible** y **no borra ni modifica registros de carga**. No cambia nombres de tablas ni columnas, por lo que el PHP actual funciona sin tocar nada.

## Qué mejora

| Área | Cambio | Para qué sirve |
|---|---|---|
| Integridad | Clave foránea `eliminado_por → usuarios(id)` en `operador` y `operador_cargador` | Antes podía quedar un ID de usuario inexistente. Primero se limpian referencias huérfanas. |
| Reglas de datos | `CHECK`: litros > 0, horómetro/kilómetro ≥ 0, turno A–D | La base rechaza datos inválidos aunque falle una validación del PHP. Solo se activan si los datos existentes las cumplen. |
| Rendimiento | Índices por `patente`, por `codigo_maquinaria` y `(eliminado_at, litros)` | Reportes por máquina y totales de litros más rápidos. |
| Limpieza | Se eliminan 5 índices redundantes (`idx_cargador_usuario`, `idx_cargador_eliminado`, `idx_operador_usuario`, `idx_operador_eliminado`, `idx_auditoria_usuario`) | Eran prefijo de otros índices; solo hacían más lentos los INSERT/UPDATE. Las claves foráneas siguen cubiertas. |
| Reportes | Vistas `v_cargas_vigentes`, `v_litros_por_mes`, `v_litros_por_maquinaria`, `v_resumen_por_usuario` | Consultas listas desde phpMyAdmin o Excel. Excluyen registros eliminados. |
| Diagnóstico | Informe final de calidad de datos | Lista reglas activas, filas que incumplen, tablas con collation distinta, RUT con nombres distintos y horómetros que retroceden. |

## Cómo aplicarla (XAMPP)

1. Respaldo: phpMyAdmin → `base_dato_formulario` → **Exportar**.
2. Pestaña **Importar** → seleccione `MEJORA_BASE_DATOS_V2.sql` → Continuar.
3. Revise los resultados finales:
   - *regla_activa*: deben aparecer 6 reglas. Si falta alguna, hay filas que no la cumplen (el siguiente resultado las cuenta).
   - *horómetro que retrocede*: son lecturas sospechosas para revisar a mano; no se corrigen automáticamente.

Requiere MariaDB 10.3+ (XAMPP actual) o MySQL 8.0+ (el informe usa funciones de ventana).

## Instalaciones nuevas

`base_dato/base_dato_formulario` e `instalar.php` ya crean las tablas con estas mejoras (FK, CHECK e índices). Las vistas se agregan ejecutando después `MEJORA_BASE_DATOS_V2.sql`.

## Probado

Se ejecutó sobre MariaDB 10.11 con la base original + mejora 1, con datos de prueba: aplica sin errores, se puede ejecutar dos veces, omite las reglas cuando hay datos inválidos y las escrituras de la aplicación (INSERT de cargas, eliminación lógica) siguen funcionando. Pruébela primero en una copia.

## Sigue pendiente (requiere cambios en el PHP)

Catálogos de faenas, maquinaria y combustibles; stock y movimientos de inventario; política de retención de auditoría. Ver `MEJORA_BASE_DATOS_EMEX.md`.
