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
   ------------------------------------- INICIO Jerez reparar_acceso_superadmin.php -------------------
   ------------------------------------------------------------------------------------------------------------- */


// TÍTULO: INICIO DEL PROGRAMA

// Le pide a PHP que sea estricto y no acepte datos mal escritos o mezclados
declare(strict_types=1);


// TÍTULO: PERMITIR SOLO EL USO DESDE LA PANTALLA DE COMANDOS (por seguridad)

// Si el programa no se está ejecutando desde la línea de comandos (por ejemplo, si alguien lo abre desde el navegador)...
if (PHP_SAPI !== 'cli') {
    // ...avisa con el código 403, que significa "acceso prohibido"
    http_response_code(403);
    // Muestra el mensaje de que solo se puede usar desde la línea de comandos y detiene todo
    exit("Este script solo puede ejecutarse desde la línea de comandos (php reparar_acceso_superadmin.php).\n");
}

// Trae el archivo que sabe conectarse a la base de datos
require_once __DIR__ . '/conexion.php';


// TÍTULO: FUNCIÓN PARA CREAR UNA CONTRASEÑA TEMPORAL

// Crea una contraseña al azar y la entrega como texto
function generarPasswordTemporalCli(): string {
    // Lista de letras mayúsculas permitidas (sin las que se confunden, como la I y la O)
    $mayus = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    // Lista de letras minúsculas permitidas (sin las que se confunden, como la l y la o)
    $minus = 'abcdefghijkmnpqrstuvwxyz';
    // Lista de números permitidos (sin el 0 ni el 1, que se confunden con letras)
    $nums = '23456789';
    // Lista de símbolos permitidos
    $simb = '!@#$%&*-_+=?';
    // Junta todas las listas anteriores en una sola
    $todos = $mayus.$minus.$nums.$simb;
    // Crea una pequeña herramienta que elige un carácter al azar de la lista que se le entregue
    $pick = static fn(string $set): string => $set[random_int(0, strlen($set) - 1)];
    // Empieza la contraseña con un carácter de cada tipo: una mayúscula, una minúscula, un número y un símbolo
    $chars = [$pick($mayus), $pick($minus), $pick($nums), $pick($simb)];
    // Repite 12 veces lo siguiente...
    for ($i = 0; $i < 12; $i++) {
        // ...agrega un carácter al azar de cualquier tipo
        $chars[] = $pick($todos);
    }
    // Mezcla todos los caracteres para que no queden en un orden predecible
    shuffle($chars);
    // Une los caracteres y entrega la contraseña terminada
    return implode('', $chars);
}


// TÍTULO: MOSTRAR EL AVISO Y PEDIR CONFIRMACIÓN

// Muestra el título del programa en pantalla
fwrite(STDOUT, "=== Reparación / recuperación de acceso: cuenta 'superadmin' ===\n\n");
// Explica en pantalla qué hará el programa (primera parte)
fwrite(STDOUT, "Esto creará (si no existe) o restaurará el acceso de la cuenta\n");
// Explica en pantalla qué hará el programa (segunda parte)
fwrite(STDOUT, "'superadmin' con una contraseña nueva y aleatoria. No se tocan\n");
// Aclara en pantalla qué datos no se modifican
fwrite(STDOUT, "registros de operador, cargador ni auditoría.\n\n");
// Pide a la persona que escriba SI para continuar
fwrite(STDOUT, "Escribe SI para continuar: ");
// Lee lo que la persona escribió y le quita los espacios de sobra
$confirmacion = trim((string)fgets(STDIN));
// Si lo escrito (en mayúsculas) no es "SI"...
if (strtoupper($confirmacion) !== 'SI') {
    // ...muestra en pantalla que se canceló y que no se cambió nada Y termina el programa sin hacer nada más
    fwrite(STDOUT, "\nCancelado. No se realizó ningún cambio.\n");    
    exit(0);
}


// TÍTULO: PASO 1 - REVISAR QUE LA BASE DE DATOS ESTÉ COMPLETA

// 1) Compatibilidad: agrega la columna si faltaba.
// Pregunta a la base de datos si existe la columna "debe_cambiar_password" en la tabla de usuarios, y guarda la respuesta
$col = $conn->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='debe_cambiar_password'"
)->fetch_assoc();

// Si la columna no existe...
if ((int)$col['c'] === 0) {
    // ...la agrega a la tabla de usuarios
    $conn->query("ALTER TABLE usuarios ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0 AFTER estado");
    // Avisa en pantalla que la columna faltaba y fue agregada
    fwrite(STDOUT, "\nColumna debe_cambiar_password agregada (faltaba en esta instalación).\n");
}


