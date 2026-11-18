<?php
/* 
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA 
*/

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- INICIO Jerez crear_primer_admin.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO CREAR EL PRIMER ADMINISTRADOR (SOLO POR LINEA DE COMANDOS)

/*
 * Script de línea de comandos para crear el PRIMER administrador de una
 * instalación nueva. Solo funciona por CLI (nunca por navegador) y solo
 * si todavía no existe ningún usuario con rol 'admin'.
 *
 * Uso (desde la carpeta backend/):
 *   php crear_primer_admin.php
 *
 * Para crear administradores adicionales una vez que el sistema ya está
 * operativo, usa el panel (superadmin.php -> Crear usuario) con una
 * cuenta admin existente.
 */

// Activa el modo estricto de tipos para todo este archivo
declare(strict_types=1);


// TITULO PERMITIR SOLO LA EJECUCION POR CONSOLA

// Entra al bloque si el script NO se esta ejecutando desde la linea de comandos
if (PHP_SAPI !== 'cli') {

    http_response_code(403);

    exit("Este script solo puede ejecutarse desde la línea de comandos (php crear_primer_admin.php).\n");
}


// TITULO CARGAR LA CONEXION A LA BASE DE DATOS

// Carga la conexion a la base de datos (crea la variable $conn)
require_once __DIR__ . '/conexion.php';


// TITULO GENERAR CONTRASEÑA TEMPORAL

// Define la funcion que crea una contraseña temporal aleatoria
function generarPasswordTemporalCli(): string {

    $mayus = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    $minus = 'abcdefghijkmnpqrstuvwxyz';

    $nums = '23456789';

    $simb = '!@#$%&*-_+=?';

    $todos = $mayus.$minus.$nums.$simb;

    $pick = static fn(string $set): string => $set[random_int(0, strlen($set) - 1)];

    $chars = [$pick($mayus), $pick($minus), $pick($nums), $pick($simb)];

    for ($i = 0; $i < 12; $i++) {

        $chars[] = $pick($todos);
    }

    shuffle($chars);

    return implode('', $chars);
}


// TITULO VERIFICAR QUE NO EXISTA UN ADMINISTRADOR

// Cuenta cuantos usuarios con rol admin existen en la base de datos
$existente = $conn->query("SELECT COUNT(*) c FROM usuarios WHERE rol='admin'")->fetch_assoc();

// Entra al bloque si ya existe al menos un administrador
if ((int)$existente['c'] > 0) {

    fwrite(STDERR, "Ya existe al menos un administrador en esta base de datos.\n");

    fwrite(STDERR, "Este script solo se usa para la instalación inicial. Para crear\n");

    fwrite(STDERR, "administradores adicionales, entra al panel con una cuenta admin\n");

    fwrite(STDERR, "existente y usa 'Crear usuario'.\n");

    exit(1);
}


// TITULO PEDIR EL NOMBRE DE USUARIO

// Muestra el titulo del proceso en pantalla
fwrite(STDOUT, "=== Creación del primer administrador ===\n\n");

// Pide al usuario que escriba el nombre de usuario
fwrite(STDOUT, "Nombre de usuario (3-50 caracteres; letras, números, punto, guion o guion bajo): ");

// Lee lo que se escribe en la consola y quita espacios sobrantes
$username = trim((string)fgets(STDIN));

// Entra al bloque si el usuario no cumple el formato permitido
if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {

    fwrite(STDERR, "\nUsuario inválido. Debe tener entre 3 y 50 caracteres (letras, números, '.', '_' o '-').\n");

    exit(1);
}


// TITULO PEDIR EL CORREO ELECTRONICO (OPCIONAL)

// Pide el correo electronico, que se puede omitir con Enter
fwrite(STDOUT, "Correo electrónico (opcional, presiona Enter para omitir): ");

// Lee lo que se escribe en la consola y quita espacios sobrantes
$email = trim((string)fgets(STDIN));

// Entra al bloque si se escribio un correo y su formato no es valido
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

    fwrite(STDERR, "\nCorreo inválido.\n");

    exit(1);
}


// TITULO PREPARAR LOS DATOS DEL NUEVO ADMINISTRADOR

// Genera la contraseña temporal
$password = generarPasswordTemporalCli();

// Convierte la contraseña en un hash seguro para guardarla en la base de datos
$hash = password_hash($password, PASSWORD_DEFAULT);

// Si no hay correo, se guardara como nulo (NULL)
$emailParam = $email !== '' ? $email : null;


// TITULO GUARDAR EL ADMINISTRADOR EN LA BASE DE DATOS

// Prepara la consulta que inserta el nuevo usuario
$stmt = $conn->prepare('INSERT INTO usuarios (username, password, email, rol, estado, debe_cambiar_password) VALUES (?,?,?,?,?,?)');

// Rol del nuevo usuario: administrador
$rol = 'admin';

// Estado del nuevo usuario: activo
$estado = 'activo';

// Obliga a cambiar la contraseña en el primer inicio de sesion (1 = si)
$debeCambiar = 1;

// Asocia los datos a la consulta (cinco textos y un entero)
$stmt->bind_param('sssssi', $username, $hash, $emailParam, $rol, $estado, $debeCambiar);

// Intenta guardar el usuario y captura cualquier error
try {

    $stmt->execute();

// Si ocurre un error al guardar
} catch (Throwable $e) {

    fwrite(STDERR, "\nNo se pudo crear el usuario (¿el nombre de usuario o correo ya existe?).\n");

    fwrite(STDERR, "Detalle: " . $e->getMessage() . "\n");

    exit(1);
}

// Guarda el id que la base de datos asigno al nuevo administrador
$adminId = (int)$stmt->insert_id;


// TITULO GUARDAR REGISTRO DE AUDITORIA

// Intenta guardar la auditoria y captura cualquier error
try {

    $ip = '127.0.0.1 (CLI)';

    $ua = 'crear_primer_admin.php (CLI)';

    $auditStmt = $conn->prepare('INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalle, ip, user_agent) VALUES (?,?,?,?,?,?,?)');

    $accion = 'CREAR_PRIMER_ADMIN';

    $tabla = 'usuarios';

    $detalle = 'username=' . $username;

    $auditStmt->bind_param('ississs', $adminId, $accion, $tabla, $adminId, $detalle, $ip, $ua);

    $auditStmt->execute();

// Si falla la auditoria
} catch (Throwable $e) {

    // La auditoría no debe impedir la creación del administrador.
}


// TITULO MOSTRAR EL RESULTADO

// Informa que el administrador fue creado correctamente
fwrite(STDOUT, "\n✔ Administrador creado correctamente.\n\n");

// Muestra el nombre de usuario creado
fwrite(STDOUT, "Usuario:              {$username}\n");

// Muestra la contraseña temporal generada
fwrite(STDOUT, "Contraseña temporal:  {$password}\n\n");

// Advierte que la contraseña no se volvera a mostrar
fwrite(STDOUT, "Guarda esta contraseña ahora: no se volverá a mostrar ni se guarda en\n");

// Aclara que no se guarda en logs ni en la base de datos
fwrite(STDOUT, "ningún log ni base de datos. Deberás cambiarla obligatoriamente en el\n");

// Recuerda que debe cambiarse en el primer inicio de sesion
fwrite(STDOUT, "primer inicio de sesión.\n");

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- FIN Jerez crear_primer_admin.php --------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

/* 
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
*/
