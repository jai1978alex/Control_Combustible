# Changelog de esta entrega

## Segunda pasada — portabilidad y pulido para reventa

1. **Rutas de redirección hardcodeadas a `/control_combustible/`**
   (`backend/security.php`, `login.php`, `logout.php`,
   `html/programa/cambiar_password.php`). El login, logout, cambio de
   contraseña obligatorio y control de sesión redirigían a una ruta fija en
   disco. Se agregó `appBasePath()` en `security.php`, que calcula la ruta
   base automáticamente (o la toma de `APP_BASE_URL` si se define), para que
   el sistema funcione instalado en cualquier carpeta, subdominio o en la
   raíz del sitio sin tocar código.

2. **`ErrorDocument` del `.htaccess` raíz** apuntaba también a
   `/control_combustible/errores/...`. Se cambió a rutas relativas.

3. **Documentación con un archivo SQL inexistente**:
   `CONFIGURACION_SEGURA_PRODUCCION.txt` indicaba ejecutar
   `migracion_seguridad.sql`, que no existe en el proyecto. Corregido para
   referenciar `migracion_produccion_100.sql`, que es el archivo real.

4. **`editar_registro.php` no mostraba el motivo de un error al guardar**
   (campos inválidos o fallo al guardar): el admin solo veía el formulario
   de nuevo sin explicación. Se agregó el mismo patrón de mensaje que ya
   usa `superadmin.php`.

5. **Auditoría sin paginación visible**: `auditoria.php` soportaba
   `?pagina=N` en el backend pero no mostraba enlaces "Anterior/Siguiente".
   Se agregó el mismo paginador que ya usa `superadmin.php`.

6. **Marca "EMEX" repetida en 7 archivos**: se centralizó en `appName()`
   (`backend/security.php`), configurable con la variable de entorno
   `APP_NAME`. Nota: los dos formularios estáticos sin PHP
   (`recuperar.html`, `restablecer.html`) siguen con el texto fijo — deben
   editarse a mano si se cambia de marca.

7. **Código muerto en `panel.php`**: quedaba un bloque
   `if (isset($_GET['ok']))` que nunca se ejecutaba (el backend redirige
   con `?success=1`, no `?ok=1`; el mensaje real lo muestra `panel.js` vía
   toast). Eliminado.



Revisión completa del código (backend PHP, frontend JS/CSS, SQL) buscando
bugs funcionales, duplicación e inconsistencias, sobre una base que ya
estaba fuertemente endurecida en seguridad (CSRF, sesiones, rate limiting,
SQL preparado, cabeceras, eliminación lógica, auditoría).

## Corregido

1. **Bug de totales en el panel de operador** (`html/programa/panel.php`)
   Las tarjetas de "Total litros" sumaban `SUM(litros)` sobre *todos* los
   registros, incluidos los eliminados lógicamente (`eliminado_at`). Esto
   contradecía el propio README ("Exportaciones, totales e impresión
   excluyen registros eliminados") y hacía que el total visible para un
   operador no coincidiera con el del panel de administrador. Se agregó el
   filtro `WHERE eliminado_at IS NULL`, con la misma comprobación de
   compatibilidad hacia atrás que ya usaba `superadmin.php` para no romper
   instalaciones que aún no corrieron la migración.

2. **Endpoint de logout duplicado y roto**
   (`html/login/seguridad_login/logout.php`) existía como archivo aparte,
   requería sesión y CSRF válidos, pero **no cerraba la sesión**: solo
   redirigía de vuelta a `superadmin.php`, sin llamar a
   `destroyCurrentSession()` ni registrar el evento en auditoría. Ningún
   formulario del sistema lo usaba (todos apuntan al endpoint real,
   `backend/logout.php`), así que se eliminó para no dejar código muerto ni
   una ruta confusa/engañosa si alguien la enlazara en el futuro.

## Refactor (sin cambio de comportamiento)

3. **Función `columnaExiste()` duplicada** — vivía únicamente dentro de
   `superadmin.php`. Se movió a `backend/security.php` como utilidad
   compartida y ahora la reutiliza también `panel.php` para el fix del
   punto 1, evitando dos implementaciones idénticas que podrían divergir
   con el tiempo.

## Revisado y sin cambios (ya estaba correcto)

- Todas las consultas de escritura usan sentencias preparadas con
  parámetros tipados; no se encontró inyección SQL.
- CSRF por token de sesión en todos los formularios POST, incluido un
  token de un solo uso específico para el login.
- Cabeceras de seguridad (CSP, HSTS condicional, X-Frame-Options,
  Referrer-Policy, etc.) consistentes entre `.htaccess` y `security.php`.
- Salida HTML escapada de forma consistente con `e()`
  (`htmlspecialchars`); no se encontró XSS reflejado ni almacenado.
- `toast.js` inserta SVG fijos con `innerHTML` (sin datos de usuario) y el
  mensaje con `textContent`; sin riesgo de XSS.
- Rate limiting de login y recuperación de contraseña, por IP y por
  usuario/correo, con limpieza periódica de archivos.
- Eliminación lógica y auditoría en registros y usuarios.
- Validación de RUT chileno (formato y dígito verificador) duplicada mas
  no inconsistente entre backend (PHP) y frontend (JS) — ambas
  implementan el mismo algoritmo Módulo 11 correctamente.

## No incluido en esta pasada (fuera de alcance de una revisión de código)

Como ya advierte `README_PRODUCCION.md`, una entrega "100% seguraˮ no es
una propiedad absoluta. Antes de producción real seguirá haciendo falta:
pentesting externo, revisión de la configuración del servidor/hosting,
HTTPS real con certificado válido, prueba de restauración de backups y
verificación del envío SMTP con credenciales reales.