// TÍTULO: PASO 2 - CREAR O RESTAURAR LA CUENTA SUPERADMIN

// 2) Crear o restaurar la cuenta superadmin con contraseña nueva.
// Crea la contraseña temporal al azar
$password = generarPasswordTemporalCli();
// Crea una versión "escondida" de esa contraseña, que es la que se guarda en la base de datos
$hash = password_hash($password, PASSWORD_DEFAULT);

// Prepara la búsqueda de la cuenta "superadmin"
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE username='superadmin' LIMIT 1");
// Ejecuta la búsqueda
$stmt->execute();
// Guarda en una variable los datos de la cuenta encontrada (o nada si no existe)
$existe = $stmt->get_result()->fetch_assoc();

// Si la cuenta "superadmin" ya existe...
if ($existe) {
    // ...prepara la orden para ponerle la contraseña nueva, dejarla como administrador, activa y obligada a cambiar la clave
    $upd = $conn->prepare("UPDATE usuarios SET password=?, rol='admin', estado='activo', debe_cambiar_password=1 WHERE username='superadmin'");
    // Rellena la orden con la contraseña escondida
    $upd->bind_param('s', $hash);
    // Ejecuta la orden
    $upd->execute();
    // Anota qué se hizo: la cuenta fue restaurada
    $accion = 'REPARAR_ACCESO_SUPERADMIN (restaurado)';
    // Guarda el número de la cuenta
    $superadminId = (int)$existe['id'];
// Si la cuenta no existe...
} else {
    // ...prepara la orden para crear la cuenta "superadmin" como administrador activo y obligada a cambiar la clave
    $ins = $conn->prepare("INSERT INTO usuarios (username, password, email, rol, estado, debe_cambiar_password) VALUES ('superadmin', ?, NULL, 'admin', 'activo', 1)");
    // Rellena la orden con la contraseña escondida
    $ins->bind_param('s', $hash);
    // Ejecuta la orden y crea la cuenta
    $ins->execute();
    // Anota qué se hizo: la cuenta fue creada
    $accion = 'REPARAR_ACCESO_SUPERADMIN (creado)';
    // Guarda el número de la cuenta recién creada
    $superadminId = (int)$ins->insert_id;
}


// TÍTULO: PASO 3 - DEJAR REGISTRO EN LA BITÁCORA

// Intenta anotar lo ocurrido en la bitácora
try {
    // Anota que la acción se hizo desde la línea de comandos del propio servidor
    $ip = '127.0.0.1 (CLI)';
    // Anota el nombre de este programa como "quién" hizo la acción
    $ua = 'reparar_acceso_superadmin.php (CLI)';
    // Prepara la orden para guardar el registro en la bitácora
    $auditStmt = $conn->prepare('INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalle, ip, user_agent) VALUES (?,?,?,?,?,?,?)');
    // Anota el nombre de la tabla que se modificó
    $tabla = 'usuarios';
    // Anota un detalle: qué cuenta fue modificada
    $detalle = 'username=superadmin';
    // Rellena la orden con todos los datos de lo ocurrido
    $auditStmt->bind_param('ississs', $superadminId, $accion, $tabla, $superadminId, $detalle, $ip, $ua);
    // Ejecuta la orden y guarda el registro
    $auditStmt->execute();
// Si algo falla al anotar en la bitácora...
} catch (Throwable $e) {
    // La auditoría no debe impedir la reparación de acceso.
    // no se hace nada, para que ese fallo no detenga la reparación de la cuenta
}


// TÍTULO: MOSTRAR EL RESULTADO EN PANTALLA

// Avisa en pantalla que todo salió bien
fwrite(STDOUT, "\n✔ Listo.\n\n");
// Muestra el nombre de usuario de la cuenta
fwrite(STDOUT, "Usuario:              superadmin\n");
// Muestra la contraseña temporal creada
fwrite(STDOUT, "Contraseña temporal:  {$password}\n\n");
// Explica que la contraseña solo se muestra ahora y no queda guardada en ningún lado (primera parte)
fwrite(STDOUT, "Guarda esta contraseña ahora: no se volverá a mostrar ni se guarda en\n");
// Explica que debe cambiarla apenas entre al sistema (primera parte)
fwrite(STDOUT, "ningún log ni base de datos. Entra al sistema y cámbiala de inmediato\n");
// Explica que debe cambiarla apenas entre al sistema (segunda parte)
fwrite(STDOUT, "desde 'Cambiar mi contraseña' (el sistema te obligará a hacerlo).\n");



/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez reparar_acceso_superadmin.php ---------------------
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