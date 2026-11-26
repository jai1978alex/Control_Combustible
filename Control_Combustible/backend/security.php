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
   ------------------------------------- INICIO Jerez security.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */


// TÍTULO: INICIO DEL PROGRAMA

// Le pide a PHP que sea estricto y no acepte datos mal escritos o mezclados
declare(strict_types=1);


// TÍTULO: DATOS FIJOS DE SEGURIDAD

// Tiempo máximo sin actividad antes de cerrar la sesión: 1800 segundos (30 minutos)
const SESSION_TIMEOUT = 1800;
// Tiempo en que se cuentan los intentos de ingreso: 900 segundos (15 minutos)
const LOGIN_WINDOW = 900;
// Cantidad máxima de intentos de ingreso permitidos en ese tiempo
const LOGIN_MAX_ATTEMPTS = 5;
// Tiempo en que se cuentan los intentos de recuperar contraseña: 900 segundos (15 minutos)
const RECOVERY_WINDOW = 900;
// Cantidad máxima de intentos de recuperación permitidos en ese tiempo
const RECOVERY_MAX_ATTEMPTS = 3;


// TÍTULO: PROTECCIONES DEL NAVEGADOR

// Función que le entrega al navegador una serie de reglas de seguridad para proteger la página
function securityHeaders(): void {
    // Evita que el navegador "adivine" el tipo de un archivo, para que no ejecute algo que no corresponde
    header('X-Content-Type-Options: nosniff');
    // Permite que esta página se muestre dentro de otras solo si son del mismo sitio
    header('X-Frame-Options: SAMEORIGIN');
    // No le cuenta a otras páginas desde dónde llegó la persona
    header('Referrer-Policy: no-referrer');
    // Apaga un filtro antiguo del navegador que ya no es seguro
    header('X-XSS-Protection: 0');
    // Evita que el navegador se conecte por adelantado a otros sitios
    header('X-DNS-Prefetch-Control: off');
    // Aísla esta página de las ventanas de otros sitios
    header('Cross-Origin-Opener-Policy: same-origin');
    // Permite usar solo la ubicación y la cámara del propio sitio; el micrófono queda prohibido
    header('Permissions-Policy: geolocation=(self), camera=(self), microphone=()');
    // Define de dónde se permite cargar scripts, estilos e imágenes; todo lo demás queda bloqueado
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self';");
    // Prohíbe que programas como Flash o PDF lean permisos de este sitio
    header('X-Permitted-Cross-Domain-Policies: none');
    // Impide que otros sitios usen los archivos de esta página
    header('Cross-Origin-Resource-Policy: same-origin');
    // Pide al navegador que no guarde copias de la página, para que no queden datos a la vista
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    // Lo mismo que lo anterior, para navegadores más antiguos
    header('Pragma: no-cache');
    // Si la página se está usando con conexión segura (https)...
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        // ...obliga al navegador a usar siempre conexión segura con este sitio durante un año
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

}
// Pone en marcha de inmediato las protecciones del navegador
securityHeaders();


// TÍTULO: PREPARAR LA SESIÓN (la "memoria" de la persona que entró)

// Si la sesión todavía no está abierta...
if (session_status() !== PHP_SESSION_ACTIVE) {
    // Rechaza números de sesión inventados por otras personas
    ini_set('session.use_strict_mode', '1');
    // Guarda el número de sesión solo en una "cookie" (un archivito del navegador), nunca en la dirección de la página
    ini_set('session.use_only_cookies', '1');
    // Impide que el número de sesión aparezca escrito en los enlaces
    ini_set('session.use_trans_sid', '0');
    // Impide que los programas de la página puedan leer la cookie de sesión
    ini_set('session.cookie_httponly', '1');
    // Impide que la cookie viaje cuando la persona llega desde otro sitio
    ini_set('session.cookie_samesite', 'Lax');
    // Define cuánto tiempo se conserva una sesión sin uso
    ini_set('session.gc_maxlifetime', (string)SESSION_TIMEOUT);
    // Esconde que el servidor usa PHP
    ini_set('expose_php', '0');
    // No muestra los errores en pantalla, para no revelar datos internos
    ini_set('display_errors', '0');
    // Anota los errores en el registro interno del servidor
    ini_set('log_errors', '1');

    // Si la sesión tiene el nombre estándar "PHPSESSID"...
    if (session_name() === 'PHPSESSID') {
        // ...le pone un nombre propio, "EMEXSESSID"
        session_name('EMEXSESSID');
    }

    // Revisa si la página se está usando con conexión segura (https)
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    // Define cómo será la cookie de sesión
    session_set_cookie_params([
        // Dura solo mientras el navegador esté abierto
        'lifetime' => 0,
        // Sirve para todo el sitio
        'path' => '/',
        // Viaja solo por conexión segura, si la hay
        'secure' => $secure,
        // Los programas de la página no pueden leerla
        'httponly' => true,
        // No viaja cuando la persona llega desde otro sitio
        'samesite' => 'Lax'
    ]);
    // Abre la sesión
    session_start();
}


