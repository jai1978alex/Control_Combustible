<?php
declare(strict_types=1);

/*
 * Script de línea de comandos para reparar/recuperar el acceso de la
 * cuenta 'superadmin' cuando el sistema quedó inaccesible (por ejemplo,
 * tras una migración incompleta o pérdida de la contraseña).
 *
 * A diferencia de la versión anterior de este proceso, este script NO
 * contiene ninguna contraseña fija dentro del código ni en un .sql: cada
 * vez que se ejecuta genera una contraseña aleatoria nueva, distinta,
 * que solo se muestra una vez en pantalla. Así, aunque este proyecto se
 * reutilice en varias instalaciones, ninguna comparte la misma clave de
 * recuperación.
 *
 * Uso (desde la carpeta backend/):
 *   php reparar_acceso_superadmin.php
 *
 * Qué hace:
 *   1) Agrega la columna debe_cambiar_password si faltaba (compatibilidad
 *      con instalaciones antiguas).
 *   2) Crea la cuenta 'superadmin' si no existe, o restaura su acceso si
 *      ya existía (rol admin, estado activo, contraseña nueva).
 *   3) Obliga a cambiar esa contraseña en el siguiente inicio de sesión.
 *
 * No elimina tablas ni registros de operador/cargador/auditoría.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo puede ejecutarse desde la línea de comandos (php reparar_acceso_superadmin.php).\n");
}

require_once __DIR__ . '/conexion.php';

function generarPasswordTemporalCli(): string {
    $mayus = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $minus = 'abcdefghijkmnpqrstuvwxyz';
    $nums = '23456789';
    $simb = '!@#$%&*-_+=?';
    $todos = $mayus.$minus.$nums.$simb;
    $pick = static fn(string $set): string => $set[random_int(0, strlen($set) - 1)];
    $chars = [$pick($mayus), $pick($minus), $pick($nums), $pick($simb)];
    for ($i = 0; $i < 12; $i++) {
        $chars[] = $pick($todos);
    }
    shuffle($chars);
    return implode('', $chars);
}

fwrite(STDOUT, "=== Reparación / recuperación de acceso: cuenta 'superadmin' ===\n\n");
fwrite(STDOUT, "Esto creará (si no existe) o restaurará el acceso de la cuenta\n");
fwrite(STDOUT, "'superadmin' con una contraseña nueva y aleatoria. No se tocan\n");
fwrite(STDOUT, "registros de operador, cargador ni auditoría.\n\n");
fwrite(STDOUT, "Escribe SI para continuar: ");
$confirmacion = trim((string)fgets(STDIN));
if (strtoupper($confirmacion) !== 'SI') {
    fwrite(STDOUT, "\nCancelado. No se realizó ningún cambio.\n");
    exit(0);
}

// 1) Compatibilidad: agrega la columna si faltaba.
$col = $conn->query(
    "SELECT COUNT(*) c FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='debe_cambiar_password'"
)->fetch_assoc();

if ((int)$col['c'] === 0) {
    $conn->query("ALTER TABLE usuarios ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0 AFTER estado");
    fwrite(STDOUT, "\nColumna debe_cambiar_password agregada (faltaba en esta instalación).\n");
}

// 2) Crear o restaurar la cuenta superadmin con contraseña nueva.
$password = generarPasswordTemporalCli();
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("SELECT id FROM usuarios WHERE username='superadmin' LIMIT 1");
$stmt->execute();
$existe = $stmt->get_result()->fetch_assoc();

if ($existe) {
    $upd = $conn->prepare("UPDATE usuarios SET password=?, rol='admin', estado='activo', debe_cambiar_password=1 WHERE username='superadmin'");
    $upd->bind_param('s', $hash);
    $upd->execute();
    $accion = 'REPARAR_ACCESO_SUPERADMIN (restaurado)';
    $superadminId = (int)$existe['id'];
} else {
    $ins = $conn->prepare("INSERT INTO usuarios (username, password, email, rol, estado, debe_cambiar_password) VALUES ('superadmin', ?, NULL, 'admin', 'activo', 1)");
    $ins->bind_param('s', $hash);
    $ins->execute();
    $accion = 'REPARAR_ACCESO_SUPERADMIN (creado)';
    $superadminId = (int)$ins->insert_id;
}

try {
    $ip = '127.0.0.1 (CLI)';
    $ua = 'reparar_acceso_superadmin.php (CLI)';
    $auditStmt = $conn->prepare('INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalle, ip, user_agent) VALUES (?,?,?,?,?,?,?)');
    $tabla = 'usuarios';
    $detalle = 'username=superadmin';
    $auditStmt->bind_param('ississs', $superadminId, $accion, $tabla, $superadminId, $detalle, $ip, $ua);
    $auditStmt->execute();
} catch (Throwable $e) {
    // La auditoría no debe impedir la reparación de acceso.
}

fwrite(STDOUT, "\n✔ Listo.\n\n");
fwrite(STDOUT, "Usuario:              superadmin\n");
fwrite(STDOUT, "Contraseña temporal:  {$password}\n\n");
fwrite(STDOUT, "Guarda esta contraseña ahora: no se volverá a mostrar ni se guarda en\n");
fwrite(STDOUT, "ningún log ni base de datos. Entra al sistema y cámbiala de inmediato\n");
fwrite(STDOUT, "desde 'Cambiar mi contraseña' (el sistema te obligará a hacerlo).\n");
