<?php
declare(strict_types=1);

/*
 * Script de línea de comandos para crear el PRIMER administrador de una
 * instalación nueva. Solo funciona por CLI (nunca por navegador) y solo
 * si todavía no existe ningún usuario con rol 'admin'.
 *
 * Uso (desde la carpeta backend/):
 *   php crear_primer_admin.php
 *
 * Para crear administradores adicionales una vez que el sistema ya está
 * operativo, usa el panel (superadmin.php -> Crear usuario) con una
 * cuenta admin existente.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo puede ejecutarse desde la línea de comandos (php crear_primer_admin.php).\n");
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

$existente = $conn->query("SELECT COUNT(*) c FROM usuarios WHERE rol='admin'")->fetch_assoc();
if ((int)$existente['c'] > 0) {
    fwrite(STDERR, "Ya existe al menos un administrador en esta base de datos.\n");
    fwrite(STDERR, "Este script solo se usa para la instalación inicial. Para crear\n");
    fwrite(STDERR, "administradores adicionales, entra al panel con una cuenta admin\n");
    fwrite(STDERR, "existente y usa 'Crear usuario'.\n");
    exit(1);
}

fwrite(STDOUT, "=== Creación del primer administrador ===\n\n");
fwrite(STDOUT, "Nombre de usuario (3-50 caracteres; letras, números, punto, guion o guion bajo): ");
$username = trim((string)fgets(STDIN));

if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
    fwrite(STDERR, "\nUsuario inválido. Debe tener entre 3 y 50 caracteres (letras, números, '.', '_' o '-').\n");
    exit(1);
}

fwrite(STDOUT, "Correo electrónico (opcional, presiona Enter para omitir): ");
$email = trim((string)fgets(STDIN));
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "\nCorreo inválido.\n");
    exit(1);
}

$password = generarPasswordTemporalCli();
$hash = password_hash($password, PASSWORD_DEFAULT);
$emailParam = $email !== '' ? $email : null;

$stmt = $conn->prepare('INSERT INTO usuarios (username, password, email, rol, estado, debe_cambiar_password) VALUES (?,?,?,?,?,?)');
$rol = 'admin';
$estado = 'activo';
$debeCambiar = 1;
$stmt->bind_param('sssssi', $username, $hash, $emailParam, $rol, $estado, $debeCambiar);

try {
    $stmt->execute();
} catch (Throwable $e) {
    fwrite(STDERR, "\nNo se pudo crear el usuario (¿el nombre de usuario o correo ya existe?).\n");
    fwrite(STDERR, "Detalle: " . $e->getMessage() . "\n");
    exit(1);
}

$adminId = (int)$stmt->insert_id;

try {
    $ip = '127.0.0.1 (CLI)';
    $ua = 'crear_primer_admin.php (CLI)';
    $auditStmt = $conn->prepare('INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalle, ip, user_agent) VALUES (?,?,?,?,?,?,?)');
    $accion = 'CREAR_PRIMER_ADMIN';
    $tabla = 'usuarios';
    $detalle = 'username=' . $username;
    $auditStmt->bind_param('ississs', $adminId, $accion, $tabla, $adminId, $detalle, $ip, $ua);
    $auditStmt->execute();
} catch (Throwable $e) {
    // La auditoría no debe impedir la creación del administrador.
}

fwrite(STDOUT, "\n✔ Administrador creado correctamente.\n\n");
fwrite(STDOUT, "Usuario:              {$username}\n");
fwrite(STDOUT, "Contraseña temporal:  {$password}\n\n");
fwrite(STDOUT, "Guarda esta contraseña ahora: no se volverá a mostrar ni se guarda en\n");
fwrite(STDOUT, "ningún log ni base de datos. Deberás cambiarla obligatoriamente en el\n");
fwrite(STDOUT, "primer inicio de sesión.\n");
