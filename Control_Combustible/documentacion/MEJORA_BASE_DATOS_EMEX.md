# Mejora de la base de datos EMEX

## Qué se encontró en el proyecto

La base actual se llama `base_dato_formulario` y utiliza InnoDB. Las tablas principales son:

- `usuarios`: cuentas, roles, estado y contraseñas protegidas.
- `operador_cargador`: registros de cargas ingresadas por cargadores.
- `operador`: registros de cargas ingresadas por operadores.
- `recuperacion_password`: enlaces de recuperación.
- `auditoria`: historial de acciones importantes.

El proyecto ya tenía claves primarias, relaciones con usuarios, `utf8mb4` y algunos índices. Por eso esta mejora conserva esa estructura: cambiar nombres o separar tablas ahora podría romper consultas PHP existentes.

## Mejoras agregadas

El archivo `MEJORA_BASE_DATOS_EMEX.sql` agrega índices complementarios para acelerar:

- Historiales y paginación ordenados por fecha.
- Búsquedas de registros vigentes (no eliminados).
- Consultas por usuario y fecha.
- Búsquedas de auditoría por usuario, tabla y registro.
- Limpieza y comprobación de enlaces de recuperación vencidos.

También crea `mejoras_base_datos`, una tabla pequeña que registra qué mejora fue aplicada.

## Cómo aplicarla en XAMPP

1. Inicia Apache y MySQL desde XAMPP.
2. Abre `http://localhost/phpmyadmin`.
3. Selecciona `base_dato_formulario`.
4. Entra a **Exportar** y descarga primero un respaldo SQL completo.
5. Abre la pestaña **Importar** y selecciona `MEJORA_BASE_DATOS_EMEX.sql` (o copia su contenido en la pestaña SQL).
6. Ejecuta y comprueba que el resultado final indique que la mejora fue aplicada.
7. No ejecutes el archivo sobre otra base de datos sin cambiar y revisar primero el `USE`.

## Importante

- La migración es aditiva y repetible: no borra ni modifica registros existentes.
- No cambia los nombres de tablas o columnas que utiliza el PHP.
- No convierte RUT en único, porque una persona puede tener muchos registros de carga.
- No agrega límites `CHECK` que puedan rechazar datos históricos existentes.
- Ejecuta primero en una copia de pruebas y conserva el respaldo original.

## Recomendaciones pendientes para una siguiente etapa

Para un sistema de producción con varias faenas y sucursales, el modelo actual todavía necesita definir junto con las pantallas y reglas del negocio:

1. Catálogo formal de faenas/sucursales y asignación de usuarios.
2. Catálogo de maquinaria y validación de códigos/patentes.
3. Catálogo de combustibles y unidades.
4. Stock por ubicación, movimientos y conciliación de inventario.
5. Registro de cargas con identificador de faena, maquinaria, combustible y responsable.
6. Política de retención, respaldo automático y prueba periódica de restauración.

No se agregaron estas tablas todavía porque el código PHP actual no las utiliza. Crearlas sin adaptar los formularios y consultas dejaría información aislada y podría dar una falsa impresión de que esas funciones ya están operativas.

## Seguridad de conexión

El proyecto ya contempla variables `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` y un usuario separado para producción. No uses `root` en un servidor publicado. Mantén la contraseña fuera de archivos públicos y concede solo los permisos que necesite la aplicación.