// TÍTULO: FUNCIONES PARA CERRAR Y REVISAR LA SESIÓN

// Función que cierra la sesión de la persona por completo
function destroyCurrentSession(): void {
    // Borra todos los datos guardados en la sesión
    $_SESSION = [];
    // Si la sesión usa cookies...
    if (ini_get('session.use_cookies')) {
        // ...lee cómo estaba definida la cookie de sesión
        $p = session_get_cookie_params();
        // Manda al navegador una cookie vacía y ya vencida, para que la elimine
        setcookie(session_name(), '', [
            // Fecha de vencimiento en el pasado, para que se borre de inmediato
            'expires' => time() - 42000,
            // Ruta de la cookie (si no tiene, usa la raíz del sitio)
            'path' => $p['path'] ?: '/',
            // Sitio al que pertenece la cookie
            'domain' => $p['domain'] ?? '',
            // Si viajaba solo por conexión segura
            'secure' => (bool)$p['secure'],
            // Si estaba oculta para los programas de la página
            'httponly' => (bool)$p['httponly'],
            // Regla de envío desde otros sitios
            'samesite' => $p['samesite'] ?? 'Lax'
        ]);
    }
    // Destruye la sesión en el servidor
    session_destroy();
}

// Función que revisa que la sesión la esté usando el mismo navegador que la abrió
function sessionBindingMatches(): bool {
    // Toma los datos del navegador que se está usando ahora
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    // Averigua la dirección de internet de la persona
    $ip = clientIp();
    // Crea una "huella" del navegador a partir de esos datos
    $fingerprint = hash('sha256', $ua);
    // Responde "sí" si no hay huella guardada, o si la guardada es igual a la actual
    return empty($_SESSION['binding']) || hash_equals((string)$_SESSION['binding'], $fingerprint);
}
// TÍTULO: AYUDAS PARA PETICIONES DEL MODO SIN CONEXIÓN (PWA)

// Indica si quien llama pide una respuesta JSON (la cola offline envía "Accept: application/json").
function wantsJson(): bool {
    return str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
}

