<?php
declare(strict_types=1);

date_default_timezone_set('America/Santiago');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/env_loader.php';
loadLocalEnv();

$host = trim((string)(getenv('DB_HOST') ?: ''));
$user = trim((string)(getenv('DB_USER') ?: ''));
$pass = (string)(getenv('DB_PASS') ?: '');
$db   = trim((string)(getenv('DB_NAME') ?: ''));
$appEnv = strtolower(trim((string)(getenv('APP_ENV') ?: 'local')));

$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    && preg_match('/^(localhost|127\\.0\\.0\\.1)(:\\d+)?$/i', (string)($_SERVER['HTTP_HOST'] ?? ''));

// Compatibilidad XAMPP únicamente para desarrollo local.
if ($isLocal && $appEnv !== 'production') {
    $host = $host !== '' ? $host : 'localhost';
    $db   = $db !== '' ? $db : 'base_dato_formulario';
    $user = $user !== '' ? $user : 'root';
} else {
    if ($host === '' || $user === '' || $db === '' || $pass === '') {
        error_log('Configuración DB incompleta: en producción DB_HOST, DB_USER, DB_PASS y DB_NAME son obligatorios.');
        http_response_code(500);
        exit('Configuración de base de datos no disponible.');
    }
}

try {
    $conn = mysqli_init();
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    $conn->real_connect($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');
    $conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
} catch (mysqli_sql_exception $e) {
    error_log('Error de conexión MySQL: ' . $e->getMessage());
    http_response_code(500);
    exit('No fue posible conectar con la base de datos. Verifique la configuración de MySQL.');
}
