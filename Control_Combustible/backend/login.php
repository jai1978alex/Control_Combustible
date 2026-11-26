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
   ------------------------------------- INICIO Jerez login.php ---------------------------------------
   ------------------------------------------------------------------------------------------------------------- */


// TÍTULO: INICIO DEL PROGRAMA

// Le pide a PHP que sea estricto y no acepte datos mal escritos o mezclados
declare(strict_types=1);
// Trae el archivo que sabe conectarse a la base de datos (donde están guardados los usuarios)
require_once __DIR__ . '/conexion.php';
// Trae el archivo con las herramientas de seguridad (claves, límites de intentos, registro de acciones)
require_once __DIR__ . '/security.php';
// Acepta solo datos enviados desde el formulario; si alguien entra "a la fuerza" por otra vía, lo detiene
requirePost();


// TÍTULO: RECIBIR LOS DATOS DEL FORMULARIO

// Toma el nombre de usuario escrito y le quita los espacios de sobra al inicio y al final
$username = trim((string)($_POST['username'] ?? ''));
// Toma la contraseña escrita tal cual (no se le quitan espacios, porque podrían ser parte de la clave)
$password = (string)($_POST['password'] ?? '');
// Toma la "llave secreta" del formulario, que comprueba que el envío viene de nuestra propia página
$csrf = (string)($_POST['csrf_token'] ?? '');


// TÍTULO: REVISAR LA LLAVE DE SEGURIDAD DEL FORMULARIO

// Si la llave del formulario no es la correcta...
if (!verifyLoginCsrf($csrf)) {
    // ...devuelve a la persona a la página de inicio con un aviso de error Y detiene todo, no se sigue avanzando
    header('Location: ' . appBasePath() . '/index.php?error=1'); 
    exit;
}


// Borra la llave usada, para que no se pueda aprovechar una segunda vez
unset($_SESSION['login_csrf']);


// TÍTULO: REVISAR QUE LOS DATOS TENGAN UN FORMATO VÁLIDO

// Si el usuario no tiene entre 3 y 50 caracteres permitidos (letras, números, punto, guion), o la contraseña está vacía o es muy larga...
if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username) || strlen($password) < 1 || strlen($password) > 255) {
    // ...devuelve a la persona al inicio con un aviso de error y detiene todo
    header('Location: ' . appBasePath() . '/index.php?error=1'); exit;
}


// TÍTULO: LIMITAR LOS INTENTOS (para que nadie pruebe claves sin parar)

// Averigua desde qué dirección de internet está entrando la persona
$ip = clientIp();

// Si esa dirección, o ese usuario, ya intentó entrar demasiadas veces en poco tiempo...
if (!rateLimitFile('login-ip', $ip, LOGIN_WINDOW, LOGIN_MAX_ATTEMPTS, false) ||
    !rateLimitFile('login-user', strtolower($username), LOGIN_WINDOW, LOGIN_MAX_ATTEMPTS, false)) {
    // ...lo bloquea por un rato, lo devuelve al inicio con el aviso de bloqueo y detiene todo
    header('Location: ' . appBasePath() . '/index.php?error=bloqueo'); exit;
}


// TÍTULO: BUSCAR AL USUARIO EN LA BASE DE DATOS

// Prepara la búsqueda del usuario (con un espacio vacío "?" que se llena después, para que nadie pueda colar órdenes dañinas)
$stmt = $conn->prepare('SELECT id, username, password, rol, estado, updated_at, debe_cambiar_password FROM usuarios WHERE username = ? LIMIT 1');
// Rellena el espacio vacío con el nombre de usuario escrito
$stmt->bind_param('s', $username);
// Ejecuta la búsqueda
$stmt->execute();
// Guarda en una variable los datos del usuario encontrado (o nada si no existe)
$user = $stmt->get_result()->fetch_assoc();


// TÍTULO: COMPROBAR USUARIO Y CONTRASEÑA

