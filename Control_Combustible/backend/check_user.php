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
   ------------------------------------- INICIO Jerez check_user.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO VERIFICACION DE SESION ACTIVA

// Este archivo comprueba que el usuario haya iniciado sesion.
// Si no hay una sesion valida, la funcion de seguridad corta el acceso.

// Activa el modo estricto de tipos para todo este archivo
declare(strict_types=1);

// Carga el archivo de seguridad que contiene las funciones de sesion y proteccion
require_once __DIR__ . '/security.php';

// Exige que exista un usuario con sesion iniciada para continuar
requireLogin();

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- FIN Jerez check_user.php --------------------------------------
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