// Responde en JSON con el código HTTP indicado y detiene todo.
function jsonExit(int $http, array $data): never {
    http_response_code($http);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Envía a la ruta indicada (comportamiento de siempre) o, si la petición pide JSON, responde 401/403 sin redirigir.
function redirigirODenegar(string $ruta): never {
    if (wantsJson()) {
        $cambiarPass = str_contains($ruta, 'cambiar_password');
        jsonExit($cambiarPass ? 403 : 401, ['ok' => false, 'codigo' => $cambiarPass ? 'cambiar_password' : 'sesion']);
    }
    header('Location: ' . appBasePath() . $ruta);
    exit;
}



// TÍTULO: FUNCIÓN QUE EXIGE HABER INICIADO SESIÓN

// Función que deja pasar solo a quien ya entró al sistema y está al día con sus reglas
function requireLogin(): void {
    // Si falta alguno de los datos básicos de la persona (número, nombre o tipo de usuario)...
    if (empty($_SESSION['usuario_id']) || empty($_SESSION['username']) || empty($_SESSION['rol'])) {
        // ...la envía a la página de inicio con el aviso de acceso no permitido Y detiene todo
        redirigirODenegar('/index.php?error=acceso');
    }

    // Si la sesión la está usando un navegador distinto al que la abrió...
    if (!sessionBindingMatches()) {
        // ...cierra la sesión por seguridad
        destroyCurrentSession();
        // La envía a la página de inicio con el aviso de sesión no válida Y detiene todo
        redirigirODenegar('/index.php?error=sesion');
    }

    // Si pasó demasiado tiempo desde su última actividad...
    if (isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > SESSION_TIMEOUT) {
        // ...cierra la sesión por inactividad
        destroyCurrentSession();
        // La envía a la página de inicio con el aviso de sesión vencida Y detiene todo
        redirigirODenegar('/index.php?error=sesion');
    }

    // Usa la conexión a la base de datos que ya está abierta
    global $conn;
    // Trae el archivo que sabe conectarse a la base de datos
    require_once __DIR__ . '/conexion.php';
    // Guarda el número del usuario que está en sesión
    $uid = (int)$_SESSION['usuario_id'];
    // Prepara la búsqueda de los datos actuales del usuario (con un espacio vacío "?" que se llena después, para que nadie pueda colar órdenes dañinas)
    $stmt = $conn->prepare('SELECT username, rol, estado, updated_at, password, debe_cambiar_password FROM usuarios WHERE id=? LIMIT 1');
    // Rellena el espacio vacío con el número del usuario
    $stmt->bind_param('i', $uid);
    // Ejecuta la búsqueda
    $stmt->execute();
    // Guarda en una variable los datos del usuario encontrado (o nada si no existe)
    $u = $stmt->get_result()->fetch_assoc();

    // Si el usuario ya no existe o dejó de estar activo...
    if (!$u || $u['estado'] !== 'activo') {
        // ...cierra la sesión
        destroyCurrentSession();
        // La envía a la página de inicio con el aviso de acceso no permitido Y detiene todo
        redirigirODenegar('/index.php?error=acceso');
    }

    // Toma la fecha de última modificación que se guardó al iniciar sesión
    $sessionUpdated = (string)($_SESSION['user_updated_at'] ?? '');
    // Si esa fecha falta, o no coincide con la actual (porque alguien modificó al usuario)...
    if ($sessionUpdated === '' || !hash_equals($sessionUpdated, (string)$u['updated_at'])) {
        // ...cierra la sesión por seguridad
        destroyCurrentSession();
        // La envía a la página de inicio con el aviso de sesión no válida Y detiene todo
        redirigirODenegar('/index.php?error=sesion');
    }

    // Anota la hora de este momento como su última actividad
    $_SESSION['last_activity'] = time();
    // Actualiza el nombre del usuario por si cambió
    $_SESSION['username'] = $u['username'];
    // Actualiza el tipo de usuario por si cambió
    $_SESSION['rol'] = $u['rol'];
    // Actualiza si está obligado a cambiar su contraseña (1 = sí, 0 = no)
    $_SESSION['debe_cambiar_password'] = (int)$u['debe_cambiar_password'];

    // Averigua el nombre de la página que se está abriendo ahora
    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    // Prepara la lista de las únicas páginas permitidas mientras tenga pendiente cambiar la contraseña
    $permitidasConCambioPendiente = ['cambiar_password.php', 'logout.php'];
    // Si está obligado a cambiar su contraseña y quiere abrir otra página que no esté en esa lista...
    if (!empty($_SESSION['debe_cambiar_password']) && !in_array($script, $permitidasConCambioPendiente, true)) {
        // ...la envía a la página para cambiar la contraseña Y detiene todo
        redirigirODenegar('/html/programa/cambiar_password.php');
    }

}


// TÍTULO: FUNCIONES QUE EXIGEN PERMISOS ESPECIALES

// Función que deja pasar solo a los administradores
function requireAdmin(): void {
    // Primero exige que haya iniciado sesión
    requireLogin();
    // Si el tipo de usuario no es administrador...
    if (($_SESSION['rol'] ?? '') !== 'admin') {
        // ...avisa con el código 403, que significa "acceso prohibido"
        http_response_code(403);
        // Muestra el mensaje de acceso restringido y detiene todo
        exit('Acceso restringido.');
    }

}

// Función que acepta solo datos enviados desde un formulario
function requirePost(): void {
    // Si los datos no llegaron por el método de envío de formularios (POST)...
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // ...avisa con el código 405, que significa "método no permitido"
        http_response_code(405);
        // Indica que el único método aceptado es POST
        header('Allow: POST');
        // Muestra el mensaje de método no permitido y detiene todo
        exit('Método no permitido.');
    }
}


