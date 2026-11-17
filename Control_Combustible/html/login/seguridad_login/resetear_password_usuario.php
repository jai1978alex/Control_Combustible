<?php
declare(strict_types=1);
require_once __DIR__.'/../../../backend/conexion.php';
require_once __DIR__.'/../../../backend/security.php';
requireAdmin(); requirePost(); verifyCsrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id === (int)$_SESSION['usuario_id']) {
    header('Location: superadmin.php?error=reset_password'); exit;
}

$stmt = $conn->prepare('SELECT id, username, estado FROM usuarios WHERE id=? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if (!$u) {
    header('Location: superadmin.php?error=reset_password'); exit;
}

$temporal = generarPasswordTemporal();
$hash = password_hash($temporal, PASSWORD_DEFAULT);

$upd = $conn->prepare('UPDATE usuarios SET password=?, debe_cambiar_password=1 WHERE id=?');
$upd->bind_param('si', $hash, $id);

if (!$upd->execute()) {
    header('Location: superadmin.php?error=reset_password'); exit;
}

audit($conn, (int)$_SESSION['usuario_id'], 'RESETEAR_PASSWORD_USUARIO', 'usuarios', $id, 'username='.$u['username']);

/*
 * La contraseña temporal se entrega una sola vez, vía flash de sesión del
 * propio admin (nunca se guarda en base de datos, log ni URL). El usuario
 * afectado deberá cambiarla obligatoriamente en su próximo inicio de sesión.
 */
$_SESSION['flash_temp_password'] = $temporal;
$_SESSION['flash_temp_password_user'] = $u['username'];

header('Location: superadmin.php?success=password_reseteada');
exit;
