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
   ------------------------------------- INICIO Jerez env_loader.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO CONFIGURACION ESTRICTA DE TIPOS

// Activa el modo estricto de tipos para todo este archivo
declare(strict_types=1);


// TITULO CARGA DE CONFIGURACION LOCAL OPCIONAL

/*
 * Carga de configuración local opcional.
 *
 * El sistema prioriza siempre variables de entorno reales del servidor
 * (definidas por Apache/PHP-FPM, panel de hosting, etc.), tal como se
 * documenta en CONFIGURACION_SEGURA_PRODUCCION.txt. Sin embargo, muchos
 * hostings compartidos económicos no dan acceso a SSH ni una forma
 * sencilla de definir variables de entorno del proceso PHP.
 *
 * Para esos casos, `instalar.php` (instalador web) puede generar
 * `backend/config.local.php`, un archivo PHP simple que define las mismas
 * variables mediante putenv(). Este archivo:
 *   - NUNCA se genera automáticamente sin pasar por el instalador.
 *   - Está bloqueado a acceso web directo por backend/.htaccess.
 *   - Solo rellena variables que no estén ya definidas por el entorno
 *     real del servidor, para no pisar una configuración más avanzada.
 *
 * Si tu hosting sí permite variables de entorno reales, puedes ignorar
 * este mecanismo por completo y nunca se genera archivo alguno.
 */

// Define la funcion que carga el archivo de configuracion local (no devuelve nada)
function loadLocalEnv(): void {

    static $loaded = false;

    if ($loaded) {

        return;
    }

    $loaded = true;

    $archivo = __DIR__ . '/config.local.php';

    if (is_file($archivo)) {

        require $archivo;
    }
}

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- FIN Jerez env_loader.php --------------------------------------
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