// TÍTULO: LLAVES SECRETAS DE LOS FORMULARIOS

// Función que entrega la llave secreta que protege los formularios del sistema
function csrfToken(): string {
    // Si todavía no existe esa llave...
    if (empty($_SESSION['csrf_token'])) {
        // ...crea una llave larga y al azar, imposible de adivinar
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    // Fin de la creación de la llave
    }
    // Entrega la llave
    return $_SESSION['csrf_token'];
}

// Función que entrega la llave secreta que protege el formulario de ingreso
function loginCsrfToken(): string {
    // Si todavía no existe esa llave...
    if (empty($_SESSION['login_csrf'])) {
        // ...crea una llave larga y al azar, imposible de adivinar
        $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
    // Fin de la creación de la llave
    }
    // Entrega la llave
    return $_SESSION['login_csrf'];
}

// Función que comprueba la llave secreta de un formulario del sistema
function verifyCsrf(): void {
    // Toma la llave que llegó junto con el formulario
    $token = (string)($_POST['csrf_token'] ?? '');
    // Si la llave está vacía, o no hay una guardada, o no son iguales...
    if ($token === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // Si la petición viene de la cola sin conexión, responde JSON en vez de texto plano
        if (wantsJson()) { jsonExit(403, ['ok' => false, 'codigo' => 'csrf']); }
        // ...avisa con el código 403, que significa "acceso prohibido"
        http_response_code(403);
        // Muestra el mensaje de solicitud no válida y detiene todo
        exit('Solicitud no válida.');
    }
}

// Función que comprueba la llave secreta del formulario de ingreso; responde "sí" o "no"
function verifyLoginCsrf(string $token): bool {
    // Responde "sí" solo si la llave no está vacía, existe una guardada y ambas son iguales
    return $token !== '' && !empty($_SESSION['login_csrf']) &&
        hash_equals($_SESSION['login_csrf'], $token);
}


// TÍTULO: HERRAMIENTAS GENERALES

// Función que limpia un texto antes de mostrarlo en pantalla
function e(mixed $value): string {
    // Cambia los símbolos peligrosos por inofensivos para que nadie pueda "colar" código en la página
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Función que averigua la dirección de internet de la persona
function clientIp(): string {

    // Entrega la dirección de internet de quien se conectó (o "0.0.0.0" si no se pudo saber)
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}


// Función que entrega el nombre del programa
function appName(): string {
    // Lee el nombre configurado en el servidor y le quita los espacios de sobra
    $name = trim((string)(getenv('APP_NAME') ?: ''));
    // Entrega ese nombre; si no hay ninguno configurado, entrega "EMEX"
    return $name !== '' ? $name : 'EMEX';
}


// Función que averigua en qué carpeta del sitio está instalado el programa
function appBasePath(): string {
    // Guarda la respuesta una vez encontrada, para no tener que buscarla de nuevo
    static $cached = null;
    // Si la respuesta ya se había encontrado antes...
    if ($cached !== null) {
        // ...la entrega de inmediato
        return $cached;    
    }

    // Lee la dirección principal de la página, si fue configurada en el servidor
    $envBase = trim((string)(getenv('APP_BASE_URL') ?: ''));
    // Si hay una dirección configurada...
    if ($envBase !== '') {
        // ...toma solo la parte de la carpeta de esa dirección
        $path = (string)(parse_url($envBase, PHP_URL_PATH) ?? '');
        // Entrega esa carpeta sin la barra "/" sobrante al final
        return $cached = rtrim($path, '/');
    }

    // Averigua dónde está guardado físicamente el programa en el servidor
    $appRoot = realpath(__DIR__ . '/..');
    // Averigua dónde está la carpeta principal del sitio web en el servidor
    $docRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));

    // Si se encontraron ambos lugares y el programa está dentro de la carpeta principal del sitio...
    if ($appRoot !== false && $docRoot !== false && str_starts_with($appRoot, $docRoot)) {
        // ...calcula la ruta del programa dentro del sitio
        $rel = substr($appRoot, strlen($docRoot));
        // Entrega esa ruta, con barras normales y sin la barra "/" sobrante al final
        return $cached = rtrim(str_replace('\\', '/', $rel), '/');
    }

    // Si nada de lo anterior funcionó, entrega una carpeta por defecto: "/control_combustible"
    return $cached = '/control_combustible';
}


