# EMEX — Base de datos profesional

Este paquete contiene exclusivamente los archivos de base de datos del proyecto **Control de Combustible EMEX**.

## Archivos

- `01_BASE_DATOS_EMEX_PROFESIONAL.sql`: esquema completo para una instalación nueva.
- `02_DIAGNOSTICO_BASE_DATOS.sql`: consultas de comprobación para revisar tablas, índices, vistas y relaciones.
- `LEEME_INSTALACION.txt`: instrucciones resumidas.

## Instalación nueva en XAMPP

1. Inicia Apache y MySQL desde el panel de XAMPP.
2. Abre `http://localhost/phpmyadmin`.
3. Si necesitas conservar información de una base anterior, expórtala primero desde phpMyAdmin.
4. Selecciona la pestaña **Importar**.
5. Importa `01_BASE_DATOS_EMEX_PROFESIONAL.sql`.
6. Comprueba que se creó `base_dato_formulario` y que aparecen sus tablas y vistas.
7. Desde PowerShell, crea el primer administrador con el script PHP que ya incluye tu proyecto:

   ```powershell
   cd C:\xampp\htdocs\Control_Combustible\backend
   C:\xampp\php\php.exe crear_primer_admin.php
   ```

   El script genera una contraseña temporal aleatoria y obliga a cambiarla en el primer inicio de sesión.

## Compatibilidad con el proyecto

Se conservaron los nombres de base de datos, tablas y columnas que utiliza el código PHP revisado, entre ellos:

- `usuarios`
- `operador_cargador`
- `operador`
- `recuperacion_password`
- `auditoria`
- `mejoras_base_datos`

También se mantienen los nombres de columnas usados por los formularios y consultas PHP, como `usuario_id`, `eliminado_at`, `eliminado_por`, `fecha_registro`, `codigo_maquinaria`, `horometro`, `kilometro`, `litros` y `consumo`.

## Importante para una base de datos que ya tiene información

El archivo principal está pensado para una **instalación limpia**. `CREATE TABLE IF NOT EXISTS` no transforma automáticamente las tablas antiguas ni modifica sus columnas. No lo uses como sustituto de una migración sobre una instalación con datos.

Para una instalación existente:

1. Haz una exportación completa de la base actual.
2. No elimines ni recrees la base de datos.
3. Revisa las migraciones existentes del proyecto (`migracion_produccion_100.sql`, `MEJORA_BASE_DATOS_EMEX.sql` y `MEJORA_BASE_DATOS_V2.sql`) y ejecútalas solo después de verificar su compatibilidad con la estructura real.

## Seguridad de producción

- No uses `root` como usuario de conexión de la aplicación en producción.
- No guardes contraseñas en texto plano ni incluyas credenciales reales en este paquete.
- Configura `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` en el `.env` del proyecto.
- Usa una contraseña robusta y permisos mínimos para el usuario MySQL de la aplicación.
- Restringe el acceso web a la carpeta donde guardes archivos SQL y respaldos.
- Programa respaldos automáticos y prueba periódicamente su restauración.

## Nota de revisión

El esquema se preparó a partir de la estructura SQL y de las consultas PHP incluidas en el ZIP recibido. Se priorizó mantener la compatibilidad con el código existente. La importación debe probarse en la versión concreta de MySQL/MariaDB instalada en tu XAMPP antes de usarla en producción.
