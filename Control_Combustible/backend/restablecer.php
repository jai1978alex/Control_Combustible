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
   ------------------------------------- INICIO Jerez restablecer.php ----------------------------------------
   ------------------------------------------------------------------------------------------------------------- */


// TÍTULO: INICIO DEL PROGRAMA

// Le pide a PHP que sea estricto y no acepte datos mal escritos o mezclados
declare(strict_types=1);
// Trae el archivo que sabe conectarse a la base de datos
require_once __DIR__.'/conexion.php';
// Trae las herramientas de seguridad (revisión de contraseñas, bitácora)
require_once __DIR__.'/security.php';

// Indica que la respuesta se entregará en formato JSON (un formato de datos que entiende la página)
header('Content-Type: application/json; charset=utf-8');


// TÍTULO: ACEPTAR SOLO ENVÍOS DESDE EL FORMULARIO

// Si los datos no llegaron por el método de envío de formularios (POST)...
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // ...avisa con el código 405, que significa "método no permitido"
    http_response_code(405);
    // Entrega el mensaje de que el método no está permitido Y detiene todo
    echo json_encode(['ok'=>false,'message'=>'Método no permitido']);   
    exit;
}


// TÍTULO: RECIBIR LOS DATOS ENVIADOS

// Lee los datos enviados (si no vienen en el formato esperado, usa los del formulario normal)
$d = json_decode(file_get_contents('php://input'), true) ?: $_POST;
// Toma la clave secreta del enlace que recibió la persona por correo
$token = (string)($d['token'] ?? '');
// Toma la contraseña nueva que la persona escribió
$password = (string)($d['password'] ?? '');


// TÍTULO: REVISAR QUE LOS DATOS SEAN VÁLIDOS

// Si la clave del enlace no tiene la forma correcta, o la contraseña nueva no cumple las reglas de seguridad...
if (!preg_match('/^[a-f0-9]{64}$/', $token) || !validPassword($password)) {
    // ...entrega el mensaje de que los datos no son válidos Y detiene todo
    echo json_encode(['ok'=>false,'message'=>'Datos inválidos.']);    
    exit;
}


// TÍTULO: BUSCAR EL ENLACE DE RECUPERACIÓN EN LA BASE DE DATOS

// Crea la versión "escondida" de la clave del enlace, que es la forma en que está guardada en la base de datos
$hash = hash('sha256', $token);
// Prepara la búsqueda de un enlace que coincida, que no se haya usado y que todavía no haya vencido (con un espacio vacío "?" que se llena después, para que nadie pueda colar órdenes dañinas)
$stmt = $conn->prepare('SELECT id,usuario_id FROM recuperacion_password WHERE token_hash=? AND usado=0 AND expira_en>NOW() LIMIT 1');
// Rellena el espacio vacío con la clave escondida
$stmt->bind_param('s', $hash);
// Ejecuta la búsqueda
$stmt->execute();
// Guarda en una variable los datos del enlace encontrado (o nada si no existe)
$r = $stmt->get_result()->fetch_assoc();

// Si no se encontró ningún enlace válido...
if (!$r) {
    // ...entrega el mensaje de que el enlace no sirve o ya venció Y detiene todo
    echo json_encode(['ok'=>false,'message'=>'El enlace es inválido o expiró.']);
    exit;
}


// TÍTULO: GUARDAR LA CONTRASEÑA NUEVA

// Inicia un "todo o nada": o se guarda todo bien, o no se guarda nada
$conn->begin_transaction();
// Intenta guardar los cambios
try {
    // Crea la versión "escondida" de la contraseña nueva, que es la que se guarda en la base de datos
    $new = password_hash($password, PASSWORD_DEFAULT);
    // Guarda el número del usuario dueño del enlace
    $uid = (int)$r['usuario_id'];

    // Prepara la orden para cambiar la contraseña del usuario y quitarle la obligación de cambiarla
    $u = $conn->prepare('UPDATE usuarios SET password=?, debe_cambiar_password=0 WHERE id=?');
    // Rellena la orden con la contraseña nueva y el número del usuario
    $u->bind_param('si', $new, $uid);
    // Ejecuta la orden
    $u->execute();

    /* Invalida cualquier otro enlace de recuperación pendiente. */
    // Prepara la orden para marcar como "usados" todos los enlaces de recuperación de este usuario
    $x = $conn->prepare('UPDATE recuperacion_password SET usado=1 WHERE usuario_id=?');
    // Rellena la orden con el número del usuario
    $x->bind_param('i', $uid);
    // Ejecuta la orden
    $x->execute();

    // Anota en la bitácora que este usuario cambió su contraseña
    audit($conn, $uid, 'CAMBIO_PASSWORD', 'usuarios', $uid);
    // Confirma el guardado definitivo de todos los cambios
    $conn->commit();

    // Entrega el mensaje de que la contraseña se actualizó correctamente
    echo json_encode(['ok'=>true,'message'=>'Contraseña actualizada correctamente.']);
// Si algo falló al guardar...
} catch (Throwable $e) {
    // ...deshace todo lo que se alcanzó a guardar
    $conn->rollback();
    // Anota el problema en el registro interno del servidor
    error_log('Error restableciendo contraseña: '.$e->getMessage());
    // Entrega el mensaje de que no fue posible cambiar la contraseña
    echo json_encode(['ok'=>false,'message'=>'No fue posible cambiar la contraseña.']);
}



/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez restablecer.php ------------------------------------------
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