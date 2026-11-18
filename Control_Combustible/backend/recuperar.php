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
   ------------------------------------- INICIO Jerez recuperar.php ----------------------------------------
   ------------------------------------------------------------------------------------------------------------- */


// TÍTULO: INICIO DEL PROGRAMA

// Le pide a PHP que sea estricto y no acepte datos mal escritos o mezclados
declare(strict_types=1);
// Trae el archivo que sabe conectarse a la base de datos
require_once __DIR__.'/conexion.php';
// Trae las herramientas de seguridad (límites de intentos, bitácora, dirección de internet)
require_once __DIR__.'/security.php';
// Trae las herramientas para enviar correos
require_once __DIR__.'/mailer.php';

// Indica que la respuesta se entregará en formato JSON (un formato de datos que entiende la página)
header('Content-Type: application/json; charset=utf-8');


// TÍTULO: ACEPTAR SOLO ENVÍOS DESDE EL FORMULARIO

// Si los datos no llegaron por el método de envío de formularios (POST)...
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // ...avisa con el código 405, que significa "método no permitido"
    http_response_code(405);
    // Entrega el mensaje de que el método no está permitido Y detiene todo
    echo json_encode(['message'=>'Método no permitido']); 
    exit;
}


// TÍTULO: RECIBIR EL CORREO ESCRITO

// Lee los datos enviados (si no vienen en el formato esperado, usa los del formulario normal)
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
// Toma el correo escrito y le quita los espacios de sobra
$email = trim((string)($data['email'] ?? ''));
// Prepara el mensaje que se mostrará siempre, exista o no el correo (así nadie puede averiguar qué correos están registrados)
$generic = 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.';


// TÍTULO: LIMITAR LOS INTENTOS (para que nadie abuse del formulario)

/* Evita abuso del formulario de recuperación. */
// Si esta dirección de internet, o este correo, ya pidió demasiadas recuperaciones en poco tiempo...
if (!rateLimitFile('recovery-ip', clientIp(), RECOVERY_WINDOW, RECOVERY_MAX_ATTEMPTS, false) ||
    ($email !== '' && !rateLimitFile('recovery-email', strtolower($email), RECOVERY_WINDOW, RECOVERY_MAX_ATTEMPTS, false))) {
    // ...entrega el mensaje de siempre, sin hacer nada más Y detiene todo
    echo json_encode(['message'=>$generic]);
    exit;
}
// Anota un intento más para esta dirección de internet
rateLimitFile('recovery-ip', clientIp(), RECOVERY_WINDOW, RECOVERY_MAX_ATTEMPTS, true);
// Si la persona escribió un correo
if ($email !== '') {
    // anota también un intento más para ese correo
    rateLimitFile('recovery-email', strtolower($email), RECOVERY_WINDOW, RECOVERY_MAX_ATTEMPTS, true);
}


// TÍTULO: REVISAR QUE EL CORREO SEA VÁLIDO

// Si lo escrito no tiene forma de correo (por ejemplo, falta la @)...
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // ...entrega el mensaje de siempre, sin hacer nada más Y detiene todo
    echo json_encode(['message'=>$generic]); 
    exit;
}


// TÍTULO: BUSCAR AL USUARIO EN LA BASE DE DATOS

// Prepara la búsqueda de un usuario activo con ese correo (con un espacio vacío "?" que se llena después, para que nadie pueda colar órdenes dañinas)
$stmt = $conn->prepare("SELECT id,username FROM usuarios WHERE email=? AND estado='activo' LIMIT 1");
// Rellena el espacio vacío con el correo escrito
$stmt->bind_param('s', $email);
// Ejecuta la búsqueda
$stmt->execute();
// Guarda en una variable los datos del usuario encontrado (o nada si no existe)
$u = $stmt->get_result()->fetch_assoc();


// TÍTULO: PREPARAR EL ENLACE PARA CAMBIAR LA CONTRASEÑA

// Si se encontró un usuario con ese correo...
if ($u) {
    // Borra de la base de datos los enlaces antiguos: los que ya vencieron o ya se usaron
    $conn->query('DELETE FROM recuperacion_password WHERE expira_en < NOW() OR usado=1');

    // Un token nuevo invalida los anteriores del mismo usuario. 
    // Guarda el número del usuario encontrado
    $uid = (int)$u['id'];
    // Prepara la orden para marcar como "usados" los enlaces anteriores de este usuario
    $old = $conn->prepare('UPDATE recuperacion_password SET usado=1 WHERE usuario_id=? AND usado=0');
    // Rellena la orden con el número del usuario
    $old->bind_param('i', $uid);
    // Ejecuta la orden
    $old->execute();

    // Crea una clave secreta larga y al azar, imposible de adivinar (será parte del enlace)
    $raw = bin2hex(random_bytes(32));
    // Crea una versión "escondida" de esa clave, que es la que se guarda en la base de datos
    $hash = hash('sha256', $raw);
    // Calcula la fecha y hora en que el enlace dejará de servir: dentro de 30 minutos
    $exp = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');

    // Prepara la orden para guardar el nuevo enlace de recuperación
    $ins = $conn->prepare('INSERT INTO recuperacion_password(usuario_id,token_hash,expira_en) VALUES(?,?,?)');
    // Rellena la orden con el número del usuario, la clave escondida y la hora de vencimiento
    $ins->bind_param('iss', $uid, $hash, $exp);
    // Ejecuta la orden y guarda el enlace
    $ins->execute();

    // Lee la dirección principal de la página, configurada en el servidor
    $baseUrl = getenv('APP_BASE_URL');
    // Si no hay ninguna dirección configurada...
    if ($baseUrl === false || $baseUrl === '') {
        // ...usa una dirección de prueba local
        $baseUrl = 'http://localhost/control_combustible';
    }
    // Quita la barra "/" sobrante al final de la dirección
    $baseUrl = rtrim($baseUrl, '/');
    // Arma el enlace completo que recibirá la persona, con su clave secreta incluida
    $url = $baseUrl . '/html/programa/restablecer.html?token=' . rawurlencode($raw);


// TÍTULO: ENVIAR EL CORREO CON EL ENLACE

    // Escribe el asunto del correo
    $subject = 'Restablecer contraseña - Control de Combustible';
    // Escribe el mensaje del correo: saluda por su nombre, entrega el enlace y avisa que dura 30 minutos
    $body = "Hola {$u['username']},\n\nSolicitaste restablecer tu contraseña. El enlace expira en 30 minutos:\n$url\n\nSi no realizaste esta solicitud, ignora este mensaje.";

    /*
     * El resultado del envío no se expone al usuario (evita filtrar si el
     * correo existe), pero queda registrado en el log del servidor y en la
     * auditoría para que un administrador pueda detectar fallos de entrega.
     */
    // Envía el correo y guarda si salió bien o mal
    $enviado = sendAppMail($email, $subject, $body);

    // Anota en la bitácora que se pidió recuperar la contraseña (y deja una nota si el correo no pudo enviarse)
    audit($conn, $uid, 'SOLICITUD_RECUPERACION', 'usuarios', $uid, $enviado ? null : 'Fallo el envio del correo de recuperacion');
}


// TÍTULO: ENTREGAR LA RESPUESTA

// Entrega el mensaje de siempre, exista o no el correo
echo json_encode(['message'=>$generic]);



/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez recuperar.php ------------------------------------------
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