// TÍTULO: HERRAMIENTAS DE LA BASE DE DATOS

// Función que responde "sí" si una tabla de la base de datos tiene cierta columna, o "no" si no la tiene
function columnaExiste(mysqli $conn, string $tabla, string $columna): bool {
    // Prepara la pregunta a la base de datos (con espacios vacíos "?" que se llenan después, para que nadie pueda colar órdenes dañinas)
    $st = $conn->prepare('SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    // Rellena los espacios vacíos con el nombre de la tabla y el de la columna
    $st->bind_param('ss', $tabla, $columna);
    // Ejecuta la pregunta
    $st->execute();
    // Responde "sí" si la columna fue encontrada al menos una vez
    return (int)$st->get_result()->fetch_assoc()['c'] > 0;
}

// Función que anota en la bitácora (el cuaderno de registro) lo que hizo una persona en el sistema
function audit(mysqli $conn, ?int $usuarioId, string $accion, ?string $tabla = null, ?int $registroId = null, ?string $detalle = null): void {
    // Intenta anotar el registro
    try {
        // Prepara la orden para guardar el registro en la bitácora
        $stmt = $conn->prepare('INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalle, ip, user_agent) VALUES (?,?,?,?,?,?,?)');
        // Averigua la dirección de internet de la persona
        $ip = clientIp();
        // Toma los datos del navegador de la persona (hasta 500 letras)
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        // Rellena la orden con todos los datos de lo ocurrido
        $stmt->bind_param('ississs', $usuarioId, $accion, $tabla, $registroId, $detalle, $ip, $ua);
        // Ejecuta la orden y guarda el registro
        $stmt->execute();
    // Si algo falla al anotar en la bitácora...
    } catch (Throwable $e) {
        // ...deja anotado el problema en el registro interno del servidor, sin detener el programa
        error_log('Auditoria no disponible: '.$e->getMessage());
    }
}


// TÍTULO: CONTROL DE INTENTOS (para frenar a quien insista demasiado)

// Función que limita cuántas veces se puede hacer algo en cierto tiempo; responde "sí" si aún puede intentar y "no" si ya intentó demasiado
function rateLimitFile(string $bucket, string $key, int $window, int $max, bool $consume = true): bool {
    // Define la carpeta donde se anotan los intentos
    $dir = __DIR__ . '/storage/rate_limit';
    // Si esa carpeta todavía no existe...
    if (!is_dir($dir)) {
        // ...la crea (el @ evita mostrar avisos feos si algo falla)
        @mkdir($dir, 0700, true);
    }
    // Convierte el motivo y la persona en un código secreto para usarlo como nombre de archivo
    $safe = preg_replace('/[^a-f0-9]/', '', hash('sha256', $bucket.'|'.$key));
    // Arma el nombre completo del archivo donde se anotan los intentos de esta persona
    $file = $dir . DIRECTORY_SEPARATOR . $safe . '.json';

    // Intenta hacer una limpieza de archivos viejos
    try {
        // Una de cada 100 veces, hace la limpieza
        if (random_int(1, 100) === 1) {
            // Revisa uno por uno los archivos de intentos que hay en la carpeta
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $oldFile) {
                // Si el archivo tiene más de un día (86400 segundos) sin cambios, lo borra
                if (@filemtime($oldFile) !== false && (time() - (int)filemtime($oldFile)) > 86400) @unlink($oldFile);
            }
        }
    // Si la limpieza falla, no se hace nada y el programa sigue
    } catch (Throwable $e) {}
    // Abre el archivo de intentos (o lo crea si no existe) para leerlo y escribirlo
    $fp = @fopen($file, 'c+');
    // Si no se pudo abrir el archivo...
    if (!$fp) {
        // ...anota el problema en el registro interno del servidor
        error_log('No se pudo abrir el almacenamiento de rate limiting.');
        // Y responde "no", por seguridad
        return false;
    }

    // Reserva el archivo para que nadie más lo use justo ahora
    flock($fp, LOCK_EX);
    // Lee todo lo que estaba anotado en el archivo
    $raw = stream_get_contents($fp);
    // Convierte lo leído en una lista que PHP entiende (o una lista vacía si no había nada)
    $data = json_decode($raw ?: '{}', true);
    // Anota la hora actual
    $now = time();
    // Toma la lista de intentos anteriores (o una lista vacía si no hay)
    $attempts = is_array($data['attempts'] ?? null) ? $data['attempts'] : [];
    // Se queda solo con los intentos que caen dentro del tiempo permitido
    $attempts = array_values(array_filter($attempts, static fn($t) => is_int($t) && ($now - $t) < $window));
    // Permite continuar solo si la cantidad de intentos es menor al máximo
    $allowed = count($attempts) < $max;

    // Si todavía puede intentar y se pidió anotar este intento...
    if ($allowed && $consume) {
        // ...anota este nuevo intento en la lista
        $attempts[] = $now;
    }

    // Borra lo que había escrito en el archivo
    ftruncate($fp, 0);
    // Vuelve al principio del archivo
    rewind($fp);
    // Escribe la lista actualizada de intentos
    fwrite($fp, json_encode(['attempts' => $attempts], JSON_UNESCAPED_SLASHES));
    // Se asegura de que todo quede realmente guardado
    fflush($fp);
    // Libera el archivo para que otros puedan usarlo
    flock($fp, LOCK_UN);
    // Cierra el archivo
    fclose($fp);
    // Entrega la respuesta: "sí" puede seguir, "no" está bloqueado por ahora
    return $allowed;
}


