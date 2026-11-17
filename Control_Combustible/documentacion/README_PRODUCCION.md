# Control de Combustible EMEX - entrega reforzada

## Instalación — dos caminos

### Opción A: tienes acceso SSH/terminal al hosting (recomendada)
1. Crear la base usando `base_dato/01_BASE_DATOS.sql`.
2. Para una instalación existente, no ejecutes una migración general que no está incluida. La única migración de esta entrega es `base_dato/03_MIGRACION_IDENTIDAD_QR.sql` y solo agrega el RUT maestro para la validación QR.
3. Crear un usuario MySQL de mínimos privilegios desde el panel del hosting o con las instrucciones SQL del apartado USUARIO MYSQL de `documentacion/CONFIGURACION_SEGURA_PRODUCCION.txt`.
4. Configurar las variables de `.env.example` como variables de entorno reales del servidor; no guardar secretos en el proyecto. `APP_NAME` controla el nombre de marca mostrado en títulos y logo (por defecto "EMEX"); `APP_BASE_URL` controla la ruta base para los enlaces de correo y, si el sistema no logra detectarla solo, también para las redirecciones internas.
5. Activar HTTPS y mantener `display_errors=Off`.
6. Configurar SMTP real para recuperación de contraseña.
7. Probar restauración de backups antes de declarar el sistema operativo.
8. Crear el primer administrador ejecutando, desde `backend/`, `php crear_primer_admin.php` (script de línea de comandos incluido). Genera una contraseña temporal aleatoria distinta en cada instalación y obliga a cambiarla en el primer inicio de sesión; el sistema ya no promociona automáticamente cuentas por nombre.

### Opción B: hosting compartido sin SSH/terminal (instalador web)
Si el hosting no permite ejecutar comandos ni definir variables de entorno del proceso PHP (común en planes de hosting compartido económico), sube el proyecto completo y visita `https://tudominio.com/instalar.php` desde el navegador.

El instalador pide los datos de conexión MySQL (los entrega tu panel de hosting), crea las tablas si no existen, crea el primer administrador con la contraseña que definas ahí mismo, y guarda todo en `backend/config.local.php` (bloqueado a acceso web por `.htaccess`, y priorizado solo cuando no existan variables de entorno reales).

**Apenas termine la instalación, elimina `instalar.php` del servidor** (o renómbralo/restríngelo). Queda bloqueado automáticamente por `backend/storage/instalado.lock`, pero no debe permanecer publicado.

Con cualquiera de las dos opciones siguen aplicando los puntos 5, 6 y 7 de la Opción A (HTTPS, SMTP real, probar backups).

## Cambios de esta entrega
- Eliminada la autocreación/promoción automática de administradores.
- Eliminado el mecanismo de reseteo de emergencia.
- Eliminada la autoalteración del esquema desde `conexion.php`.
- Eliminación de registros de combustible convertida en eliminación lógica y auditada.
- Usuarios eliminados pasan a estado inactivo para conservar trazabilidad.
- Exportaciones, totales e impresión excluyen registros eliminados.
- Endurecimiento adicional de cabeceras, sesión y huella de sesión.
- Recuperación de contraseña limpia el indicador de cambio pendiente.
- Añadida migración de producción y ejemplo de variables de entorno.

> Seguridad al 100% no es una propiedad absoluta: esta entrega deja la aplicación preparada y endurecida, pero antes de una puesta en producción empresarial se debe realizar una prueba externa de penetración, revisión del servidor, HTTPS real, backup/restauración y configuración SMTP.

## Mejoras de esta entrega (revisión de calidad)

- Corregido: el panel de operador (`panel.php`) sumaba en sus totales los
  registros eliminados lógicamente, mostrando cifras distintas a las del
  panel de administrador, la exportación CSV y la impresión por RUT (que sí
  los excluían). Ahora todos los totales son consistentes entre sí.
- Eliminado `html/login/seguridad_login/logout.php`: era una copia
  duplicada y rota que no cerraba realmente la sesión (no invalidaba la
  cookie ni la registraba en auditoría). Nada del sistema la usaba; todos
  los formularios de cierre de sesión apuntan al único endpoint correcto,
  `backend/logout.php`.
- La comprobación de compatibilidad de esquema (`columnaExiste`), antes
  duplicada dentro de `superadmin.php`, ahora vive una sola vez en
  `backend/security.php` y la reutilizan tanto `superadmin.php` como
  `panel.php`.

## Reparación de acceso de una instalación existente

Si el login muestra que el usuario no existe o falla porque falta la columna `debe_cambiar_password`, ejecutar desde `backend/`:

```
php reparar_acceso_superadmin.php
```

El script genera una contraseña temporal aleatoria nueva en cada ejecución (no hay ninguna contraseña fija dentro del proyecto), crea o restaura la cuenta `superadmin` y obliga a cambiar la contraseña en el siguiente inicio de sesión. Es acotado: no elimina registros de combustible ni tablas. Ver `REPARAR_ACCESO_SUPERADMIN.txt` para el detalle paso a paso.


## Validación de identidad QR

La cuenta del trabajador debe tener asignado su RUT maestro en `usuarios.rut`.
En instalaciones nuevas el campo se crea con el esquema principal. En bases
existentes, respalda la base y ejecuta `base_dato/03_MIGRACION_IDENTIDAD_QR.sql`.
El RUT maestro de cada cuenta se sigue guardando, pero ya no se exige que el RUT
ingresado en los formularios (o leído por QR) sea el mismo de la cuenta conectada.
Puede registrarse el RUT de cualquier operador; el servidor solo valida su formato
y dígito verificador.

## Usuarios y validación QR

Al crear cuentas desde el panel de administración, ingresa el RUT verificado del titular. Para cuentas antiguas de una base existente, asígnalo después de ejecutar la migración QR. El lector envía el contenido al servidor, que valida el formato y el dígito verificador del RUT del QR (de cualquier operador). Los usuarios con rol operador pueden registrar el RUT de otro operador; el inicio de sesión y los permisos por rol no cambian.
