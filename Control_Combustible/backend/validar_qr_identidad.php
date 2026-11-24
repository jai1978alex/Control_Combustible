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
   ------------------------------------- INICIO Jerez validar_qr_identidad.php ----------------------------------------
   ------------------------------------------------------------------------------------------------------------- */


// TÍTULO: INICIO DEL PROGRAMA

// Le pide a PHP que sea estricto y no acepte datos mal escritos o mezclados
declare(strict_types=1);

// Trae el archivo que sabe conectarse a la base de datos
require_once __DIR__ . '/conexion.php';
// Trae las herramientas de seguridad (control de ingreso, llaves, bitácora)
require_once __DIR__ . '/security.php';
// Trae las herramientas para revisar y arreglar el RUT (el número de identificación chileno)
require_once __DIR__ . '/rut_helper.php';

// Indica que la respuesta se entregará en formato JSON (un formato de datos que entiende la página)
header('Content-Type: application/json; charset=utf-8');


// TÍTULO: REVISAR QUE LA PERSONA TENGA PERMISO

// Revisa que la persona haya iniciado sesión; si no, no la deja pasar
requireLogin();
// Acepta solo datos enviados desde un formulario; si llegan por otra vía, los detiene
requirePost();
// Comprueba la llave secreta del formulario, que demuestra que el envío viene de nuestra propia página
verifyCsrf();


// TÍTULO: FUNCIÓN PARA ENTREGAR LA RESPUESTA

// Recibe un código de estado y los datos de la respuesta; entrega la respuesta y termina el programa
function responderQR(int $estado, array $datos): never {
    // Avisa con el código de estado indicado (200 = todo bien; 400 o 422 = hubo un problema)
    http_response_code($estado);
    // Entrega los datos de la respuesta, dejando tildes y barras tal como se escriben Y detiene todo
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}


// TÍTULO: RECIBIR EL CONTENIDO DEL CÓDIGO QR

// Toma el texto que traía el código QR y le quita los espacios de sobra
$contenido = trim((string)($_POST['qr'] ?? ''));
// Si el texto está vacío o es demasiado largo (más de 10.000 letras)...
if ($contenido === '' || strlen($contenido) > 10000) {
    // ...responde con el código 400 y el aviso de que el QR está vacío o no es válido
    responderQR(400, ['ok' => false, 'mensaje' => 'El código QR está vacío o no es válido.']);
}


// TÍTULO: ORDENAR LOS DATOS QUE TRAE EL QR

// Intenta leer el contenido como una lista de datos en formato JSON
$datos = json_decode($contenido, true);
// Si no se pudo leer como lista...
if (!is_array($datos)) {
    // ...prepara una lista vacía para llenarla a mano
    $datos = [];
    // Corta el texto en partes, separándolas donde haya saltos de línea, punto y coma o barras "|", y revisa cada parte
    foreach (preg_split('/[\r\n;|]+/', $contenido) as $parte) {
        // Si la parte tiene la forma "nombre: valor" o "nombre = valor"...
        if (preg_match('/^\s*([^:=]+?)\s*[:=]\s*(.*?)\s*$/u', $parte, $coincidencia)) {
            // ...guarda en la lista el valor bajo su nombre
            $datos[$coincidencia[1]] = $coincidencia[2];
        }
    }
}


// TÍTULO: BUSCAR EL RUT DENTRO DE LOS DATOS

// Prepara una variable vacía donde se guardará el RUT encontrado
$rutQR = '';
// Revisa uno por uno los datos que trajo el QR
foreach ($datos as $clave => $valor) {
    // Limpia el nombre del dato: le quita tildes, símbolos y espacios, y lo pasa a minúsculas, para poder compararlo
    $claveNormalizada = strtolower(preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$clave) ?: (string)$clave));
    // Si el nombre del dato es "rut", "documento" o "documento de identidad", y el valor es un dato simple...
    if (in_array($claveNormalizada, ['rut', 'documento', 'documentoidentidad'], true) && is_scalar($valor)) {
        // ...guarda ese valor como el RUT, dejándolo escrito siempre con el mismo formato
        $rutQR = normalizeRut((string)$valor);
        // Y deja de buscar, porque ya lo encontró
        break;
    }
}


// TÍTULO: REVISAR QUE EL RUT SEA VÁLIDO

// Si no se encontró RUT, o no tiene la forma correcta, o su dígito verificador no calza...
if ($rutQR === '' || !validarFormatoRut($rutQR) || !validarRut($rutQR)) {
    // ...responde con el código 422 y el aviso de que el QR no contiene un RUT válido
    responderQR(422, ['ok' => false, 'mensaje' => 'El QR no contiene un RUT válido.']);
}


// TÍTULO: DEJAR REGISTRO Y ENTREGAR LA RESPUESTA

// Guarda el número del usuario que leyó el QR
$usuarioId = (int)$_SESSION['usuario_id'];

// El RUT leido en el QR debe ser el de la cuenta (los administradores quedan exentos)
$identidad = verificarRutDeCuenta($conn, $usuarioId, $rutQR);
if (!$identidad['ok']) {
    audit($conn, $usuarioId, 'QR_IDENTIDAD_RECHAZADA', 'usuarios', $usuarioId, 'Motivo: ' . $identidad['codigo'] . '; RUT del QR ' . $rutQR);
    responderQR(403, ['ok' => false, 'codigo' => $identidad['codigo'], 'mensaje' => $identidad['mensaje']]);
}

// Anota en la bitácora que esta persona leyó un QR con un RUT válido
audit($conn, $usuarioId, 'QR_IDENTIDAD_VALIDADA', 'usuarios', $usuarioId, 'QR con RUT válido leído: ' . $rutQR);
// Responde con el código 200 (todo bien), el aviso de éxito y el RUT validado
responderQR(200, ['ok' => true, 'mensaje' => 'RUT del QR validado correctamente.', 'rut' => $rutQR]);



/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez validar_qr_identidad.php ------------------------------------------
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