// Si el usuario no existe, o no está activo, o la contraseña no coincide...
if (!$user || $user['estado'] !== 'activo' || !password_verify($password, $user['password'])) {
    // ...anota un intento fallido más para esta dirección de internet
    rateLimitFile('login-ip', $ip, LOGIN_WINDOW, LOGIN_MAX_ATTEMPTS, true);
    // ...y anota también un intento fallido más para este nombre de usuario
    rateLimitFile('login-user', strtolower($username), LOGIN_WINDOW, LOGIN_MAX_ATTEMPTS, true);
    // Deja registrado en la bitácora que hubo un intento de ingreso que falló
    audit($conn, $user ? (int)$user['id'] : null, 'LOGIN_FALLIDO', 'usuarios', $user ? (int)$user['id'] : null, 'Usuario: '.$username);
    // Devuelve a la persona al inicio con un aviso de error y detiene todo
    header('Location: ' . appBasePath() . '/index.php?error=1'); exit;
}


// TÍTULO: ACTUALIZAR LA CLAVE GUARDADA SI QUEDÓ ANTIGUA

// Si la forma en que está guardada la contraseña quedó anticuada...
if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    // ...la vuelve a guardar con el método de protección más moderno
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    // Prepara la orden para actualizar la contraseña en la base de datos
    $u = $conn->prepare('UPDATE usuarios SET password=? WHERE id=?');
    // Guarda el número del usuario; rellena la orden con la clave nueva y ese número; y la ejecuta
    $uid=(int)$user['id']; $u->bind_param('si',$newHash,$uid); $u->execute();
    // Anota la fecha y hora actual como momento de la última modificación
    $user['updated_at'] = date('Y-m-d H:i:s');
}


// TÍTULO: ABRIR LA SESIÓN (la "memoria" de que la persona ya entró)

// Cambia el número de sesión por uno nuevo, para que nadie pueda robar el anterior
session_regenerate_id(true);
// Marca que hay un usuario que ya entró al sistema
$_SESSION['user'] = true;
// Guarda el número del usuario
$_SESSION['usuario_id'] = (int)$user['id'];
// Guarda el nombre del usuario
$_SESSION['username'] = $user['username'];
// Guarda el tipo de usuario (por ejemplo: administrador u operador)
$_SESSION['rol'] = $user['rol'];
// Guarda la fecha en que se modificó por última vez el usuario
$_SESSION['user_updated_at'] = (string)$user['updated_at'];
// Guarda la hora de este momento, para saber cuándo fue su última actividad
$_SESSION['last_activity'] = time();
// Guarda una huella del navegador que usa la persona, para detectar si alguien más intenta usar su sesión
$_SESSION['binding'] = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
// Crea una llave secreta nueva para proteger los formularios del resto del sistema
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
// Crea otra llave secreta nueva para el próximo ingreso
$_SESSION['login_csrf'] = bin2hex(random_bytes(32));
// Guarda si la persona está obligada a cambiar su contraseña (1 = sí, 0 = no)
$_SESSION['debe_cambiar_password'] = (int)$user['debe_cambiar_password'];


// TÍTULO: DEJAR REGISTRO DEL INGRESO

// Anota en la bitácora que esta persona ingresó correctamente
audit($conn, (int)$user['id'], 'LOGIN_EXITOSO', 'usuarios', (int)$user['id']);


// TÍTULO: ENVIAR A LA PERSONA A LA PÁGINA QUE LE CORRESPONDE

// Si la persona está obligada a cambiar su contraseña...
if (!empty($_SESSION['debe_cambiar_password'])) {
    // ...la envía primero a la página para cambiarla Y detiene todo
    header('Location: ' . appBasePath() . '/html/programa/cambiar_password.php');
    exit;
}

// Si es administrador la envía al panel de administración; si no, la envía al panel normal del programa
$destino = match ($user['rol']) {
    'admin' => '/html/login/seguridad_login/superadmin.php',
    'supervisor' => '/html/login/seguridad_login/supervisor.php',
    default => '/html/programa/panel.php',
};
header('Location: ' . appBasePath() . $destino);
exit;



/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez login.php -----------------------------------------
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