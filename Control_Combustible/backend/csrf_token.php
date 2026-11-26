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
   ------------------------------------- INICIO Jerez csrf_token.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

/* ------------------------------------- INICIO csrf_token.php -------------------------------------
   Entrega el token CSRF vigente de la sesión. Lo usa la cola sin conexión (offline.js) antes de enviar
   los registros pendientes, porque el token que quedó en la página guardada puede ser de otra sesión.
   Responde siempre JSON; si no hay sesión válida responde 401 (requireLogin detecta Accept: application/json).
   ------------------------------------------------------------------------------------------------- */

declare(strict_types=1);

require_once __DIR__ . '/security.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonExit(405, ['ok' => false, 'codigo' => 'metodo']);
}

jsonExit(200, [
    'ok' => true,
    'csrf_token' => csrfToken(),
    'usuario_id' => (int)$_SESSION['usuario_id'],
]);

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- FIN Jerez csrf_token.php --------------------------------------
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
