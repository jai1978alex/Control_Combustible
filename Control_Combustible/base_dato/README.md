# EMEX — Base de datos

Este paquete contiene los archivos SQL incluidos en Control de Combustible EMEX.

## Archivos incluidos

- `01_BASE_DATOS.sql`: esquema completo para una instalación nueva.
- `02_DIAGNOSTICO_BASE_DATOS.sql`: consultas de comprobación de tablas, índices, vistas y relaciones.
- `03_MIGRACION_IDENTIDAD_QR.sql`: agrega el RUT maestro a cuentas existentes para habilitar la validación de identidad QR.
- `05_MIGRACION_VISTAS_REPORTES.sql`: crea las vistas `v_litros_por_mes`, `v_litros_por_maquinaria` y `v_resumen_por_usuario` que usa la pantalla Reportes (útil en bases instaladas sin ellas).

## Instalación nueva en XAMPP

1. Inicia Apache y MySQL desde XAMPP.
2. Abre `http://localhost/phpmyadmin`.
3. Exporta cualquier base anterior que necesites conservar.
4. Importa `01_BASE_DATOS.sql`.
5. Comprueba que se creó `base_dato_formulario`.
6. Crea el primer administrador con `backend/crear_primer_admin.php`, siguiendo las instrucciones de producción.

## Instalación existente

No importes el esquema principal encima de una base con información. Primero exporta un respaldo y revisa la estructura actual. La única migración incluida en este paquete es `03_MIGRACION_IDENTIDAD_QR.sql`; no reemplaza otras migraciones ni actualiza el resto del esquema.

## Validación de identidad QR

Cada cuenta de trabajador debe tener asignado su RUT real en `usuarios.rut`. Al crear una cuenta desde el panel de administración, ahora se solicita el RUT y se valida su dígito verificador. En una base recién creada, la columna ya existe. En una base existente, respalda la base y ejecuta `03_MIGRACION_IDENTIDAD_QR.sql`. Para cuentas antiguas, el administrador debe asociar el RUT verificado:

```sql
UPDATE usuarios SET rut = '12345678-5' WHERE username = 'usuario_operador';
```

Reemplaza los valores por los datos reales. El RUT es único por cuenta. Los operadores sin RUT asignado no podrán validar un QR ni registrar operaciones con un RUT distinto al suyo. Esta regla se aplica en el servidor (`validar_qr_identidad.php`, `guardar_cargador.php` y `guardar_operador.php`); los intentos rechazados quedan en auditoría (`RUT_NO_COINCIDE`, `QR_IDENTIDAD_RECHAZADA`). Las cuentas con rol administrador quedan exentas. Los QR de maquinaria que no traen RUT se siguen leyendo con normalidad.

## Seguridad

- No uses `root` como usuario de conexión en producción.
- No guardes contraseñas en texto plano ni incluyas credenciales reales en el ZIP.
- Configura las variables de conexión mediante el entorno o el instalador.
- Restringe el acceso web a los archivos SQL y respaldos.
- Programa respaldos y prueba su restauración.
