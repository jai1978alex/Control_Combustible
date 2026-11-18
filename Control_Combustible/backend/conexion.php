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
   ------------------------------------- INICIO Jerez conexion.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO CONEXION A LA BASE DE DATOS

// Este archivo configura la zona horaria, carga la configuracion local,
// detecta si se ejecuta en entorno local o de produccion y abre la conexion MySQL.
// La conexion queda disponible en la variable $conn para los demas archivos.

// Activa el modo estricto de tipos para todo este archivo
declare(strict_types=1);


// TITULO CONFIGURACION GENERAL

// Define la zona horaria de Chile para todas las fechas y horas del sistema
date_default_timezone_set('America/Santiago');

// Hace que los errores de MySQL se lancen como excepciones
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


// TITULO CARGA DE CONFIGURACION LOCAL

// Carga el archivo que contiene la funcion loadLocalEnv()
require_once __DIR__ . '/env_loader.php';

// Carga la configuracion local opcional (variables de entorno)
loadLocalEnv();


// TITULO DATOS DE CONEXION A LA BASE DE DATOS

// Lee el servidor de la base de datos desde las variables de entorno
$host = trim((string)(getenv('DB_HOST') ?: ''));

// Lee el usuario de la base de datos desde las variables de entorno
$user = trim((string)(getenv('DB_USER') ?: ''));

// Lee la contraseña de la base de datos desde las variables de entorno
$pass = (string)(getenv('DB_PASS') ?: '');

// Lee el nombre de la base de datos desde las variables de entorno
$db   = trim((string)(getenv('DB_NAME') ?: ''));

// Lee el entorno de la aplicacion (por defecto: local) y lo pasa a minusculas
$appEnv = strtolower(
    trim((string)(getenv('APP_ENV') ?: 'local'))
);


// TITULO DETECCION DEL ENTORNO

/*
 * PHP puede ejecutarse de dos maneras principales:
 *
 * 1. Desde Apache/Navegador:
 *    http://localhost/control_combustible/
 *
 * 2. Desde CMD:
 *    C:\xampp\php\php.exe archivo.php
 *
 * Antes solamente se reconocía el primer caso.
 * Por eso reparar_acceso_superadmin.php no podía utilizar
 * la configuración local de XAMPP desde CMD.
 */

// Guarda true si el script se ejecuta desde la linea de comandos
$isCli = PHP_SAPI === 'cli';

// Es local si es consola, o si se accede desde el propio equipo (localhost)
$isLocal = $isCli || (
    in_array(
        $_SERVER['REMOTE_ADDR'] ?? '',
        ['127.0.0.1', '::1'],
        true
    )
    && preg_match(
        '/^(localhost|127\\.0\\.0\\.1)(:\\d+)?$/i',
        (string)($_SERVER['HTTP_HOST'] ?? '')
    )
);


// TITULO CONFIGURACION COMPATIBLE CON XAMPP

/*
 * Compatibilidad XAMPP únicamente para desarrollo local.
 *
 * Si estamos trabajando desde localhost o desde CMD y
 * APP_ENV no es production, se utilizan los valores locales.
 *
 * Esto permite ejecutar:
 *
 * - El login desde el navegador.
 * - El sistema desde localhost.
 * - reparar_acceso_superadmin.php desde CMD.
 */

// Entra al bloque si es entorno local y la aplicacion no esta en produccion
if ($isLocal && $appEnv !== 'production') {

    $host = $host !== ''
        ? $host
        : 'localhost';

    $db = $db !== ''
        ? $db
        : 'base_dato_formulario';

    $user = $user !== ''
        ? $user
        : 'root';

// Si no es entorno local o esta en produccion
} else {

    // TITULO VALIDACION DE CONFIGURACION DE PRODUCCION

    if (
        $host === ''
        || $user === ''
        || $db === ''
        || $pass === ''
    ) {

        error_log(
            'Configuración DB incompleta: en producción DB_HOST, DB_USER, DB_PASS y DB_NAME son obligatorios.'
        );

        http_response_code(500);

        exit(
            'Configuración de base de datos no disponible.'
        );
    }
}


// TITULO CONEXION CON MYSQL

// Intenta conectar con la base de datos y captura errores de conexion
try {

    $conn = mysqli_init();

    $conn->options(
        MYSQLI_OPT_CONNECT_TIMEOUT,
        5
    );

    $conn->real_connect(
        $host,
        $user,
        $pass,
        $db
    );

    $conn->set_charset('utf8mb4');


    // TITULO CONFIGURACION DEL MODO SQL

    $conn->query(
        "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
    );


// TITULO CONTROL DE ERRORES DE CONEXION

// Si falla la conexion con MySQL
} catch (mysqli_sql_exception $e) {

    error_log(
        'Error de conexión MySQL: ' . $e->getMessage()
    );

    http_response_code(500);

    exit(
        'No fue posible conectar con la base de datos. Verifique la configuración de MySQL.'
    );
}

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- FIN Jerez conexion.php --------------------------------------
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