// TÍTULO: REVISIÓN DE DATOS ESCRITOS

// Función que revisa que un texto no esté vacío, no sea muy largo y no tenga caracteres raros
function validText(string $value, int $max = 100): bool {
    // Si el texto está vacío o es más largo que el máximo permitido, responde "no"
    if ($value === '' || mb_strlen($value) > $max) return false;
    // Responde "sí" solo si el texto no contiene caracteres de control invisibles
    return !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value);
}

// Función que revisa que un número tenga el formato correcto y esté dentro de los límites
function validDecimal(string $value, float $min = 0, float $max = 100000000): bool {
    // Si está vacío o no es un número con hasta 2 decimales, responde "no"
    if ($value === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) return false;
    // Convierte el texto en número
    $n = (float)$value;
    // Responde "sí" solo si es un número normal y está entre el mínimo y el máximo
    return is_finite($n) && $n >= $min && $n <= $max;
}

// Función que revisa que un texto sea una posición GPS válida (latitud y longitud)
function validGps(string $value): bool {
    // Si el texto no tiene la forma "latitud, longitud", responde "no"
    if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $value, $m)) return false;
    // Toma la latitud (la posición de norte a sur)
    $lat = (float)$m[1];
    // Toma la longitud (la posición de este a oeste)
    $lon = (float)$m[2];
    // Responde "sí" solo si ambos son números normales y están dentro de los límites del planeta
    return is_finite($lat) && is_finite($lon) &&
        $lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180;
}

// Función que revisa que una contraseña sea lo bastante fuerte
function validPassword(string $password): bool {
    // Debe tener 12 letras o más
    return strlen($password) >= 12
        // Y no puede pasar de 255 letras
        && strlen($password) <= 255
        // Debe tener al menos una letra mayúscula
        && preg_match('/[A-Z]/', $password)
        // Debe tener al menos una letra minúscula
        && preg_match('/[a-z]/', $password)
        // Debe tener al menos un número
        && preg_match('/\d/', $password)
        // Debe tener al menos un símbolo (por ejemplo ! # $ %)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

// Función que deja el RUT escrito siempre igual: sin espacios de sobra y en mayúsculas
function normalizeRut(string $rut): string {
    // Quita los espacios de sobra y pasa todo a mayúsculas
    return strtoupper(trim($rut));
}


// TÍTULO: CREAR CONTRASEÑAS TEMPORALES

// Función que crea una contraseña temporal al azar y la entrega como texto
function generarPasswordTemporal(): string {
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
    $pick = static function (string $set): string {
        // Entrega un carácter al azar de la lista
        return $set[random_int(0, strlen($set) - 1)];
    };

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



/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez security.php --------------------------------------
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