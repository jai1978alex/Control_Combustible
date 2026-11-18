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
   ------------------------------------- INICIO Jerez logout.php ----------------------------------------
   ------------------------------------------------------------------------------------------------------------- */


// TÍTULO: INICIO DEL PROGRAMA

// Le pide a PHP que sea estricto y no acepte datos mal escritos o mezclados
declare(strict_types=1);
// Trae las herramientas de seguridad (control de ingreso, llaves, cierre de sesión)
require_once __DIR__ . '/security.php';


// TÍTULO: REVISAR QUE LA PERSONA TENGA PERMISO

// Revisa que la persona haya iniciado sesión; si no, no la deja pasar
requireLogin();
// Acepta solo datos enviados desde un formulario; si llegan por otra vía, los detiene
requirePost();
// Comprueba la llave secreta del formulario, que demuestra que el envío viene de nuestra propia página
verifyCsrf();


// TÍTULO: CERRAR LA SESIÓN

// Guarda el número del usuario que está saliendo del sistema
$uid = (int)$_SESSION['usuario_id'];
// Trae el archivo que sabe conectarse a la base de datos
require_once __DIR__ . '/conexion.php';
// Anota en la bitácora que esta persona salió del sistema
audit($conn, $uid, 'LOGOUT', 'usuarios', $uid);
// Borra la "memoria" de la sesión, para que nadie más pueda usarla
destroyCurrentSession();


// TÍTULO: VOLVER A LA PÁGINA DE INICIO

// Envía a la persona a la página de inicio
header('Location: ' . appBasePath() . '/index.php');
exit;



/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez logout.php ------------------------------------------
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